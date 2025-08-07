<?php

namespace GienieLab\PasskeyAuth\Extension;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\LiteralField;
use GienieLab\PasskeyAuth\Model\PasskeyCredential;

class MemberPasskeyExtension extends DataExtension
{
    private static $has_many = [
        'PasskeyCredentials' => PasskeyCredential::class,
    ];

    /**
     * Update CMS fields to show passkey credentials
     */
    public function updateCMSFields(FieldList $fields)
    {
        // Add passkey credentials management
        if ($this->owner->ID) {
            $config = GridFieldConfig_RecordEditor::create();
            
            // Remove add new button (passkeys should only be created through registration)
            $config->removeComponentsByType(GridFieldAddNewButton::class);
            
            // Keep delete functionality so users can remove old passkeys
            $config->addComponent(new GridFieldDeleteAction());
            
            $passkeysGrid = GridField::create(
                'PasskeyCredentials',
                'Passkey Credentials',
                $this->owner->PasskeyCredentials(),
                $config
            );

            $fields->addFieldToTab('Root.Security', $passkeysGrid);

            // Add some info about passkeys
            $passkeyInfo = LiteralField::create(
                'PasskeyInfo',
                '<div class="alert alert-info">
                    <strong>Passkey Credentials</strong><br>
                    These are the passkey credentials registered for this user. 
                    Users can register new passkeys through the login page when logged in.
                    <br><br>
                    <strong>Management:</strong> You can delete outdated or compromised passkeys here. 
                    Users can register new passkeys anytime through the authentication flow.
                </div>'
            );

            $fields->insertBefore('PasskeyCredentials', $passkeyInfo);
        }
    }

    /**
     * Get all passkey credentials for this member
     */
    public function getPasskeyCredentials()
    {
        return PasskeyCredential::get()->filter('MemberID', $this->owner->ID);
    }

    /**
     * Check if this member has any passkey credentials
     */
    public function hasPasskeyCredentials()
    {
        return $this->getPasskeyCredentials()->count() > 0;
    }

    /**
     * Add a new passkey credential for this member
     */
    public function addPasskeyCredential($credentialData)
    {
        $credential = PasskeyCredential::create();
        $credential->CredentialID = $credentialData['credentialId'];
        $credential->PublicKey = $credentialData['publicKey'];
        $credential->AAGUID = $credentialData['aaguid'] ?? '';
        $credential->SignCount = $credentialData['signCount'] ?? 0;
        $credential->MemberID = $this->owner->ID;
        
        return $credential->write();
    }

    /**
     * Remove a passkey credential
     */
    public function removePasskeyCredential($credentialId)
    {
        $credential = PasskeyCredential::get()
            ->filter([
                'CredentialID' => $credentialId,
                'MemberID' => $this->owner->ID
            ])
            ->first();

        if ($credential) {
            return $credential->delete();
        }

        return false;
    }
}
