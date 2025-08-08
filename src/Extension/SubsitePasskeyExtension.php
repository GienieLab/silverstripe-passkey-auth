<?php

namespace GienieLab\PasskeyAuth\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Subsites\State\SubsiteState;
use SilverStripe\Subsites\Model\Subsite;
use GienieLab\PasskeyAuth\Model\PasskeyCredential;

/**
 * Subsite-aware PasskeyService extension
 * Handles credential isolation and RP ID management across subsites
 */
class SubsitePasskeyExtension extends Extension
{
    /**
     * Get site-specific RP ID for WebAuthn dynamically from subsite domains
     * Handles multiple domains by using the currently accessed domain
     */
    public function getSubsiteRpId(): ?string
    {
        $subsite = Subsite::currentSubsite();
        
        if ($subsite && $subsite->exists()) {
            // First, try to match current request domain with subsite domains
            $currentDomain = $this->getCurrentDomain();
            
            // Check if current domain matches any of the subsite's configured domains
            $matchingDomain = $this->findMatchingSubsiteDomain($subsite, $currentDomain);
            if ($matchingDomain) {
                return $this->cleanDomainForRpId($matchingDomain);
            }
            
            // If no match, use primary domain as fallback
            $domain = $subsite->getPrimaryDomain();
            if ($domain && $domain->Domain) {
                return $this->cleanDomainForRpId($domain->Domain);
            }
            
            // Fallback to subsite domain field if no primary domain
            if ($subsite->Domain) {
                return $this->cleanDomainForRpId($subsite->Domain);
            }
        }
        
        // Fall back to main site RP ID from config or current domain
        $configRpId = $this->owner->config()->get('rp_id');
        if ($configRpId) {
            return $configRpId;
        }
        
        // Ultimate fallback: use current request domain
        return $this->getCurrentDomain();
    }

    /**
     * Get site-specific RP name dynamically from subsite configuration
     */
    public function getSubsiteRpName(): string
    {
        $subsite = Subsite::currentSubsite();
        
        if ($subsite && $subsite->exists()) {
            // Use subsite title as primary choice
            if ($subsite->Title) {
                return $subsite->Title;
            }
            
            // Use domain as fallback
            $domain = $subsite->getPrimaryDomain();
            if ($domain && $domain->Domain) {
                return $this->humanizeDomain($domain->Domain);
            }
            
            if ($subsite->Domain) {
                return $this->humanizeDomain($subsite->Domain);
            }
        }
        
        // Fall back to main site config or default
        $configRpName = $this->owner->config()->get('rp_name');
        return $configRpName ?: 'SilverStripe Site';
    }

    /**
     * Clean domain name for use as RP ID (remove protocols, paths, etc.)
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
        
        // Convert to lowercase
        return strtolower(trim($domain));
    }

    /**
     * Convert domain to human-readable name
     */
    private function humanizeDomain(string $domain): string
    {
        $cleanDomain = $this->cleanDomainForRpId($domain);
        
        // Remove common prefixes
        $cleanDomain = preg_replace('/^(www\.|m\.|mobile\.)/', '', $cleanDomain);
        
        // Convert to title case and replace dots with spaces for readability
        $parts = explode('.', $cleanDomain);
        $mainPart = ucfirst($parts[0]);
        
        return $mainPart . ' Site';
    }

    /**
     * Get current request domain as fallback
     */
    private function getCurrentDomain(): string
    {
        $request = $this->owner->getRequest();
        if ($request) {
            $host = $request->getHeader('Host');
            if ($host) {
                return $this->cleanDomainForRpId($host);
            }
        }
        
        // Ultimate fallback
        return 'localhost';
    }

    /**
     * Find matching domain for current subsite from all configured domains
     * Handles multiple domains per subsite by finding the one being accessed
     */
    private function findMatchingSubsiteDomain(Subsite $subsite, string $currentDomain): ?string
    {
        // Clean current domain for comparison
        $cleanCurrentDomain = $this->cleanDomainForRpId($currentDomain);
        
        // Get all domains for this subsite (modern SilverStripe Subsites)
        if (method_exists($subsite, 'getDomains')) {
            $domains = $subsite->getDomains();
            foreach ($domains as $domainObj) {
                $domainName = $this->cleanDomainForRpId($domainObj->Domain);
                if ($domainName === $cleanCurrentDomain) {
                    return $domainName;
                }
            }
        }
        
        // Try alternative approach for different subsite module versions
        if (method_exists($subsite, 'Domains')) {
            $domains = $subsite->Domains();
            if ($domains) {
                foreach ($domains as $domainObj) {
                    $domainName = $this->cleanDomainForRpId($domainObj->Domain);
                    if ($domainName === $cleanCurrentDomain) {
                        return $domainName;
                    }
                }
            }
        }
        
        // Check primary domain
        $primaryDomain = $subsite->getPrimaryDomain();
        if ($primaryDomain && $primaryDomain->Domain) {
            $domainName = $this->cleanDomainForRpId($primaryDomain->Domain);
            if ($domainName === $cleanCurrentDomain) {
                return $domainName;
            }
        }
        
        // Check subsite domain field (legacy)
        if ($subsite->Domain) {
            $domainName = $this->cleanDomainForRpId($subsite->Domain);
            if ($domainName === $cleanCurrentDomain) {
                return $domainName;
            }
        }
        
        return null;
    }

