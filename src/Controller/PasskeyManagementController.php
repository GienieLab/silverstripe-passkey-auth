<?php

namespace GienieLab\PasskeyAuth\Controller;

use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use GienieLab\PasskeyAuth\Model\PasskeyCredential;
use SilverStripe\CMS\Controllers\ContentController;

class PasskeyManagementController extends ContentController
{
    private static $allowed_actions = [
        'index',
        'delete',
        'list'
    ];

    private static $url_handlers = [
        '' => 'index',
        'delete/$ID' => 'delete',
        'list' => 'list'
    ];

    private static $url_segment = 'passkey-management';

    /**
     * Display passkey management interface
     */
    public function index(HTTPRequest $request)
    {
        $member = Security::getCurrentUser();
        if (!$member) {
            return $this->redirect('Security/login?BackURL=' . urlencode($request->getURL()));
        }
        // Include module assets using vendor path for proper module installation
        Requirements::javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-management.js');  
        Requirements::css('gienielab/silverstripe-passkey-auth:client/dist/css/styles.css');
        
        $credentials = PasskeyCredential::get()->filter('MemberID', $member->ID);
        
        return $this->customise([
            'Title' => 'Manage Your Passkeys',
            'Member' => $member,
            'PasskeyCredentials' => $credentials,
            'CanRegisterNew' => true
        ])->renderWith(['PasskeyManagement', 'Page']);
    }

    /**
     * Delete a specific passkey credential
     */
    public function delete(HTTPRequest $request)
    {
        if (!$request->isPost()) {
            return $this->httpError(405, 'Method not allowed');
        }

        $member = Security::getCurrentUser();
        if (!$member) {
            return $this->httpError(401, 'Unauthorized');
        }

        $credentialID = $request->param('ID');
        if (!$credentialID) {
            return $this->httpError(400, 'Missing credential ID');
        }

        $credential = PasskeyCredential::get()
            ->filter([
                'ID' => $credentialID,
                'MemberID' => $member->ID
            ])
            ->first();

        if (!$credential) {
            return $this->httpError(404, 'Credential not found or not owned by you');
        }

        $credential->delete();

        // Return JSON response for AJAX calls
        if ($request->getHeader('Content-Type') === 'application/json' || 
            $request->getHeader('Accept') === 'application/json') {
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Passkey deleted successfully'
            ]);
        }

        // Redirect back to management page
        $this->getRequest()->getSession()->set('FormInfo.PasskeyManagement.message', 'Passkey deleted successfully');
        return $this->redirect($this->Link());
    }

    /**
     * Get list of passkeys as JSON
     */
    public function list(HTTPRequest $request)
    {
        $member = Security::getCurrentUser();
        if (!$member) {
            return $this->httpError(401, 'Unauthorized');
        }

        $credentials = PasskeyCredential::get()->filter('MemberID', $member->ID);
        
        $data = [];
        foreach ($credentials as $credential) {
            $data[] = [
                'ID' => $credential->ID,
                'CredentialID' => substr($credential->CredentialID, 0, 20) . '...',
                'Created' => $credential->dbObject('Created')->Nice(),
                'LastUsed' => $credential->LastUsed ? $credential->dbObject('LastUsed')->Nice() : 'Never',
                'UserAgent' => $credential->UserAgent ? substr($credential->UserAgent, 0, 50) . '...' : 'Unknown',
                'DeleteURL' => $this->Link("delete/{$credential->ID}")
            ];
        }

        return $this->jsonResponse([
            'credentials' => $data,
            'count' => count($data)
        ]);
    }

    /**
     * Helper method to return JSON responses
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        $response = new HTTPResponse();
        $response->setStatusCode($statusCode);
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode($data));
        return $response;
    }

    /**
     * Get the link to this controller
     */
    public function Link($action = null)
    {
        return Controller::join_links(Director::baseURL(), $this->config()->get('url_segment'), $action);
    }
}
