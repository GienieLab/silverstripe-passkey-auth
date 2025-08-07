<?php

namespace GienieLab\PasskeyAuth\Authenticator;

use SilverStripe\Forms\Form;
use SilverStripe\Security\Member;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Authenticator;
use GienieLab\PasskeyAuth\Form\PasskeyLoginForm;
use SilverStripe\Security\MemberAuthenticator\LoginHandler;

class PasskeyAuthenticator implements Authenticator
{
    public function supportedServices()
    {
        return Authenticator::LOGIN;
    }

    public function getLoginHandler($link)
    {
        return PasskeyLoginHandler::create($link, $this);
    }

    public function getLogOutHandler($link)
    {
        // Return null as we don't provide a custom logout handler
        return null;
    }

    public function getChangePasswordHandler($link)
    {
        // Return null as we don't provide a custom change password handler
        return null;
    }

    public function getLostPasswordHandler($link)
    {
        // Return null as we don't provide a custom lost password handler
        return null;
    }

    public function checkPassword(Member $member, $password, &$result = null)
    {
        // This method is not used for passkey authentication
        return false;
    }

    public function authenticate(array $data, HTTPRequest $request, &$result = null)
    {
        // This method can be used for direct authentication if needed
        return null;
    }

    public function logIn(Member $member, $persistent = false, HTTPRequest $request = null)
    {
        if (!$request) {
            $request = Controller::curr()->getRequest();
        }

        $request->getSession()->set('loggedInAs', $member->ID);
        
        if ($persistent) {
            $member->logIn($persistent);
        }

        return $member;
    }

    public function logOut(HTTPRequest $request = null)
    {
        if (!$request) {
            $request = Controller::curr()->getRequest();
        }

        $request->getSession()->clear('loggedInAs');
        
        if ($member = Member::currentUser()) {
            $member->logOut();
        }
    }
}

