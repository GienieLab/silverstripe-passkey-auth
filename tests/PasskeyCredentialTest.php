<?php

namespace GienieLab\PasskeyAuth\Tests;

use SilverStripe\Dev\SapphireTest;
use GienieLab\PasskeyAuth\Model\PasskeyCredential;
use SilverStripe\Security\Member;

class PasskeyCredentialTest extends SapphireTest
{
    protected static $fixture_file = 'PasskeyCredentialTest.yml';

    protected static $extra_dataobjects = [
        PasskeyCredential::class,
    ];

    public function testCredentialCreation()
    {
        $member = $this->objFromFixture(Member::class, 'testuser');
        
        $credential = PasskeyCredential::create([
            'CredentialID' => 'test-credential-id',
            'PublicKey' => 'test-public-key',
            'Counter' => 0,
            'MemberID' => $member->ID,
            'UserHandle' => 'test-user-handle',
            'DeviceName' => 'Test Device'
        ]);
        
        $credential->write();
        
        $this->assertNotNull($credential->ID);
        $this->assertEquals('test-credential-id', $credential->CredentialID);
        $this->assertEquals($member->ID, $credential->MemberID);
        $this->assertEquals('Test Device', $credential->DeviceName);
    }

    public function testCredentialUserHandleUniqueness()
    {
        $member1 = $this->objFromFixture(Member::class, 'testuser');
        $member2 = $this->objFromFixture(Member::class, 'testuser2');
        
        // Create first credential
        $credential1 = PasskeyCredential::create([
            'CredentialID' => 'credential-1',
            'PublicKey' => 'public-key-1',
            'Counter' => 0,
            'MemberID' => $member1->ID,
            'UserHandle' => 'unique-handle',
            'DeviceName' => 'Device 1'
        ]);
        $credential1->write();
        
        // Try to create second credential with same user handle
        $credential2 = PasskeyCredential::create([
            'CredentialID' => 'credential-2',
            'PublicKey' => 'public-key-2',
            'Counter' => 0,
            'MemberID' => $member2->ID,
            'UserHandle' => 'unique-handle',
            'DeviceName' => 'Device 2'
        ]);
        
        // This should work since UserHandle uniqueness is per-user, not global
        $credential2->write();
        $this->assertNotNull($credential2->ID);
    }

    public function testFindByCredentialID()
    {
        $member = $this->objFromFixture(Member::class, 'testuser');
        
        $credential = PasskeyCredential::create([
            'CredentialID' => 'findable-credential',
            'PublicKey' => 'test-public-key',
            'Counter' => 0,
            'MemberID' => $member->ID,
            'UserHandle' => 'test-handle',
            'DeviceName' => 'Findable Device'
        ]);
        $credential->write();
        
        $found = PasskeyCredential::get()->filter('CredentialID', 'findable-credential')->first();
        $this->assertNotNull($found);
        $this->assertEquals($credential->ID, $found->ID);
    }
}
