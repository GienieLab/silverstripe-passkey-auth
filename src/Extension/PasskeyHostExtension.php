<?php

namespace GienieLab\PasskeyAuth\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Environment;
use SilverStripe\Control\Director;
use SilverStripe\Subsites\State\SubsiteState;
use SilverStripe\Subsites\Model\Subsite;
use GienieLab\PasskeyAuth\Model\PasskeyCredential;

/**
 * Simplified PasskeyService extension using SS_ALLOWED_HOSTS for domain management
 * This approach is more reliable and works for both main site and subsites
 */
class PasskeyHostExtension extends Extension
{
    /**
     * Get RP ID based on current domain from SS_ALLOWED_HOSTS
     * This is more reliable than subsite-specific configuration
     */
    public function getHostBasedRpId(): ?string
    {
        $currentDomain = $this->getCurrentDomain();
        
        // Validate against SS_ALLOWED_HOSTS for security
        if ($this->isDomainAllowed($currentDomain)) {
            return $this->cleanDomainForRpId($currentDomain);
        }
        
        // Fallback to config or first allowed host
        $configRpId = $this->owner->config()->get('rp_id');
        if ($configRpId && $this->isDomainAllowed($configRpId)) {
            return $configRpId;
        }
        
        // Ultimate fallback: first allowed host
        $allowedHosts = $this->getAllowedHosts();
        if (!empty($allowedHosts)) {
            return $this->cleanDomainForRpId($allowedHosts[0]);
        }
        
        return 'localhost';
    }

    /**
     * Get RP Name based on current context with multiple fallback strategies
     */
    public function getHostBasedRpName(): string
    {
        $currentDomain = $this->getCurrentDomain();
        
        // Strategy 1: Check for domain-specific configuration
        $domainSpecificName = $this->getDomainSpecificRpName($currentDomain);
        if ($domainSpecificName) {
            return $domainSpecificName;
        }
        
        // Strategy 2: If subsites is available, use subsite title
        if (class_exists(Subsite::class)) {
            $subsite = Subsite::currentSubsite();
            if ($subsite && $subsite->exists() && $subsite->Title) {
                return $subsite->Title;
            }
        }
        
        // Strategy 3: Use global configuration
        $globalRpName = $this->owner->config()->get('rp_name');
        if ($globalRpName && $globalRpName !== 'SilverStripe Site') {
            return $globalRpName;
        }
        
        // Strategy 4: Generate name from current domain
        return $this->humanizeDomain($currentDomain);
    }

    /**
     * Get domain-specific RP name from configuration
     */
    private function getDomainSpecificRpName(string $domain): ?string
    {
        $cleanDomain = $this->cleanDomainForRpId($domain);
        
        // Check for domain-specific configuration
        $domainNames = $this->owner->config()->get('domain_names');
        if (is_array($domainNames) && isset($domainNames[$cleanDomain])) {
            return $domainNames[$cleanDomain];
        }
        
        return null;
    }

    /**
     * Check if domain is in SS_ALLOWED_HOSTS
     */
    private function isDomainAllowed(string $domain): bool
    {
        $cleanDomain = $this->cleanDomainForRpId($domain);
        $allowedHosts = $this->getAllowedHosts();
        
        foreach ($allowedHosts as $allowedHost) {
            $cleanAllowedHost = $this->cleanDomainForRpId($allowedHost);
            if ($cleanAllowedHost === $cleanDomain) {
                return true;
            }
        }
        
        // Allow localhost for development
        return $cleanDomain === 'localhost' || $cleanDomain === '127.0.0.1';
    }

    /**
     * Get all allowed hosts from SS_ALLOWED_HOSTS
     */
    private function getAllowedHosts(): array
    {
        $allowedHosts = Environment::getEnv('SS_ALLOWED_HOSTS');
        
        if (!$allowedHosts) {
            // Fallback to configuration
            $allowedHosts = $this->owner->config()->get('allowed_hosts');
        }
        
        if (is_string($allowedHosts)) {
            // Handle comma-separated string
            return array_map('trim', explode(',', $allowedHosts));
        }
        
        if (is_array($allowedHosts)) {
            return $allowedHosts;
        }
        
        return [];
    }

