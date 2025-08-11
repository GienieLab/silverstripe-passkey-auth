<?php

namespace GienieLab\PasskeyAuth\Service;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;
use GienieLab\PasskeyAuth\Model\PasskeyCredential;

// web-auth/webauthn-lib imports
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\AuthenticatorAttachment;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\CollectedClientData;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticatorDataLoader;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\TrustPath\EmptyTrustPath;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use Symfony\Component\Uid\Uuid;

class PasskeyService
{
    use Injectable, Configurable, Extensible;

    /**
     * @var PublicKeyCredentialRpEntity
     */
    protected $rpEntity;

    /**
     * @var Manager
     */
    protected $algorithmManager;

    /**
     * @var AuthenticatorAttestationResponseValidator
     */
    protected $attestationValidator;

    /**
     * @var AuthenticatorAssertionResponseValidator
     */
    protected $assertionValidator;

    /**
     * @var AttestationObjectLoader
     */
    protected $attestationObjectLoader;

    /**
     * @var AuthenticatorDataLoader
     */
    protected $authenticatorDataLoader;

    /**
     * @config
     * @var string
     */
    private static $rp_name = 'Gienie Creative Agency';

    /**
     * @config
     * @var string|null
     */
    private static $rp_id = null;

    /**
     * @config
     * @var int
     */
    private static $timeout = 60;

    /**
     * @config
     * @var bool
     */
    private static $require_user_verification = true;

    /**
     * @config
     * @var bool
     */
    private static $require_user_presence = true;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct()
    {
        // Initialize logger
        $this->logger = Injector::inst()->get(LoggerInterface::class);
        
        // Get configuration values
        $rpName = $this->config()->get('rp_name');
        
        // Create algorithm manager with supported algorithms
        $this->algorithmManager = new Manager();
        $this->algorithmManager->add(new ECDSA\ES256());
        $this->algorithmManager->add(new ECDSA\ES384());
        $this->algorithmManager->add(new ECDSA\ES512());
        $this->algorithmManager->add(new EdDSA\EdDSA());
        $this->algorithmManager->add(new RSA\RS256());
        $this->algorithmManager->add(new RSA\RS384());
        $this->algorithmManager->add(new RSA\RS512());
        $this->algorithmManager->add(new RSA\PS256());
        $this->algorithmManager->add(new RSA\PS384());
        $this->algorithmManager->add(new RSA\PS512());
        
        // Create attestation statement support manager
        $attestationStatementSupportManager = AttestationStatementSupportManager::create();
        
        // Create ceremony step manager factory
        $ceremonyStepManagerFactory = new CeremonyStepManagerFactory();
        $ceremonyStepManagerFactory->setAlgorithmManager($this->algorithmManager);
        
        // Create validators with correct parameters
        $this->attestationValidator = AuthenticatorAttestationResponseValidator::create(
            $attestationStatementSupportManager
        );
        $this->assertionValidator = AuthenticatorAssertionResponseValidator::create();
        
        // Create attestation object loader
        $this->attestationObjectLoader = AttestationObjectLoader::create($attestationStatementSupportManager);
        
        // Create authenticator data loader
        $this->authenticatorDataLoader = AuthenticatorDataLoader::create();
    }

    /**
     * Get the RP Entity, creating it if needed
     * Supports dynamic subsite configuration when subsites module is available
     */
    protected function getRpEntity()
    {
        if (!$this->rpEntity) {
            // Try to get dynamic subsite-specific values first
            $rpName = $this->getDynamicRpName();
            $rpId = $this->getDynamicRpId();
            
            // Create RP Entity
            $this->rpEntity = new PublicKeyCredentialRpEntity($rpName, $rpId);
        }
        
        return $this->rpEntity;
    }

    /**
     * Get RP Name dynamically, supporting host-based and subsite configuration
     */
    protected function getDynamicRpName(): string
    {
        // Check if host-based extension is available (preferred)
        if ($this->hasExtension('PasskeyHostExtension')) {
            return $this->getHostBasedRpName();
        }
        
        // Check if subsites extension is available (legacy)
        if ($this->hasExtension('SubsitePasskeyExtension')) {
            return $this->getSubsiteRpName();
        }
        
        // Fall back to config
        $rpName = $this->config()->get('rp_name');
        return $rpName ?: 'SilverStripe Site';
    }

