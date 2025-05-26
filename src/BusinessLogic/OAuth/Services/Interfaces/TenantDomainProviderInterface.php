<?php

namespace Packlink\BusinessLogic\OAuth\Services\Interfaces;

interface TenantDomainProviderInterface
{
    const CLASS_NAME = __CLASS__;

    public static function getDomain($tenantCode);

    public static function getAllowedCountries();
}