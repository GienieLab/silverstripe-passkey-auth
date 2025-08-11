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
use SilverStripe\Security\SecurityToken;
use SilverStripe\Security\Permission;
use SilverStripe\Core\Cache\CacheFactory;
use Psr\Log\LoggerInterface;
use GienieLab\PasskeyAuth\Service\PasskeyService;
use GienieLab\PasskeyAuth\Model\PasskeyCredential;

class PasskeyAuthController extends Controller
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    private static $allowed_actions = [
        'registerBegin' => '->checkPasskeyCSRF',
        'registerFinish' => '->checkPasskeyCSRF',
        'loginBegin' => '->checkPasskeyCSRF',
        'loginFinish' => '->checkPasskeyCSRF',
        'challenge' => '->checkPasskeyCSRF',
        'clearSessionFlag' => true,
        'webauth' => true,
        'debugConfig' => '->checkDebugPermission',
        'debugDomain' => '->checkDebugPermission'
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

    public function __construct()
    {
        parent::__construct();
        $this->logger = Injector::inst()->get(LoggerInterface::class);
    }

    /**
     * Check CSRF token for regular actions
     */
    public function checkCSRF(HTTPRequest $request = null): bool
    {
        if (!$request) {
            $request = $this->getRequest();
        }
        return SecurityToken::inst()->checkRequest($request);
    }

    /**
     * Custom security check for passkey actions
     * More lenient than CSRF but still secure for passkey flows
     */
    public function checkPasskeyCSRF(HTTPRequest $request = null): bool
    {
        if (!$request) {
            $request = $this->getRequest();
        }
        
        // 1. Must be POST request
        if (!$request->isPost()) {
            return false;
        }
        
        // 2. Must have proper Content-Type for API calls
        $contentType = $request->getHeader('Content-Type');
        if (!$contentType || !str_contains($contentType, 'application/json')) {
            return false;
        }
        
        // 3. Check for common CSRF attack patterns
        $referer = $request->getHeader('Referer');
        $origin = $request->getHeader('Origin');
        $host = $request->getHeader('Host');
        
        // Allow requests from same origin
        if ($origin) {
            $originHost = parse_url($origin, PHP_URL_HOST);
            if ($originHost && $originHost === $host) {
                return true;
            }
        }
        
        // Allow requests with valid referer from same domain
        if ($referer) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            if ($refererHost && $refererHost === $host) {
                return true;
            }
        }
        
        // For localhost development, be more lenient
        if ($this->isLocalhostDomain($host)) {
            return true;
        }
        
        // If we can't verify origin, fall back to CSRF token check
        return SecurityToken::inst()->checkRequest($request);
    }

    /**
     * Additional security validation for API requests
     */
    protected function validateRequestSecurity(HTTPRequest $request): bool
    {
        // 1. Check User-Agent (block obvious bots/crawlers)
        $userAgent = $request->getHeader('User-Agent');
        if (!$userAgent || $this->isSuspiciousUserAgent($userAgent)) {
            $this->logger->warning('Blocked suspicious User-Agent', [
                'user_agent' => $userAgent,
                'ip' => $request->getIP()
            ]);
            return false;
        }
        
        // 2. Check for required headers that legitimate browsers send
        $requiredHeaders = ['Accept', 'Accept-Language'];
        foreach ($requiredHeaders as $header) {
            if (!$request->getHeader($header)) {
                $this->logger->warning('Missing required header', [
                    'missing_header' => $header,
                    'ip' => $request->getIP()
                ]);
                return false;
            }
        }
        
        // 3. Check request body size (passkey requests should be reasonable size)
        $body = $request->getBody();
        if (strlen($body) > 10240) { // 10KB limit
            $this->logger->warning('Request body too large', [
                'body_size' => strlen($body),
                'ip' => $request->getIP()
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if User-Agent looks suspicious
     */
    protected function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/requests/i',
            '/postman/i',
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check debug permission
     */
    public function checkDebugPermission(HTTPRequest $request): bool
    {
        return Director::isDev() && Permission::check('ADMIN');
    }

    /**
     * Check rate limit for actions
     */
    protected function checkRateLimit(HTTPRequest $request, string $action, int $limit = 5, int $window = 3600): bool
    {
        $cache = Injector::inst()->get(CacheFactory::class)->create('passkey_rate_limit');
        $key = $action . '_' . $request->getIP();
        $attempts = $cache->get($key) ?: 0;
        
        if ($attempts >= $limit) {
            return false;
        }
        
        $cache->set($key, $attempts + 1, $window);
        return true;
    }

    /**
     * Add security headers to response
     */
    protected function addSecurityHeaders(HTTPResponse $response): void
    {
        $response->addHeader('X-Content-Type-Options', 'nosniff');
        $response->addHeader('X-Frame-Options', 'DENY');
        $response->addHeader('X-XSS-Protection', '1; mode=block');
        $response->addHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->addHeader('Content-Security-Policy', "default-src 'self'");
    }

    /**
     * Validate challenge expiry and session binding
     */
    protected function validateChallengeSession(HTTPRequest $request, array $challengeData): bool
    {
        if (!$challengeData || !isset($challengeData['expires'])) {
            return false;
        }
        
        if ($challengeData['expires'] < time()) {
            return false;
        }
        
        if (isset($challengeData['user_agent']) && 
            $challengeData['user_agent'] !== $request->getHeader('User-Agent')) {
            return false;
        }
        
        return true;
    }

    /**
     * Generate authentication challenge for passkey login
     * This is called from the JavaScript to initiate authentication
     */
    public function challenge(HTTPRequest $request)
    {
        if (!$request->isPost()) {
            return $this->httpError(405, 'Method not allowed');
        }

        // Additional security: Check for suspicious request patterns
        if (!$this->validateRequestSecurity($request)) {
            return $this->httpError(403, 'Forbidden');
        }

        // Check rate limiting
        if (!$this->checkRateLimit($request, 'challenge')) {
            return $this->httpError(429, 'Too many requests');
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
            
            // Store challenge in session for verification with expiry and session binding
            $challengeData = [
                'challenge' => $getArgsArray['challenge'],
                'expires' => time() + 300, // 5 minutes
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getIP()
            ];
            $request->getSession()->set('passkey_challenge_data', $challengeData);
            
            // Preserve BackURL if provided
            $backURL = $this->getCustomBackURL($request);
            if ($backURL) {
                $request->getSession()->set('BackURL', $backURL);
            }
            
            $response = new HTTPResponse();
            $this->addSecurityHeaders($response);
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode($getArgsArray));
            
            return $response;

        } catch (\Exception $e) {
            $this->logger->warning('Challenge generation failed', [
                'error' => $e->getMessage(),
                'ip' => $request->getIP()
            ]);
            
            $response = new HTTPResponse();
            $response->setStatusCode(500);
            $this->addSecurityHeaders($response);
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode(['error' => 'Failed to generate challenge']));
            
            return $response;
        }
    }

    public function registerBegin(HTTPRequest $request)
    {
        if (!$request->isPost()) {
            return $this->httpError(405, 'Method not allowed');
        }

        // Check rate limiting
        if (!$this->checkRateLimit($request, 'register')) {
            return $this->httpError(429, 'Too many registration attempts');
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
            
            // Validate the response structure
            if (!isset($createArgsArray['publicKey']['challenge'])) {
                $this->logger->error('Invalid registration challenge structure', [
                    'member_id' => $member->ID,
                    'available_keys' => isset($createArgsArray['publicKey']) ? 
                        array_keys($createArgsArray['publicKey']) : array_keys($createArgsArray)
                ]);
                throw new \Exception('Invalid registration challenge structure');
            }
            
            // Store challenge data with security binding
            $challengeData = [
                'challenge' => json_encode($createArgsArray),
                'user_id' => $member->ID,
                'expires' => time() + 300, // 5 minutes
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getIP()
            ];
            $request->getSession()->set('passkey_registration_data', $challengeData);

            $response = new HTTPResponse();
            $this->addSecurityHeaders($response);
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode($createArgsArray));
            
            return $response;

        } catch (\Exception $e) {
            $this->logger->warning('Registration begin failed', [
                'member_id' => isset($member) ? $member->ID : 'unknown',
                'error' => $e->getMessage(),
                'ip' => $request->getIP()
            ]);
            
            $response = new HTTPResponse();
            $response->setStatusCode(500);
            $this->addSecurityHeaders($response);
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode(['error' => 'Failed to begin registration']));
            
            return $response;
        }
    }

    public function registerFinish(HTTPRequest $request)
    {
        if (!$request->isPost()) {
            return $this->httpError(405, 'Method not allowed');
        }

        // Check rate limiting
        if (!$this->checkRateLimit($request, 'register_finish')) {
            return $this->httpError(429, 'Too many registration attempts');
        }

        try {
            $member = Security::getCurrentUser();
            if (!$member) {
                return $this->httpError(401, 'User must be logged in to register passkey');
            }

            // Validate session data
            $challengeData = $request->getSession()->get('passkey_registration_data');
            if (!$this->validateChallengeSession($request, $challengeData)) {
                throw new \Exception('Invalid or expired registration session');
            }

            if ($challengeData['user_id'] !== $member->ID) {
                throw new \Exception('Registration session user mismatch');
            }

            $registrationData = json_decode($request->getBody(), true);
            if (!$registrationData) {
                throw new \Exception('Invalid registration data format');
            }

            /** @var \GienieLab\PasskeyAuth\Service\PasskeyService $passkeyService */
            $passkeyService = Injector::inst()->get(PasskeyService::class);

            // Process registration
            $credential = $passkeyService->processRegistration(
                $registrationData, 
                $challengeData['challenge'], 
                $member
            );
            
            // Clear session data
            $request->getSession()->clear('passkey_registration_data');

            $response = new HTTPResponse();
            $this->addSecurityHeaders($response);
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode([
                'success' => true,
                'message' => 'Passkey registered successfully'
            ]));
            
            return $response;

        } catch (\Exception $e) {
            $this->logger->warning('Registration finish failed', [
                'member_id' => isset($member) ? $member->ID : 'unknown',
                'error' => $e->getMessage(),
                'ip' => $request->getIP()
            ]);
            
            $response = new HTTPResponse();
            $response->setStatusCode(500);
            $this->addSecurityHeaders($response);
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode(['error' => 'Registration failed']));
            
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

        // Check rate limiting
        if (!$this->checkRateLimit($request, 'login')) {
            return $this->httpError(429, 'Too many authentication attempts');
        }

        try {
            // Validate session data
            $challengeData = $request->getSession()->get('passkey_challenge_data');
            if (!$this->validateChallengeSession($request, $challengeData)) {
                throw new \Exception('Invalid or expired authentication session');
            }

            $authenticationData = json_decode($request->getBody(), true);
            if (!$authenticationData) {
                throw new \Exception('Invalid authentication data format');
            }

            /** @var \GienieLab\PasskeyAuth\Service\PasskeyService $passkeyService */
            $passkeyService = Injector::inst()->get(PasskeyService::class);
            
            // Verify authentication
            $member = $passkeyService->processAuthentication(
                $authenticationData, 
                $challengeData['challenge']
            );
            
            if (!$member) {
                throw new \Exception('Authentication verification failed');
            }
            
            // Log the user in using SilverStripe's Security class
            $identityStore = Injector::inst()->get(IdentityStore::class);
            $identityStore->logIn($member, true, $request);
            
            // Clear challenge data
            $request->getSession()->clear('passkey_challenge_data');

            // Determine redirect URL with BackURL support
            $backURL = $this->getCustomBackURL($request);
            $redirectURL = $backURL ?: Security::config()->get('default_login_dest') ?: Director::baseURL();
            
            // Clear BackURL from session to prevent reuse
            $request->getSession()->clear('BackURL');

            $response = new HTTPResponse();
            $this->addSecurityHeaders($response);
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode([
                'success' => true,
                'message' => 'Authentication successful',
                'redirectURL' => $redirectURL
            ]));
            
            return $response;

        } catch (\Exception $e) {
            $this->logger->warning('Login finish failed', [
                'error' => $e->getMessage(),
                'ip' => $request->getIP()
            ]);
            
            $response = new HTTPResponse();
            $response->setStatusCode(401);
            $this->addSecurityHeaders($response);
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode(['error' => 'Authentication failed']));
            
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
        // Rate limiting for debug endpoint
        if (!$this->checkRateLimit($request, 'debug', 3, 300)) {
            return $this->httpError(429, 'Too many debug requests');
        }

        $passkeyService = Injector::inst()->get(PasskeyService::class);
         
        $debugInfo = [
            'environment' => [
                'mode' => Director::get_environment_type(),
                'is_dev' => Director::isDev(),
                'is_https' => Director::is_https(),
                'current_domain' => $request->getHeader('Host'),
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

        // Log debug access
        $this->logger->info('Debug config accessed', [
            'ip' => $request->getIP(),
            'user_agent' => $request->getHeader('User-Agent')
        ]);

        $response = HTTPResponse::create(json_encode($debugInfo, JSON_PRETTY_PRINT));
        $this->addSecurityHeaders($response);
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
        // Rate limiting for debug endpoint
        if (!$this->checkRateLimit($request, 'debug_domain', 3, 300)) {
            return $this->httpError(429, 'Too many debug requests');
        }

        $passkeyService = Injector::inst()->get(PasskeyService::class);
        
        // Sanitized domain info (remove sensitive server details)
        $domainInfo = [
            'request_headers' => [
                'Host' => $request->getHeader('Host'),
                'Origin' => $request->getHeader('Origin'),
            ],
            'silverstripe_detection' => [
                'Director::is_https()' => Director::is_https(),
            ],
            'passkey_service' => [
                'getCurrentDomain()' => $passkeyService->getCurrentDomain(),
                'configured_rp_id' => $passkeyService->config()->get('rp_id'),
            ]
        ];

        // Log debug access
        $this->logger->info('Debug domain accessed', [
            'ip' => $request->getIP(),
            'user_agent' => $request->getHeader('User-Agent')
        ]);

        $response = HTTPResponse::create(json_encode($domainInfo, JSON_PRETTY_PRINT));
        $this->addSecurityHeaders($response);
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }
}
