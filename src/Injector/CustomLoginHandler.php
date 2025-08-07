<?php

namespace GienieLab\PasskeyAuth\Injector;

use SilverStripe\Forms\Form;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use GienieLab\PasskeyAuth\Model\PasskeyCredential;
use SilverStripe\Security\MemberAuthenticator\LoginHandler;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;

class CustomLoginHandler extends LoginHandler
{
    /**
     * Override doLogin to handle passkey registration prompts after successful password login
     */
    public function doLogin($data, MemberLoginForm $form, HTTPRequest $request)
    {
        // Get the BackURL before calling parent to preserve it
        $backURL = $request->getVar('BackURL') ?: $request->getSession()->get('BackURL');
        
        // First, perform the standard login process
        $result = parent::doLogin($data, $form, $request);
        
        // Check if login was successful by getting the current user
        $member = Security::getCurrentUser();
        
        if ($member instanceof Member) {
            
            // Check if this member has any passkeys registered
            $passkeyCount = PasskeyCredential::get()
                ->filter('MemberID', $member->ID)
                ->count();
            
            if ($passkeyCount === 0) {
                // Store the original BackURL for after registration
                if ($backURL) {
                    $request->getSession()->set('PostPasskeyRegistrationURL', $backURL);
                }
                
                // Redirect to passkey registration page instead of normal redirect
                return $this->redirect('passkey-auth/webauth');
            } else {
                // User has passkeys - let the original result handle the redirect
                // But ensure BackURL is properly preserved in session if the result doesn't handle it
                if ($backURL && !$request->getSession()->get('BackURL')) {
                    $request->getSession()->set('BackURL', $backURL);
                }
            }
        } 
        
        // For successful logins with existing passkeys, or failed logins, return original result
        return $result;
    }


}
