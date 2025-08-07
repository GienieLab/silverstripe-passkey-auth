<?php

namespace GienieLab\PasskeyAuth\Service;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
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
    use Injectable, Configurable;

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

    public function __construct()
    {
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
        
        // Create ceremony step manager factory
        $ceremonyStepManagerFactory = new CeremonyStepManagerFactory();
        $ceremonyStepManagerFactory->setAlgorithmManager($this->algorithmManager);
        
        // Create validators with different ceremony managers
        $this->attestationValidator = AuthenticatorAttestationResponseValidator::create(
            $ceremonyStepManagerFactory->creationCeremony()
        );
        $this->assertionValidator = AuthenticatorAssertionResponseValidator::create(
            $ceremonyStepManagerFactory->requestCeremony()
        );
        
        // Create attestation object loader
        $attestationStatementSupportManager = AttestationStatementSupportManager::create();
        $this->attestationObjectLoader = AttestationObjectLoader::create($attestationStatementSupportManager);
        
        // Create authenticator data loader
        $this->authenticatorDataLoader = AuthenticatorDataLoader::create();
    }

    /**
     * Get the RP Entity, creating it if needed
     */
    protected function getRpEntity()
    {
        if (!$this->rpEntity) {
            $rpName = $this->config()->get('rp_name');
            $rpId = $this->config()->get('rp_id');
            
            // Use configured RP ID or fall back to current domain
            if (!$rpId) {
                $rpId = $this->getCurrentDomain();
            }
        
            
            // Create RP Entity
            $this->rpEntity = new PublicKeyCredentialRpEntity($rpName, $rpId);
        }
        
        return $this->rpEntity;
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
            // Decode the stored challenge
            $challengeData = json_decode($challenge, true);
            $originalChallenge = base64_decode($challengeData['publicKey']['challenge']);
            
            // Create the collected client data object from the base64url-encoded JSON
            // Convert standard base64 to base64url (remove padding, replace + with -, replace / with _)
            $clientDataBase64Url = rtrim(strtr($registrationData['response']['clientDataJSON'], '+/', '-_'), '=');
            $collectedClientData = CollectedClientData::createFormJson($clientDataBase64Url);
            
            // Create the attestation object from the base64-encoded attestation object
            $attestationObject = $this->attestationObjectLoader->load($registrationData['response']['attestationObject']);
            
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
            
            // Debug: Check public key data
            error_log('Registration Debug - Public Key type: ' . gettype($publicKeyCredentialSource->credentialPublicKey));
            error_log('Registration Debug - Public Key length: ' . strlen($publicKeyCredentialSource->credentialPublicKey));
            error_log('Registration Debug - Public Key (first 50 chars): ' . substr($publicKeyCredentialSource->credentialPublicKey, 0, 50));
            
            // Store public key as base64 to avoid binary data issues
            $credential->PublicKey = base64_encode($publicKeyCredentialSource->credentialPublicKey);
            $credential->AAGUID = $publicKeyCredentialSource->aaguid ? $publicKeyCredentialSource->aaguid->toString() : '';
            $credential->SignCount = $publicKeyCredentialSource->counter;
            $credential->MemberID = $member->ID;
            $credential->write();

            return $credential;

        } catch (\Throwable $ex) {
            error_log('Registration processing error: ' . $ex->getMessage());
            error_log('Stack trace: ' . $ex->getTraceAsString());
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
                return [
                    'type' => $cred->type,
                    'id' => base64_encode($cred->id),
                ];
            }, $requestOptions->allowCredentials ?? []),
            'userVerification' => $requestOptions->userVerification,
            'timeout' => $requestOptions->timeout,
        ];
    }

    /**
     * Process authentication response
     */
    public function processAuthentication($authenticationData, $challenge)
    {
        try {
            // Decode challenge
            $originalChallenge = base64_decode($challenge);
            
            // Find the credential
            $credentialId = base64_encode(base64_decode($authenticationData['id']));
            $credential = PasskeyCredential::get()->filter('CredentialID', $credentialId)->first();
            
            if (!$credential) {
                throw new \Exception('Credential not found');
            }
            
            // Debug logging for credential data
            error_log('Authentication Debug - Credential ID: ' . $credential->CredentialID);
            error_log('Authentication Debug - Public Key length: ' . strlen($credential->PublicKey));
            error_log('Authentication Debug - AAGUID: ' . $credential->AAGUID);
            error_log('Authentication Debug - Sign Count: ' . $credential->SignCount);
            
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
            
            // Create the collected client data object from the base64url-encoded JSON
            // Convert standard base64 to base64url (remove padding, replace + with -, replace / with _)
            $clientDataBase64Url = rtrim(strtr($authenticationData['response']['clientDataJSON'], '+/', '-_'), '=');
            $collectedClientData = CollectedClientData::createFormJson($clientDataBase64Url);
            
            // Create the authenticator data object from the raw data
            $authenticatorDataRaw = base64_decode($authenticationData['response']['authenticatorData']);
            $authenticatorData = $this->authenticatorDataLoader->load($authenticatorDataRaw);
            
            // Create assertion response
            $assertionResponse = new AuthenticatorAssertionResponse(
                $collectedClientData,
                $authenticatorData,
                base64_decode($authenticationData['response']['signature']),
                isset($authenticationData['response']['userHandle']) ? 
                    base64_decode($authenticationData['response']['userHandle']) : null
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
            
            error_log("PasskeyService: Updated credential usage - ID: {$credential->CredentialID}, LastUsed: {$credential->LastUsed}, SignCount: {$credential->SignCount}");
            
            return $credential->Member();
            
        } catch (\Throwable $ex) {
            error_log('Authentication verification error: ' . $ex->getMessage());
            error_log('Stack trace: ' . $ex->getTraceAsString());
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
