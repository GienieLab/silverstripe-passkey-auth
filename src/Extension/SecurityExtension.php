<?php

namespace GienieLab\PasskeyAuth\Extension;

use SilverStripe\Security\Security;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\HiddenField;
use SilverStripe\View\Requirements;
use SilverStripe\Core\Extension;

class SecurityExtension extends Extension
{
    public function updateLoginForm(&$form)
    {
        // Add passkey assets to the login form
        Requirements::javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-auth.js');
        Requirements::css('gienielab/silverstripe-passkey-auth:client/dist/css/passkey-auth.css');
        
        // Add hidden field for passkey authentication data
        $form->Fields()->push(
            HiddenField::create('PasskeyData', 'PasskeyData')
        );
        
        // Add CSS class for styling
        $form->addExtraClass('passkey-enhanced-form');
    }
    
    /**
     * Check if passkey registration prompt should be shown
     */
    public function getShowPasskeyRegistrationPrompt()
    {
        $request = Controller::curr()->getRequest();
        return $request->getSession()->get('ShowPasskeyRegistrationPrompt');
    }
    
    /**
     * Get the email for passkey registration
     */
    public function getPasskeyRegistrationEmail()
    {
        $request = Controller::curr()->getRequest();
        return $request->getSession()->get('PasskeyRegistrationEmail');
    }
    
    /**
     * Get the URL to redirect to after passkey registration
     */
    public function getPostPasskeyRegistrationURL()
    {
        $request = Controller::curr()->getRequest();
        return $request->getSession()->get('PostPasskeyRegistrationURL') ?: '/admin';
    }
}
