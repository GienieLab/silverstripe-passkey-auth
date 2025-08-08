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
use GienieLab\PasskeyAuth\Extension\SubsitePasskeyExtension;

/**
 * Admin interface for viewing dynamic subsite passkey configuration
 */
class PasskeySubsiteConfigAdmin extends ModelAdmin
{
    private static $menu_title = 'Passkey Subsite Config';
    private static $url_segment = 'passkey-subsites';
    private static $menu_icon_class = 'font-icon-key';
    private static $menu_priority = 10;

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        
        if (class_exists('SilverStripe\Subsites\Model\Subsite')) {
            $this->addSubsiteConfigurationView($form);
        }
        
        return $form;
    }

    protected function addSubsiteConfigurationView($form)
    {
        $fields = $form->Fields();
        
        // Add header
        $fields->push(HeaderField::create(
            'SubsiteConfigHeader',
            'Dynamic Passkey Configuration by Subsite'
        ));
        
        // Get PasskeyService instance
        $passkeyService = Injector::inst()->get(PasskeyService::class);
        
        // Main site configuration
        $mainSiteConfig = $this->getMainSiteConfig($passkeyService);
        $fields->push(ReadonlyField::create(
            'MainSiteConfig',
            'Main Site (ID: 0)',
            $mainSiteConfig
        ));
        
        // Get all subsites and show their dynamic configuration
        $subsites = Subsite::get();
        
        foreach ($subsites as $subsite) {
            $config = $this->getSubsiteConfig($subsite, $passkeyService);
            $fields->push(ReadonlyField::create(
                'SubsiteConfig_' . $subsite->ID,
                "Subsite: {$subsite->Title} (ID: {$subsite->ID})",
                $config
            ));
        }
        
        // Add explanation
        $fields->push(ReadonlyField::create(
            'ConfigExplanation',
            'How it works',
            'The system automatically detects RP ID from subsite domains and RP Name from subsite titles. ' .
            'No manual YAML configuration needed for new subsites!'
        ));
    }

    protected function getMainSiteConfig(PasskeyService $service): string
    {
        // Temporarily switch to main site context
        $currentSubsite = Subsite::currentSubsiteID();
        Subsite::changeSubsite(0);
        
        // Get the fresh RP entity with main site context
        $service->clearRpEntityCache();
        $rpEntity = $service->refreshRpEntity();
        $rpId = $rpEntity->getId();
        $rpName = $rpEntity->getName();
        
        // Restore original subsite context
        Subsite::changeSubsite($currentSubsite);
        
        return "RP ID: {$rpId} | RP Name: {$rpName}";
    }

    protected function getSubsiteConfig(Subsite $subsite, PasskeyService $service): string
    {
        // Temporarily switch to this subsite context
        $currentSubsite = Subsite::currentSubsiteID();
        Subsite::changeSubsite($subsite->ID);
        
        // Get the fresh RP entity with subsite context
        $service->clearRpEntityCache();
        $rpEntity = $service->refreshRpEntity();
        $rpId = $rpEntity->getId();
        $rpName = $rpEntity->getName();
        
        // Get all domains for this subsite
        $extension = $service->getExtensionInstance(SubsitePasskeyExtension::class);
        $allDomains = $extension ? $extension->getAllSubsiteDomains($subsite) : [];
        
        // Format domain information
        $domainInfo = '';
        if (empty($allDomains)) {
            $domainInfo = 'No domains configured';
        } else {
            $domainStrings = [];
            foreach ($allDomains as $domainData) {
                $domainString = $domainData['domain'];
                if ($domainData['is_primary']) {
                    $domainString .= ' (PRIMARY)';
                }
                $domainStrings[] = $domainString;
            }
            $domainInfo = implode(', ', $domainStrings);
        }
        
        // Restore original subsite context
        Subsite::changeSubsite($currentSubsite);
        
        return "RP ID: {$rpId} | RP Name: {$rpName}\nDomains: {$domainInfo}";
    }
}
