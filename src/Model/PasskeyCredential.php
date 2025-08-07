<?php

namespace GienieLab\PasskeyAuth\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\PermissionProvider;

class PasskeyCredential extends DataObject implements PermissionProvider
{
    private static $table_name = 'PasskeyCredential';

    private static $db = [
        'CredentialID' => 'Varchar(255)',
        'PublicKey' => 'Text',
        'AAGUID' => 'Varchar(255)',
        'SignCount' => 'Int',
        'Transports' => 'Text',
        'UserAgent' => 'Text',
        'LastUsed' => 'Datetime',
    ];

    private static $has_one = [
        'Member' => Member::class,
    ];

    private static $indexes = [
        'CredentialID' => true, // Unique index for credential ID
    ];

    private static $summary_fields = [
        'CredentialID' => 'Credential ID',
        'Member.Email' => 'User Email',
        'Created' => 'Created',
        'LastUsed' => 'Last Used',
    ];

    private static $searchable_fields = [
        'CredentialID',
        'Member.Email',
    ];

    private static $default_sort = 'Created DESC';

    /**
     * Update the last used timestamp and sign count
     */
    public function updateUsage($signCount = null, $userAgent = null)
    {
        $this->LastUsed = DBDatetime::now();
        if ($signCount !== null) {
            $this->SignCount = $signCount;
        }
        if ($userAgent !== null) {
            $this->UserAgent = $userAgent;
        }
        $this->write();
    }

    /**
     * Get a human readable name for this credential
     */
    public function getTitle()
    {
        $created = $this->dbObject('Created')->Nice();
        $userAgent = $this->UserAgent ? ' (' . substr($this->UserAgent, 0, 50) . '...)' : '';
        return "Passkey created {$created}{$userAgent}";
    }

    /**
     * Check if this credential belongs to the current user
     */
    public function canView($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        
        return $member && (
            $member->ID === $this->MemberID || 
            Permission::checkMember($member, 'ADMIN')
        );
    }

    /**
     * Only allow editing by the owner or admin
     */
    public function canEdit($member = null)
    {
        if (!$member) {
           $member = Security::getCurrentUser();
        }
        
        return $member && (
            $member->ID === $this->MemberID || 
            Permission::checkMember($member, 'ADMIN')
        );
    }

    /**
     * Only allow deletion by the owner or admin
     */
    public function canDelete($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        
        return $member && (
            $member->ID === $this->MemberID || 
            Permission::checkMember($member, 'ADMIN')
        );
    }

    /**
     * Only admins can create credentials directly (usually done through registration process)
     */
    public function canCreate($member = null, $context = [])
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        
        return $member && Permission::checkMember($member, 'ADMIN');
    }

    /**
     * Provide permissions for this model
     */
    public function providePermissions()
    {
        return [
            'PASSKEY_ADMIN' => 'Administer passkey credentials',
        ];
    }

    /**
     * Set user agent when credential is created
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        
        if (!$this->UserAgent && isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->UserAgent = $_SERVER['HTTP_USER_AGENT'];
        }
    }
}