    /**
     * Get RP ID dynamically, supporting host-based and subsite configuration
     */
    protected function getDynamicRpId(): string
    {
        // Check if host-based extension is available (preferred)
        if ($this->hasExtension('PasskeyHostExtension')) {
            $dynamicRpId = $this->getHostBasedRpId();
            if ($dynamicRpId) {
                return $dynamicRpId;
            }
        }
        
        // Check if subsites extension is available (legacy)
        if ($this->hasExtension('SubsitePasskeyExtension')) {
            $dynamicRpId = $this->getSubsiteRpId();
            if ($dynamicRpId) {
                return $dynamicRpId;
            }
        }
        
        // Fall back to config or current domain
        $rpId = $this->config()->get('rp_id');
        if (!$rpId) {
            $rpId = $this->getCurrentDomain();
        }
        
        return $rpId;
    }

    /**
     * Clear the RP entity cache (useful when switching between subsites)
     */
    public function clearRpEntityCache(): void
    {
        $this->rpEntity = null;
    }

    /**
     * Force refresh of RP entity with current subsite context
     */
    public function refreshRpEntity(): PublicKeyCredentialRpEntity
    {
        $this->clearRpEntityCache();
        return $this->getRpEntity();
    }

    /**
     * Validate registration data structure
     */
    private function validateRegistrationData($data): bool
    {
        if (!is_array($data)) {
            return false;
        }
        
        $required = ['response', 'id', 'type'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        if (!is_array($data['response'])) {
            return false;
        }
        
        $responseRequired = ['clientDataJSON', 'attestationObject'];
        foreach ($responseRequired as $field) {
            if (!isset($data['response'][$field])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Validate challenge format
     */
    private function validateChallenge($challenge): bool
    {
        if (empty($challenge)) {
            return false;
        }
        
        $challengeData = json_decode($challenge, true);
        if (!$challengeData || !isset($challengeData['publicKey']['challenge'])) {
            return false;
        }
        
        return true;
    }

    /**
     * Generate registration challenge for new passkey
     */
    public function generateRegistrationChallenge($member)
    {
        // Create user entity
        $userId = hash('sha256', $member->ID, true);
        $userEntity = new PublicKeyCredentialUserEntity(
            $member->Email,
            $userId,
            $member->getTitle()
        );

        // Get existing credentials to exclude
        $excludeCredentials = [];
        $existingCredentials = PasskeyCredential::get()->filter('MemberID', $member->ID);
        
        foreach ($existingCredentials as $credential) {
            $excludeCredentials[] = new PublicKeyCredentialDescriptor(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                base64_decode($credential->CredentialID)
            );
        }

        // Create authenticator selection criteria
        $authenticatorSelection = new AuthenticatorSelectionCriteria(
            null, // authenticatorAttachment - allow any
            $this->config()->get('require_user_verification') ? 
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED : 
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED
        );

        // Create credential parameters (supported algorithms)
        $credentialParameters = [
            new PublicKeyCredentialParameters(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, -7),  // ES256
            new PublicKeyCredentialParameters(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, -35), // ES384
            new PublicKeyCredentialParameters(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, -36), // ES512
            new PublicKeyCredentialParameters(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, -8),  // EdDSA
            new PublicKeyCredentialParameters(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, -257), // RS256
        ];

        // Generate challenge
        $challenge = random_bytes(32);

        // Create creation options manually
        $creationOptions = new PublicKeyCredentialCreationOptions(
            $this->getRpEntity(),
            $userEntity,
            $challenge,
            $credentialParameters,
            $authenticatorSelection,
            null, // attestation
            $excludeCredentials,
            $this->config()->get('timeout') * 1000 // Convert to milliseconds
        );

        // Convert to array for JSON response
        $result = [
            'publicKey' => [
                'challenge' => base64_encode($creationOptions->challenge),
                'rp' => [
                    'name' => $creationOptions->rp->name,
                    'id' => $creationOptions->rp->id,
                ],
                'user' => [
                    'id' => base64_encode($creationOptions->user->id),
                    'name' => $creationOptions->user->name,
                    'displayName' => $creationOptions->user->displayName,
                ],
                'pubKeyCredParams' => array_map(function($param) {
                    return [
                        'type' => $param->type,
                        'alg' => $param->alg,
                    ];
                }, $creationOptions->pubKeyCredParams),
                'authenticatorSelection' => [
                    'residentKey' => $creationOptions->authenticatorSelection?->residentKey,
                    'userVerification' => $creationOptions->authenticatorSelection?->userVerification,
                ],
                'timeout' => $creationOptions->timeout,
                'excludeCredentials' => array_map(function($cred) {
                    return [
                        'type' => $cred->type,
                        'id' => base64_encode($cred->id),
                    ];
                }, $creationOptions->excludeCredentials ?? []),
            ]
        ];
        
        return $result;
    }

    /**
     * Process registration response and store credential
     */
    public function processRegistration($registrationData, $challenge, $member)
    {
        try {
            // Validate input structure
            if (!$this->validateRegistrationData($registrationData)) {
                throw new \InvalidArgumentException('Invalid registration data structure');
            }
            
            if (!$this->validateChallenge($challenge)) {
                throw new \InvalidArgumentException('Invalid challenge format');
            }

            // Decode the stored challenge
            $challengeData = json_decode($challenge, true);
            $originalChallenge = base64_decode($challengeData['publicKey']['challenge']);
            
            // Handle clientDataJSON - convert from array format to base64url string
            if (is_array($registrationData['response']['clientDataJSON'])) {
                // Convert array back to binary data
                $clientDataBinary = pack('C*', ...$registrationData['response']['clientDataJSON']);
                // Convert to base64url format (remove padding, replace + with -, replace / with _)
                $clientDataBase64Url = rtrim(strtr(base64_encode($clientDataBinary), '+/', '-_'), '=');
            } else {
                // Already in string format
                $clientDataBase64Url = rtrim(strtr($registrationData['response']['clientDataJSON'], '+/', '-_'), '=');
            }
            $collectedClientData = CollectedClientData::createFormJson($clientDataBase64Url);
            
            // Handle attestationObject - convert from array format to binary if needed
            if (is_array($registrationData['response']['attestationObject'])) {
                $attestationObjectBinary = pack('C*', ...$registrationData['response']['attestationObject']);
                $attestationObjectBase64 = base64_encode($attestationObjectBinary);
            } else {
                $attestationObjectBase64 = $registrationData['response']['attestationObject'];
            }
            $attestationObject = $this->attestationObjectLoader->load($attestationObjectBase64);
            
            // Create the attestation response from the client data
            $attestationResponse = new AuthenticatorAttestationResponse(
                $collectedClientData,
                $attestationObject
            );
            
            // Create user entity for verification
            $userId = hash('sha256', $member->ID, true);
            $userEntity = new PublicKeyCredentialUserEntity(
                $member->Email,
                $userId,
                $member->getTitle()
            );
            
            // Create the creation options that were used
            $creationOptions = new PublicKeyCredentialCreationOptions(
                $this->getRpEntity(),
                $userEntity,
                $originalChallenge,
                [new PublicKeyCredentialParameters(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, -7)]
            );
            
            // Verify and process the attestation
            $publicKeyCredentialSource = $this->attestationValidator->check(
                $attestationResponse,
                $creationOptions,
                $_SERVER['HTTP_HOST'] ?? parse_url(Director::absoluteBaseURL(), PHP_URL_HOST)
            );
            
            // Store the credential in our database
            $credential = PasskeyCredential::create();
            $credential->CredentialID = base64_encode($publicKeyCredentialSource->publicKeyCredentialId);
            
            // Store public key as base64 to avoid binary data issues
            $credential->PublicKey = base64_encode($publicKeyCredentialSource->credentialPublicKey);
            $credential->AAGUID = $publicKeyCredentialSource->aaguid ? $publicKeyCredentialSource->aaguid->toString() : '';
            $credential->SignCount = $publicKeyCredentialSource->counter;
            $credential->MemberID = $member->ID;
            $credential->write();

            // Log successful registration
            $this->logger->info('Successful passkey registration', [
                'member_id' => $member->ID,
                'credential_id' => substr($credential->CredentialID, 0, 8) . '...',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            return $credential;

        } catch (\Throwable $ex) {
            // Log failed registration attempt
            $this->logger->warning('Failed passkey registration attempt', [
                'member_id' => $member ? $member->ID : 'unknown',
                'error' => $ex->getMessage(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            throw new \Exception('Registration failed: ' . $ex->getMessage());
        }
    }

    /**
     * Generate authentication challenge
     */
    public function generateAuthenticationChallenge($credentialIds = null)
    {
        $allowCredentials = [];
        
        if ($credentialIds === null) {
            // Get all credentials if none specified
            $credentials = PasskeyCredential::get();
            foreach ($credentials as $credential) {
                $allowCredentials[] = new PublicKeyCredentialDescriptor(
                    PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                    base64_decode($credential->CredentialID)
                );
            }
        } else {
            foreach ($credentialIds as $credentialId) {
                $allowCredentials[] = new PublicKeyCredentialDescriptor(
                    PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                    base64_decode($credentialId)
                );
            }
        }

        // Generate challenge
        $challenge = random_bytes(32);

        // Create request options
        $requestOptions = new PublicKeyCredentialRequestOptions(
            $challenge,
            $this->getRpEntity()->id,
            $allowCredentials,
            $this->config()->get('require_user_verification') ? 
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED : 
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            $this->config()->get('timeout') * 1000
        );

        // Convert to array for JSON response
        return [
            'challenge' => base64_encode($requestOptions->challenge),
            'allowCredentials' => array_map(function($cred) {
                // Convert to base64url format (WebAuthn standard)
                $base64 = base64_encode($cred->id);
                $base64url = rtrim(strtr($base64, '+/', '-_'), '=');
                return [
                    'type' => $cred->type,
                    'id' => $base64url,
                ];
            }, $requestOptions->allowCredentials ?? []),
            'userVerification' => $requestOptions->userVerification,
            'timeout' => $requestOptions->timeout,
        ];
    }

    /**
     * Validate authentication data structure
     */
    private function validateAuthenticationData($data): bool
    {
        if (!is_array($data)) {
            return false;
        }
        
        $required = ['response', 'id'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        if (!is_array($data['response'])) {
            return false;
        }
        
        $responseRequired = ['clientDataJSON', 'authenticatorData', 'signature'];
        foreach ($responseRequired as $field) {
            if (!isset($data['response'][$field])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Process authentication response
     */
    public function processAuthentication($authenticationData, $challenge)
    {
        try {
            // Validate input structure
            if (!$this->validateAuthenticationData($authenticationData)) {
                throw new \InvalidArgumentException('Invalid authentication data structure');
            }
            
            if (empty($challenge)) {
                throw new \InvalidArgumentException('Challenge cannot be empty');
            }

            // Decode challenge
            $originalChallenge = base64_decode($challenge);
            
            // Handle credential ID - convert from base64url (from WebAuthn) to base64 (for database lookup)
            $credentialIdBase64Url = $authenticationData['id']; // From WebAuthn API (base64url format)
            
            // Convert base64url to base64 by adding padding and replacing characters
            $credentialIdBase64 = str_pad(strtr($credentialIdBase64Url, '-_', '+/'), strlen($credentialIdBase64Url) % 4, '=', STR_PAD_RIGHT);
            
            // First try to find by base64url format (in case it was stored that way)
            $credential = PasskeyCredential::get()->filter('CredentialID', $credentialIdBase64Url)->first();
            
            // If not found, try base64 format
            if (!$credential) {
                $credential = PasskeyCredential::get()->filter('CredentialID', $credentialIdBase64)->first();
            }
            
            
            if (!$credential) {
                // Try to decode and re-encode to find the credential
                $rawCredentialId = base64_decode($credentialIdBase64);
                $credentialIdAlternative = base64_encode($rawCredentialId);
                $credential = PasskeyCredential::get()->filter('CredentialID', $credentialIdAlternative)->first();
                
                if (!$credential) {
                    $this->logger->warning('Authentication attempt with unknown credential', [
                        'credential_id' => substr($credentialIdBase64Url, 0, 8) . '...',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    throw new \Exception('Credential not found');
                }
            }
            
            
            // Validate public key data
            if (empty($credential->PublicKey)) {
                throw new \Exception('Credential public key is empty');
            }
            
            // Create PublicKeyCredentialSource object
            $publicKeyCredentialSource = new PublicKeyCredentialSource(
                base64_decode($credential->CredentialID),
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                [], // transports
                'none', // attestationType
                new EmptyTrustPath(), // trustPath
                $credential->AAGUID ? Uuid::fromString($credential->AAGUID) : Uuid::v4(),
                base64_decode($credential->PublicKey), // Decode the base64-stored public key
                hash('sha256', $credential->MemberID, true), // userHandle
                $credential->SignCount
            );
            
            // Handle clientDataJSON - convert from array format to base64url string
            if (is_array($authenticationData['response']['clientDataJSON'])) {
                // Convert array back to binary data
                $clientDataBinary = pack('C*', ...$authenticationData['response']['clientDataJSON']);
                // Convert to base64url format (remove padding, replace + with -, replace / with _)
                $clientDataBase64Url = rtrim(strtr(base64_encode($clientDataBinary), '+/', '-_'), '=');
            } else {
                // Already in string format
                $clientDataBase64Url = rtrim(strtr($authenticationData['response']['clientDataJSON'], '+/', '-_'), '=');
            }
            $collectedClientData = CollectedClientData::createFormJson($clientDataBase64Url);
            
            // Handle authenticatorData - convert from array format to binary
            if (is_array($authenticationData['response']['authenticatorData'])) {
                $authenticatorDataRaw = pack('C*', ...$authenticationData['response']['authenticatorData']);
            } else {
                $authenticatorDataRaw = base64_decode($authenticationData['response']['authenticatorData']);
            }
            $authenticatorData = $this->authenticatorDataLoader->load($authenticatorDataRaw);
            
            // Handle signature - convert from array format to binary
            if (is_array($authenticationData['response']['signature'])) {
                $signature = pack('C*', ...$authenticationData['response']['signature']);
            } else {
                $signature = base64_decode($authenticationData['response']['signature']);
            }
            
            // Handle userHandle - convert from array format to binary if present
            $userHandle = null;
            if (isset($authenticationData['response']['userHandle']) && $authenticationData['response']['userHandle']) {
                if (is_array($authenticationData['response']['userHandle'])) {
                    $userHandle = pack('C*', ...$authenticationData['response']['userHandle']);
                } else {
                    $userHandle = base64_decode($authenticationData['response']['userHandle']);
                }
            }
            
            // Create assertion response
            $assertionResponse = new AuthenticatorAssertionResponse(
                $collectedClientData,
                $authenticatorData,
                $signature,
                $userHandle
            );
            
            // Create request options
            $requestOptions = new PublicKeyCredentialRequestOptions(
                $originalChallenge,
                $this->getRpEntity()->id,
                [],
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
                $this->config()->get('timeout') * 1000
            );
            
            // Verify the assertion
            $updatedSource = $this->assertionValidator->check(
                $publicKeyCredentialSource,
                $assertionResponse,
                $requestOptions,
                $_SERVER['HTTP_HOST'] ?? parse_url(Director::absoluteBaseURL(), PHP_URL_HOST),
                null
            );
            
            // Update counter, last used timestamp, and user agent in database
            $userAgent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
            $credential->updateUsage($updatedSource->counter, $userAgent);
            
            // Log successful authentication
            $this->logger->info('Successful passkey authentication', [
                'member_id' => $credential->Member()->ID,
                'credential_id' => substr($credential->CredentialID, 0, 8) . '...',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return $credential->Member();
            
        } catch (\Throwable $ex) {
            // Log failed authentication attempt
            $this->logger->warning('Failed passkey authentication attempt', [
                'error' => $ex->getMessage(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            throw new \Exception('Authentication failed: ' . $ex->getMessage());
        }
    }

    /**
     * Get all credentials for a user
     */
    public function getCredentialsForUser($member)
    {
        $credentials = PasskeyCredential::get()->filter('MemberID', $member->ID);
        $result = [];
        
        foreach ($credentials as $credential) {
            $result[] = [
                'id' => base64_decode($credential->CredentialID),
                'type' => 'public-key'
            ];
        }
        
        return $result;
    }

    /**
     * Get all credentials for authentication challenge
     */
    public function getAllCredentials()
    {
        $credentials = PasskeyCredential::get();
        $result = [];
        
        foreach ($credentials as $credential) {
            $result[] = [
                'id' => base64_decode($credential->CredentialID),
                'type' => 'public-key'
            ];
        }
        
        return $result;
    }

    /**
     * Find a member by credential ID
     */
    public function findMemberByCredential($credentialId)
    {
        $credential = PasskeyCredential::get()->filter('CredentialID', $credentialId)->first();
        
        return $credential ? $credential->Member() : null;
    }

    /**
     * Get the current domain for RP ID
     * Uses HTTP_HOST from the current request for maximum accuracy
     */
    public function getCurrentDomain()
    {
        // Try HTTP_HOST first (most accurate for current request)
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            // Remove port if present
            $host = explode(':', $host)[0];
            return $host;
        }
        
        // Fall back to Director's base URL
        $baseUrl = Director::absoluteBaseURL();
        $parsed = parse_url($baseUrl);
        return $parsed['host'] ?? 'localhost';
    }
}
