<?php

namespace GienieLab\PasskeyAuth\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;
use SilverStripe\Security\Security;

class PageControllerExtension extends Extension
{
    public function onAfterInit()
    {
        // Only include assets on Security pages (login/logout)
        if ($this->owner instanceof Security || 
            strpos($this->owner->getRequest()->getURL(), 'Security') !== false ||
            strpos($this->owner->getRequest()->getURL(), 'passkey-auth') !== false) {
            
            Requirements::javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-auth.js');
            Requirements::css('gienielab/silverstripe-passkey-auth:client/dist/css/styles.css');
        }
    }
    
    /**
     * Check if passkey registration prompt should be shown
     */
    public function getShowPasskeyRegistrationPrompt()
    {
        $request = $this->owner->getRequest();
        return $request->getSession()->get('ShowPasskeyRegistrationPrompt');
    }
    
    /**
     * Get the email for passkey registration
     */
    public function getPasskeyRegistrationEmail()
    {
        $request = $this->owner->getRequest();
        return $request->getSession()->get('PasskeyRegistrationEmail');
    }
}