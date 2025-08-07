<?php

namespace GienieLab\PasskeyAuth\Extension;

use SilverStripe\Core\Extension;

class PageControllerExtension extends Extension
{
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