<?php

namespace GienieLab\PasskeyAuth\Controller;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use SilverStripe\Security\Security;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Control\RequestHandler;
use GienieLab\PasskeyAuth\Service\PasskeyService;
use GienieLab\PasskeyAuth\Model\PasskeyCredential;

class PasskeyAuthController extends Controller
{
    private static $allowed_actions = [
        'registerBegin',
        'registerFinish',
        'loginBegin',
        'loginFinish',
        'challenge',
        'clearSessionFlag',
        'webauth',
        'debugConfig',
        'debugDomain'
    ];

    private static $url_handlers = [
        'challenge' => 'challenge',
        'register-begin' => 'registerBegin',
        'register-finish' => 'registerFinish',
        'login-begin' => 'loginBegin',
        'login-finish' => 'loginFinish',
        'clear-session-flag' => 'clearSessionFlag',
        'webauth' => 'webauth',
        'debug-config' => 'debugConfig',
        'debug-domain' => 'debugDomain'
    ];

    /**
     * Generate authentication challenge for passkey login
     * This is called from the JavaScript to initiate authentication
     */
    public function challenge(HTTPRequest $request)
    {
        if (!$request->isPost()) {
            return $this->httpError(405, 'Method not allowed');
        }

        try {
            // Check HTTPS requirement (except for localhost development)
            $this->checkHttpsRequirement($request);
            
            /** @var \GienieLab\PasskeyAuth\Service\PasskeyService $passkeyService */
            $passkeyService = Injector::inst()->get(PasskeyService::class);
            
            // Check if any passkeys are registered
            $credentialCount = PasskeyCredential::get()->count();
            if ($credentialCount === 0) {
                $response = new HTTPResponse();
                $response->setStatusCode(400);
                $response->addHeader('Content-Type', 'application/json');
                $response->setBody(json_encode([
                    'error' => 'No passkeys are registered. Please register a passkey first.'
                ]));
                return $response;
            }
            
            // Generate authentication challenge
            $getArgs = $passkeyService->generateAuthenticationChallenge();
            
            // Convert object to array if needed
            if (is_object($getArgs)) {
                $getArgsArray = json_decode(json_encode($getArgs), true);
            } else {
                $getArgsArray = $getArgs;
            }
            
            // Store challenge in session for verification
            $request->getSession()->set('passkey_challenge', $getArgsArray['challenge']);
            
            // Preserve BackURL if provided
            $backURL = $this->getCustomBackURL($request);
            if ($backURL) {
                $request->getSession()->set('BackURL', $backURL);
            }
            
            $response = new HTTPResponse();
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode($getArgsArray));
            
            return $response;

        } catch (\Exception $e) {
            $response = new HTTPResponse();
            $response->setStatusCode(500);
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode(['error' => 'Failed to generate challenge: ' . $e->getMessage()]));
            