    /**
     * Get all domains for a subsite (for admin interface)
     */
    public function getAllSubsiteDomains(Subsite $subsite): array
    {
        $domains = [];
        
        // Get all domains for this subsite
        if (method_exists($subsite, 'getDomains')) {
            $domainObjs = $subsite->getDomains();
            foreach ($domainObjs as $domainObj) {
                $domains[] = [
                    'domain' => $domainObj->Domain,
                    'is_primary' => $domainObj->IsPrimary ?? false,
                    'protocol' => $domainObj->Protocol ?? 'https'
                ];
            }
        } elseif (method_exists($subsite, 'Domains')) {
            $domainObjs = $subsite->Domains();
            if ($domainObjs) {
                foreach ($domainObjs as $domainObj) {
                    $domains[] = [
                        'domain' => $domainObj->Domain,
                        'is_primary' => $domainObj->IsPrimary ?? false,
                        'protocol' => $domainObj->Protocol ?? 'https'
                    ];
                }
            }
        }
        
        // Add primary domain if not already included
        $primaryDomain = $subsite->getPrimaryDomain();
        if ($primaryDomain && $primaryDomain->Domain) {
            $found = false;
            foreach ($domains as $domain) {
                if ($domain['domain'] === $primaryDomain->Domain) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $domains[] = [
                    'domain' => $primaryDomain->Domain,
                    'is_primary' => true,
                    'protocol' => $primaryDomain->Protocol ?? 'https'
                ];
            }
        }
        
        // Add legacy domain field if not already included
        if ($subsite->Domain) {
            $found = false;
            foreach ($domains as $domain) {
                if ($domain['domain'] === $subsite->Domain) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $domains[] = [
                    'domain' => $subsite->Domain,
                    'is_primary' => false,
                    'protocol' => 'https'
                ];
            }
        }
        
        return $domains;
    }

    /**
     * Hook into subsite state changes to clear RP entity cache
     */
    public function onAfterSubsiteChange(): void
    {
        // Clear the RP entity cache when subsite changes
        // This ensures fresh RP ID and name for the new subsite
        $this->owner->clearRpEntityCache();
    }

    /**
     * Update credential queries to filter by current subsite
     */
    public function updateCredentialQuery($query)
    {
        $subsiteID = SubsiteState::singleton()->getSubsiteId();
        
        if ($subsiteID > 0) {
            // Filter credentials to current subsite
            $query = $query->filter('SubsiteID', $subsiteID);
        }
        
        return $query;
    }

    /**
     * Set subsite context when creating credentials
     */
    public function onBeforeWrite()
    {
        $owner = $this->owner;
        
        if ($owner instanceof PasskeyCredential) {
            $subsiteID = SubsiteState::singleton()->getSubsiteId();
            
            if ($subsiteID > 0 && !$owner->SubsiteID) {
                $owner->SubsiteID = $subsiteID;
            }
        }
    }
}

/**
 * Database extension for PasskeyCredential to support subsites
 */
class PasskeyCredentialSubsiteExtension extends Extension
{
    private static $db = [
        'SubsiteID' => 'Int'
    ];

    private static $has_one = [
        'Subsite' => Subsite::class
    ];

    private static $indexes = [
        'SubsiteCredential' => [
            'type' => 'index',
            'columns' => ['SubsiteID', 'UserID', 'IsActive']
        ]
    ];

    /**
     * Ensure credentials are filtered by subsite
     */
    public function augmentSQL(SQLSelect $query, DataQuery $dataQuery = null)
    {
        if (Subsite::$disable_subsite_filter) {
            return;
        }

        $subsiteID = SubsiteState::singleton()->getSubsiteId();
        
        if ($subsiteID !== null) {
            $query->addWhere([
                '"PasskeyCredential"."SubsiteID" IN (?, 0)' => [$subsiteID]
            ]);
        }
    }

    /**
     * Set default subsite on credential creation
     */
    public function onBeforeWrite()
    {
        if (!$this->owner->SubsiteID) {
            $this->owner->SubsiteID = SubsiteState::singleton()->getSubsiteId() ?: 0;
        }
    }
}
