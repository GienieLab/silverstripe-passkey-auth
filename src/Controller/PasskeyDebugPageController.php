<?php

namespace GienieLab\PasskeyAuth\Controller;

use SilverStripe\View\Requirements;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\Director;

class PasskeyDebugPageController extends ContentController
{
    public function index()
    {
        // Only allow in dev mode for security
        if (!Director::isDev()) {
            return $this->httpError(404, 'Not found');
        }
        Requirements::css('gienielab/silverstripe-passkey-auth:client/dist/css/styles.css');

        return $this->renderWith(['Includes\PasskeyDebugPage', 'Page']);
    }
}