            return $response;
        }
    }

    public function registerBegin(HTTPRequest $request)
    {
        if (!$request->isPost()) {
            return $this->httpError(405, 'Method not allowed');
        }

        try {
            // Check HTTPS requirement (except for localhost development)
            $this->checkHttpsRequirement($request);
            
            $member = Security::getCurrentUser();
            if (!$member) {
                return $this->httpError(401, 'User must be logged in to register passkey');
            }

            /** @var \GienieLab\PasskeyAuth\Service\PasskeyService $passkeyService */
            $passkeyService =  Injector::inst()->get(PasskeyService::class);
            
            // Generate registration challenge (returns array)
            $createArgsArray = $passkeyService->generateRegistrationChallenge($member);
            
            // Debug: Check the structure of the WebAuthn response
            if (!isset($createArgsArray['publicKey']['challenge'])) {
                $availableKeys = isset($createArgsArray['publicKey']) ? 
                    'publicKey keys: ' . implode(', ', array_keys($createArgsArray['publicKey'])) :
                    'top-level keys: ' . implode(', ', array_keys($createArgsArray));
                throw new \Exception('Challenge not found in WebAuthn response. Available: ' . $availableKeys);
            }
            
            // Store the complete createArgs as JSON for verification (required by processRegistration)
            $request->getSession()->set('passkey_registration_challenge', json_encode($createArgsArray));
            $request->getSession()->set('passkey_registration_user_id', $member->ID);

            $response = new HTTPResponse();
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode($createArgsArray));
            
            return $response;

        } catch (\Exception $e) {
            $response = new HTTPResponse();
            $response->setStatusCode(500);
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode(['error' => 'Failed to begin registration: ' . $e->getMessage()]));
            
            return $response;
        }
    }

    public function registerFinish(HTTPRequest $request)
    {
        if (!$request->isPost()) {
            return $this->httpError(405, 'Method not allowed');
        }

        try {
            $member = Security::getCurrentUser();
            if (!$member) {
                return $this->httpError(401, 'User must be logged in to register passkey');
            }

            $registrationData = json_decode($request->getBody(), true);
            $challenge = $request->getSession()->get('passkey_registration_challenge');
            
            // Debug: Log the received registration data structure
            error_log('Registration data received: ' . print_r($registrationData, true));
            error_log('Challenge from session: ' . $challenge);
            
            if (!$registrationData || !$challenge) {
                throw new \Exception('Invalid registration data or missing challenge');
            }

            /** @var \GienieLab\PasskeyAuth\Service\PasskeyService $passkeyService */
            $passkeyService = Injector::inst()->get(PasskeyService::class);

            // Process registration
            $credential = $passkeyService->processRegistration($registrationData, $challenge, $member);
            
            // Clear session data
            $request->getSession()->clear('passkey_registration_challenge');
            $request->getSession()->clear('passkey_registration_user_id');

            $response = new HTTPResponse();
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode([
                'success' => true,
                'message' => 'Passkey registered successfully',
                'credentialId' => $credential->CredentialID
            ]));
            
            return $response;

        } catch (\Exception $e) {
            $response = new HTTPResponse();
            $response->setStatusCode(500);
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode(['error' => 'Registration failed: ' . $e->getMessage()]));
            
            return $response;
        }
    }

    public function loginBegin(HTTPRequest $request)
    {
        // Alias for challenge method for backward compatibility
        return $this->challenge($request);
    }

    public function loginFinish(HTTPRequest $request)
    {
        if (!$request->isPost()) {
            return $this->httpError(405, 'Method not allowed');
        }

        try {
            $authenticationData = json_decode($request->getBody(), true);
            $challenge = $request->getSession()->get('passkey_challenge');
            
            if (!$authenticationData || !$challenge) {
                throw new \Exception('Invalid authentication data or missing challenge');
            }

            /** @var \GienieLab\PasskeyAuth\Service\PasskeyService $passkeyService */
            $passkeyService = Injector::inst()->get(PasskeyService::class);
            
            // Verify authentication
            $member = $passkeyService->processAuthentication($authenticationData, $challenge);
            
            if (!$member) {
                throw new \Exception('Authentication verification failed');
            }
            
            // Log the user in using SilverStripe's Security class
           // Get the IdentityStore service
            $identityStore = Injector::inst()->get(IdentityStore::class);

            // Log in the member
            $identityStore->logIn($member, true, $request);
            
            // Clear challenge
            $request->getSession()->clear('passkey_challenge');

            // Determine redirect URL with BackURL support
            $backURL = $this->getCustomBackURL($request);
            $redirectURL = $backURL ?: Security::config()->get('default_login_dest') ?: Director::baseURL();
            
            // Clear BackURL from session to prevent reuse
            $request->getSession()->clear('BackURL');

            $response = new HTTPResponse();
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode([
                'success' => true,
                'message' => 'Authentication successful',
                'redirectURL' => $redirectURL
            ]));
            
            return $response;

        } catch (\Exception $e) {
            $response = new HTTPResponse();
            $response->setStatusCode(401);
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode(['error' => 'Authentication failed: ' . $e->getMessage()]));
            
            return $response;
        }
    }

    /**
     * Get allowed credentials for authentication
     */
    protected function getAllowedCredentials()
    {
        /** @var \GienieLab\PasskeyAuth\Service\PasskeyService $passkeyService */
        $passkeyService = Injector::inst()->get(PasskeyService::class);
        
        return $passkeyService->getAllCredentials();
    }
    
    /**
     * Get the BackURL from various sources in order of preference
     * @param HTTPRequest $request
     * @return string|null
     */
    protected function getCustomBackURL(HTTPRequest $request)
    {

        $referer = $request->getHeader('Referer');
        if ($referer) {
            $parsedUrl = parse_url($referer);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                $refererBackURL = $queryParams['BackURL'] ?? null;
                if ($refererBackURL) {
                    return $refererBackURL;
                }
            }
        }
        
        // 4. Use SilverStripe's standard method
        return RequestHandler::getBackURL();
    }
    
    /**
     * Clear session flags for passkey registration prompt
     */
    public function clearSessionFlag(HTTPRequest $request)
    {
        $session = $request->getSession();
        $session->clear('ShowPasskeyRegistrationPrompt');
        $session->clear('PasskeyRegistrationEmail');
        
        return $this->httpResponse(200, [
            'success' => true,
            'message' => 'Session flags cleared'
        ]);
    }
    
    /**
     * Check if HTTPS is required and available
     */
    protected function checkHttpsRequirement(HTTPRequest $request)
    {
        $host = $request->getHeader('Host');
        $isLocalhost = strpos($host, 'localhost') !== false || 
                      strpos($host, '127.0.0.1') !== false || 
                      strpos($host, '.local') !== false;
        
        // Allow HTTP for localhost development
        if (!$isLocalhost && !Director::is_https()) {
            throw new \Exception('Passkeys require HTTPS. Please use a secure connection.');
        }
    }

    /**
     * Handle the passkey registration page
     */
    public function webauth(HTTPRequest $request)
    {
        // Ensure user is logged in
        $member = Security::getCurrentUser();
        if (!$member) {
            return $this->redirect('Security/login');
        }
        
        // Check if user already has passkeys
        $passkeyCount = PasskeyCredential::get()
            ->filter('MemberID', $member->ID)
            ->count();
            
        if ($passkeyCount > 0) {
            // User already has passkeys, redirect to original destination
            $backURL = $request->getSession()->get('PostPasskeyRegistrationURL') ?: '/admin';
            return $this->redirect($backURL);
        }
        
        // Render the passkey registration page
        return $this->renderWith(['WebAuthRegistration', 'Security', 'Page']);
    }

    /**
     * Debug endpoint to check configuration and environment
     */
    public function debugConfig(HTTPRequest $request)
    {

        // Only allow in dev mode for security
        if (!Director::isDev()) {
            return $this->httpError(404, 'Not found');
        }

        $passkeyService = Injector::inst()->get(PasskeyService::class);
         
        $debugInfo = [
            'environment' => [
                'mode' => Director::get_environment_type(),
                'is_dev' => Director::isDev(),
                'is_https' => Director::is_https(),
                'current_domain' => $request->getHeader('Host'),
                'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            ],
            'passkey_config' => [
                'rp_name' => $passkeyService->config()->get('rp_name'),
                'rp_id_configured' => $passkeyService->config()->get('rp_id'),
                'rp_id_actual' => $passkeyService->getCurrentDomain(),
                'timeout' => $passkeyService->config()->get('timeout'),
                'require_user_verification' => $passkeyService->config()->get('require_user_verification'),
                'require_user_presence' => $passkeyService->config()->get('require_user_presence'),
            ],
            'recommendations' => []
        ];

        // Add recommendations
        if (!Director::is_https() && !$this->isLocalhostDomain($request->getHeader('Host'))) {
            $debugInfo['recommendations'][] = 'HTTPS is required for WebAuthn in production. Configure SSL for your domain.';
        }

        $actualRpId = $passkeyService->config()->get('rp_id') ?: $passkeyService->getCurrentDomain();
     
        if ($actualRpId !== $request->getHeader('Host')) {
            $debugInfo['recommendations'][] = 'RP ID (' . $actualRpId . ') does not match current domain (' . $request->getHeader('Host') . '). This will cause "operation is insecure" errors.';
        }

        $response = HTTPResponse::create(json_encode($debugInfo, JSON_PRETTY_PRINT));
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Check if a domain is considered localhost/development
     */
    protected function isLocalhostDomain($domain)
    {
        return strpos($domain, 'localhost') !== false || 
               strpos($domain, '127.0.0.1') !== false || 
               strpos($domain, '.local') !== false ||
               strpos($domain, '.test') !== false ||
               strpos($domain, '.dev') !== false ||
               strpos($domain, 'dev.') === 0;
    }

    /**
     * Debug endpoint to check domain detection
     */
    public function debugDomain(HTTPRequest $request)
    {
        if (!Director::isDev()) {
            return $this->httpError(404, 'Not found');
        }

        $passkeyService = Injector::inst()->get(PasskeyService::class);
        
        $domainInfo = [
            'request_headers' => [
                'Host' => $request->getHeader('Host'),
                'X-Forwarded-Host' => $request->getHeader('X-Forwarded-Host'),
                'Origin' => $request->getHeader('Origin'),
                'Referer' => $request->getHeader('Referer'),
            ],
            'server_vars' => [
                'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
                'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? null,
                'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
            ],
            'silverstripe_detection' => [
                'Director::absoluteBaseURL()' => Director::absoluteBaseURL(),
                'Director::is_https()' => Director::is_https(),
            ],
            'passkey_service' => [
                'getCurrentDomain()' => $passkeyService->getCurrentDomain(),
                'configured_rp_id' => $passkeyService->config()->get('rp_id'),
            ]
        ];

        $response = HTTPResponse::create(json_encode($domainInfo, JSON_PRETTY_PRINT));
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }
}
