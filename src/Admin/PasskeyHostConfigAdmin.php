<?php

namespace GienieLab\PasskeyAuth\Admin;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Subsites\Model\Subsite;
use GienieLab\PasskeyAuth\Service\PasskeyService;
use GienieLab\PasskeyAuth\Extension\PasskeyHostExtension;

/**
 * Admin interface for viewing host-based passkey configuration
 * Uses SS_ALLOWED_HOSTS instead of subsite-specific configuration
 */
class PasskeyHostConfigAdmin extends ModelAdmin
{
    private static $menu_title = 'Passkey Host Config';
    private static $url_segment = 'passkey-hosts';
    private static $menu_icon_class = 'font-icon-key';

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        
        $fields = $form->Fields();
        $fields->removeByName('SearchForm');
        
        // Get PasskeyService
        $passkeyService = Injector::inst()->get(PasskeyService::class);
        
        // Add header
        $fields->push(HeaderField::create(
            'PasskeyHostConfigHeader',
            'Passkey Host-Based Configuration'
        ));
        
        // Show current configuration
        $this->addCurrentConfig($fields, $passkeyService);
        
        // Show all allowed hosts
        $this->addAllowedHosts($fields, $passkeyService);
        
        // Show subsites if available
        if (class_exists(Subsite::class)) {
            $this->addSubsiteInfo($fields, $passkeyService);
        }
        
        return $form;
    }

    protected function addCurrentConfig($fields, PasskeyService $service)
    {
        $service->clearRpEntityCache();
        $rpEntity = $service->refreshRpEntity();
        $rpId = $rpEntity->getId();
        $rpName = $rpEntity->getName();
        
        // Get the source of the RP Name
        $rpNameSource = $this->getRpNameSource($service);
        
        $fields->push(HeaderField::create(
            'CurrentConfigHeader',
            'Current Configuration'
        ));
        
        $fields->push(ReadonlyField::create(
            'CurrentRpId',
            'Current RP ID',
            $rpId . ' (from current domain)'
        ));
        
        $fields->push(ReadonlyField::create(
            'CurrentRpName',
            'Current RP Name',
            $rpName . ' (' . $rpNameSource . ')'
        ));
    }

    /**
     * Determine the source of the RP Name
     */
    protected function getRpNameSource(PasskeyService $service): string
    {
        $extension = $service->getExtensionInstance(PasskeyHostExtension::class);
        if (!$extension) {
            return 'fallback configuration';
        }
        
        $currentDomain = $extension->getCurrentDomain();
        
        // Check domain-specific configuration
        $domainNames = $service->config()->get('domain_names');
        if (is_array($domainNames) && isset($domainNames[$currentDomain])) {
            return 'domain-specific configuration';
        }
        
        // Check subsite title
        if (class_exists(Subsite::class)) {
            $subsite = Subsite::currentSubsite();
            if ($subsite && $subsite->exists() && $subsite->Title) {
                return 'subsite title';
            }
        }
        
        // Check global configuration
        $globalRpName = $service->config()->get('rp_name');
        if ($globalRpName && $globalRpName !== 'SilverStripe Site') {
            return 'global configuration';
        }
        
        return 'auto-generated from domain';
    }

    protected function addAllowedHosts($fields, PasskeyService $service)
    {
        $fields->push(HeaderField::create(
            'AllowedHostsHeader',
            'SS_ALLOWED_HOSTS Configuration'
        ));
        
        // Get host extension if available
        $extension = $service->getExtensionInstance(PasskeyHostExtension::class);
        if ($extension) {
            $domains = $extension->getAllConfiguredDomains();
            
            if (empty($domains)) {
                $fields->push(ReadonlyField::create(
                    'NoAllowedHosts',
                    'Status',
                    'No SS_ALLOWED_HOSTS configured. This may cause issues in production.'
                ));
            } else {
                foreach ($domains as $index => $domainData) {
                    $status = [];
                    if ($domainData['is_current']) {
                        $status[] = 'CURRENT';
                    }
                    if ($domainData['is_allowed']) {
                        $status[] = 'ALLOWED';
                    }
                    
                    $statusText = !empty($status) ? ' (' . implode(', ', $status) . ')' : '';
                    
                    $fields->push(ReadonlyField::create(
                        'AllowedHost_' . $index,
                        "Domain " . ($index + 1),
                        $domainData['domain'] . $statusText
                    ));
                }
            }
        } else {
            $fields->push(ReadonlyField::create(
                'NoHostExtension',
                'Status',
                'PasskeyHostExtension not enabled. Using fallback configuration.'
            ));
        }
        
        // Add configuration guidance
        $fields->push(ReadonlyField::create(
            'HostConfigGuidance',
            'Configuration Guide',
            'Set SS_ALLOWED_HOSTS in your .env file: SS_ALLOWED_HOSTS="example.com,www.example.com,shop.example.com"'
        ));
        
        // Add RP Name configuration examples
        $fields->push(HeaderField::create(
            'RpNameConfigHeader',
            'RP Name Configuration Options'
        ));
        
        $fields->push(ReadonlyField::create(
            'RpNameGuidance',
            'Configuration Examples',
            "1. Global: rp_name: 'My Company'\n" .
            "2. Domain-specific: domain_names: {'example.com': 'Main Site', 'shop.example.com': 'Shop'}\n" .
            "3. Subsite titles (automatic)\n" .
            "4. Auto-generated from domain names"
        ));
        
        // Show current domain-specific configuration if available
        $domainNames = $service->config()->get('domain_names');
        if (is_array($domainNames) && !empty($domainNames)) {
            $domainNamesList = [];
            foreach ($domainNames as $domain => $name) {
                $domainNamesList[] = "{$domain}: '{$name}'";
            }
            
            $fields->push(ReadonlyField::create(
                'DomainNamesConfig',
                'Configured Domain Names',
                implode("\n", $domainNamesList)
            ));
        }
    }

    protected function addSubsiteInfo($fields, PasskeyService $service)
    {
        $fields->push(HeaderField::create(
            'SubsiteInfoHeader',
            'Subsite Integration'
        ));
        
        $currentSubsite = Subsite::currentSubsite();
        if ($currentSubsite && $currentSubsite->exists()) {
            $fields->push(ReadonlyField::create(
                'CurrentSubsite',
                'Current Subsite',
                "{$currentSubsite->Title} (ID: {$currentSubsite->ID})"
            ));
        } else {
            $fields->push(ReadonlyField::create(
                'CurrentSubsite',
                'Current Subsite',
                'Main Site (ID: 0)'
            ));
        }
        
        // List all subsites with their host-based configuration
        $subsites = Subsite::get();
        foreach ($subsites as $subsite) {
            $config = $this->getSubsiteHostConfig($subsite, $service);
            $fields->push(ReadonlyField::create(
                'SubsiteHost_' . $subsite->ID,
                "Subsite: {$subsite->Title} (ID: {$subsite->ID})",
                $config
            ));
        }
    }

    protected function getSubsiteHostConfig(Subsite $subsite, PasskeyService $service): string
    {
        // Temporarily switch to this subsite context
        $currentSubsite = Subsite::currentSubsiteID();
        Subsite::changeSubsite($subsite->ID);
        
        // Get the fresh RP entity with subsite context
        $service->clearRpEntityCache();
        $rpEntity = $service->refreshRpEntity();
        $rpId = $rpEntity->getId();
        $rpName = $rpEntity->getName();
        
        // Restore original subsite context
        Subsite::changeSubsite($currentSubsite);
        
        return "RP ID: {$rpId} | RP Name: {$rpName} | Source: SS_ALLOWED_HOSTS";
    }
}
