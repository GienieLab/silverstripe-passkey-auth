<?php

namespace GienieLab\PasskeyAuth\Tests;

use SilverStripe\Dev\SapphireTest;
use GienieLab\PasskeyAuth\Service\PasskeyService;
use SilverStripe\Security\Member;

class PasskeyServiceTest extends SapphireTest
{
    protected static $fixture_file = 'PasskeyCredentialTest.yml';

    public function testGetRpEntity()
    {
        $service = new PasskeyService();
        $rpEntity = $service->getRpEntity();
        
        $this->assertNotNull($rpEntity);
        $this->assertNotEmpty($rpEntity->getId());
        $this->assertNotEmpty($rpEntity->getName());
    }

    public function testGenerateRandomBytes()
    {
        $service = new PasskeyService();
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('generateRandomBytes');
        $method->setAccessible(true);
        
        $bytes = $method->invoke($service, 32);
        $this->assertEquals(32, strlen($bytes));
        
        // Generate another set to ensure they're different
        $bytes2 = $method->invoke($service, 32);
        $this->assertNotEquals($bytes, $bytes2);
    }

    public function testBase64UrlEncodeDecode()
    {
        $service = new PasskeyService();
        $testData = 'Hello, World! This is a test string with special characters: +/=';
        
        // Use reflection to test private methods
        $reflection = new \ReflectionClass($service);
        
        $encodeMethod = $reflection->getMethod('base64url_encode');
        $encodeMethod->setAccessible(true);
        
        $decodeMethod = $reflection->getMethod('base64url_decode');
        $decodeMethod->setAccessible(true);
        
        $encoded = $encodeMethod->invoke($service, $testData);
        $decoded = $decodeMethod->invoke($service, $encoded);
        
        $this->assertEquals($testData, $decoded);
        
        // Ensure base64url encoding doesn't contain +, /, or = characters
        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
    }
}
