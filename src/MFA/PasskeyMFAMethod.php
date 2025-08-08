<?php

namespace GienieLab\PasskeyAuth\MFA;

// Only define this class if the MFA module is available
if (!interface_exists('SilverStripe\MFA\Method\MethodInterface')) {
    return;
}

use SilverStripe\MFA\Method\MethodInterface;
use SilverStripe\MFA\Model\RegisteredMethod;
use SilverStripe\Security\Member;
use GienieLab\PasskeyAuth\Service\PasskeyService;

/**
 * Passkey integration with SilverStripe MFA module
 * This allows passkeys to work as an MFA method alongside TOTP, etc.
 * 
 * This class is only loaded when silverstripe/mfa module is installed.
 */
class PasskeyMFAMethod implements MethodInterface
{
    private static $service_dependencies = [
        'PasskeyService' => PasskeyService::class,
    ];

    /**
     * @var PasskeyService
     */
    protected $passkeyService;

    public function getURLSegment(): string
    {
        return 'passkey';
    }

    public function getName(): string
    {
        return 'Passkey Authentication';
    }

    public function getDescription(): string
    {
        return 'Use your device\'s biometric sensor, security key, or device authentication as a second factor.';
    }

    public function getSupportLink(): string
    {
        return '/passkey-auth/help';
    }

    public function getIcon(): string
    {
        return 'gienielab/silverstripe-passkey-auth:client/dist/images/passkey-icon.svg';
    }

    public function isAvailable(): bool
    {
        // Check if WebAuthn is supported (client-side check needed)
        return true; // Server-side always available
    }

    public function getRegisterHandler(): ?string
    {
        return PasskeyMFARegisterHandler::class;
    }

    public function getVerifyHandler(): ?string
    {
        return PasskeyMFAVerifyHandler::class;
    }

    public function getRemoveHandler(): ?string
    {
        return PasskeyMFARemoveHandler::class;
    }

    /**
     * Check if user has passkeys registered for MFA
     */
    public function isRegistered(Member $member): bool
    {
        return $this->passkeyService->hasRegisteredCredentials($member);
    }

    /**
     * Get backup tokens (passkeys don't use backup tokens)
     */
    public function getBackupTokens(RegisteredMethod $method): array
    {
        return []; // Passkeys are the backup method
    }

    /**
     * Priority for MFA method selection
     */
    public function getPriority(): int
    {
        return 100; // High priority - passkeys are very secure
    }
}

/**
 * MFA Registration Handler for Passkeys
 */
class PasskeyMFARegisterHandler
{
    use Injectable;

    private static $service_dependencies = [
        'PasskeyService' => PasskeyService::class,
    ];

    protected $passkeyService;

    public function start(Member $member): array
    {
        // Generate registration options for MFA context
        return $this->passkeyService->generateRegistrationOptions($member, [
            'user_verification' => 'required', // Stricter for MFA
            'authenticator_attachment' => 'cross-platform' // Allow security keys
        ]);
    }

    public function complete(Member $member, array $data): RegisteredMethod
    {
        // Complete registration and create MFA record
        $credential = $this->passkeyService->processRegistration($member, $data);
        
        return RegisteredMethod::create([
            'MemberID' => $member->ID,
            'MethodClassName' => PasskeyMFAMethod::class,
            'Name' => 'Passkey MFA',
            'Data' => json_encode(['credential_id' => $credential->ID])
        ]);
    }
}

/**
 * MFA Verification Handler for Passkeys
 */
class PasskeyMFAVerifyHandler
{
    use Injectable;

    private static $service_dependencies = [
        'PasskeyService' => PasskeyService::class,
    ];

    protected $passkeyService;

    public function start(Member $member): array
    {
        // Generate authentication challenge for MFA verification
        return $this->passkeyService->generateAuthenticationChallenge($member, [
            'user_verification' => 'required' // Require user verification for MFA
        ]);
    }

    public function verify(Member $member, array $data): bool
    {
        // Verify the passkey authentication for MFA step
        try {
            $result = $this->passkeyService->processAuthentication($member, $data);
            return $result['success'] ?? false;
        } catch (Exception $e) {
            return false;
        }
    }
}
