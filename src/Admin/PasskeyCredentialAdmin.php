<?php

namespace GienieLab\PasskeyAuth\Admin;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\LiteralField;
use GienieLab\PasskeyAuth\Model\PasskeyCredential;

class PasskeyCredentialAdmin extends ModelAdmin
{
    private static $managed_models = [
        PasskeyCredential::class,
    ];

    private static $url_segment = 'passkey-credentials';

    private static $menu_title = 'Passkey Credentials';

    private static $menu_icon_class = 'font-icon-p-shield';

    private static $menu_priority = 3;

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        if ($this->modelClass === PasskeyCredential::class) {
            $gridField = $form->Fields()->fieldByName($this->sanitiseClassName($this->modelClass));
            
            if ($gridField) {
                // Customize grid field config
                $config = $gridField->getConfig();
                
                // Remove add new button (passkeys should only be created through registration)
                $config->removeComponentsByType(GridFieldAddNewButton::class);
                $config->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
                
                // Keep delete functionality for admins
                $config->addComponent(new GridFieldDeleteAction());
                
                // Add info field at the top
                $form->Fields()->insertBefore(
                    $gridField->getName(),
                    LiteralField::create(
                        'PasskeyCredentialInfo',
                        '<div class="alert alert-info">
                            <h4>Passkey Credential Management</h4>
                            <p><strong>Important:</strong> Passkey credentials cannot be created manually through this interface. 
                            They are automatically generated when users register passkeys through the authentication flow.</p>
                            <p><strong>Deleting Credentials:</strong> Removing a passkey credential here will prevent the user 
                            from using that specific passkey to authenticate. The user can register a new passkey if needed.</p>
                            <p><strong>Security Note:</strong> Users can manage their own passkeys through their account settings 
                            when logged in.</p>
                        </div>'
                    )
                );
            }
        }

        return $form;
    }

    public function getList()
    {
        $list = parent::getList();

        if ($this->modelClass === PasskeyCredential::class) {
            // Sort by most recently used and created
            $list = $list->sort(['LastUsed' => 'DESC', 'Created' => 'DESC']);
        }

        return $list;
    }
}