    /**
     * Get current request domain
     */
    private function getCurrentDomain(): string
    {
        // Try to get from current request
        $request = $this->owner->getRequest();
        if ($request) {
            $host = $request->getHeader('Host');
            if ($host) {
                return $this->cleanDomainForRpId($host);
            }
        }
        
        // Try Director::absoluteBaseURL()
        $baseUrl = Director::absoluteBaseURL();
        if ($baseUrl) {
            $parsed = parse_url($baseUrl);
            if (isset($parsed['host'])) {
                return $this->cleanDomainForRpId($parsed['host']);
            }
        }
        
        // Try HTTP_HOST from server variables
        if (isset($_SERVER['HTTP_HOST'])) {
            return $this->cleanDomainForRpId($_SERVER['HTTP_HOST']);
        }
        
        return 'localhost';
    }

    /**
     * Clean domain name for use as RP ID
     */
    private function cleanDomainForRpId(string $domain): string
    {
        // Remove protocol if present
        $domain = preg_replace('#^https?://#', '', $domain);
        
        // Remove port if present
        $domain = preg_replace('#:\d+$#', '', $domain);
        
        // Remove path and query parameters
        $domain = strtok($domain, '/');
        $domain = strtok($domain, '?');
        
        // Convert to lowercase and trim
        return strtolower(trim($domain));
    }

    /**
     * Convert domain to human-readable name with improved logic
     */
    private function humanizeDomain(string $domain): string
    {
        $cleanDomain = $this->cleanDomainForRpId($domain);
        
        // Remove common prefixes
        $cleanDomain = preg_replace('/^(www\.|m\.|mobile\.|api\.|admin\.|shop\.|store\.|blog\.)/', '', $cleanDomain);
        
        // Split domain and create readable name
        $parts = explode('.', $cleanDomain);
        $mainPart = $parts[0];
        
        // Handle special cases for better naming
        $specialNames = [
            'shop' => 'Shop',
            'store' => 'Store', 
            'blog' => 'Blog',
            'api' => 'API',
            'admin' => 'Admin',
            'portal' => 'Portal',
            'dashboard' => 'Dashboard',
            'app' => 'Application',
            'demo' => 'Demo',
            'staging' => 'Staging',
            'dev' => 'Development',
            'test' => 'Testing'
        ];
        
        $mainPartLower = strtolower($mainPart);
        if (isset($specialNames[$mainPartLower])) {
            return $specialNames[$mainPartLower];
        }
        
        // Convert to title case
        $readableName = ucfirst($mainPart);
        
        // Add "Site" suffix if it's a simple domain
        if (count($parts) <= 2 && !in_array($mainPartLower, array_keys($specialNames))) {
            $readableName .= ' Site';
        }
        
        return $readableName;
    }

    /**
     * Get all configured domains for admin display
     */
    public function getAllConfiguredDomains(): array
    {
        $domains = [];
        $allowedHosts = $this->getAllowedHosts();
        $currentDomain = $this->getCurrentDomain();
        
        foreach ($allowedHosts as $host) {
            $cleanHost = $this->cleanDomainForRpId($host);
            $domains[] = [
                'domain' => $cleanHost,
                'is_current' => ($cleanHost === $currentDomain),
                'is_allowed' => true,
                'source' => 'SS_ALLOWED_HOSTS'
            ];
        }
        
        return $domains;
    }

    /**
     * Update credential queries to filter by current subsite (if subsites enabled)
     */
    public function updateCredentialQuery($query)
    {
        if (class_exists(SubsiteState::class)) {
            $subsiteID = SubsiteState::singleton()->getSubsiteId();
            
            if ($subsiteID > 0) {
                // Filter credentials to current subsite
                $query = $query->filter('SubsiteID', $subsiteID);
            }
        }
        
        return $query;
    }

    /**
     * Set subsite context when creating credentials (if subsites enabled)
     */
    public function onBeforeWrite()
    {
        $owner = $this->owner;
        
        if ($owner instanceof PasskeyCredential && class_exists(SubsiteState::class)) {
            $subsiteID = SubsiteState::singleton()->getSubsiteId();
            
            if ($subsiteID > 0 && !$owner->SubsiteID) {
                $owner->SubsiteID = $subsiteID;
            }
        }
    }

    /**
     * Clear RP entity cache when needed
     */
    public function clearRpEntityCache(): void
    {
        // Clear any cached RP entity data
        if (method_exists($this->owner, 'clearRpEntityCache')) {
            $this->owner->clearRpEntityCache();
        }
    }
}
