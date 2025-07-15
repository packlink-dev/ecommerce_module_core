<?php

namespace Packlink\DemoUI\Services\BusinessLogic;

use Packlink\BusinessLogic\OAuth\Services\OAuthConfiguration;

class OAuthConfigurationService extends OAuthConfiguration
{
    public function getClientId()
    {
        return 'demo-ui-client-id';
    }

    public function getClientSecret()
    {
        return 'demo-ui-client-secret';
    }

    public function getRedirectUri()
    {
        return 'demo-ui-redirect-uri';
    }

    public function getScopes()
    {
        return array('shipment:write', 'user:get');
    }

    public function getDomain()
    {
        return 'demo-ui';
    }

    public function getTenantId()
    {
        return 'demo-ui-tenant-id';
    }

}