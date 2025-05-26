<?php

namespace Packlink\BusinessLogic\OAuth\Services;

use Logeecom\Infrastructure\Singleton;

abstract class OAuthConfiguration extends Singleton
{
    const CLASS_NAME =  __CLASS__;

    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;


    /**
     * Retrieves the client ID from the configuration.
     *
     * @return string
     */
    abstract public function getClientId();

    /**
     * Retrieves the client secret from the configuration.
     *
     * @return string
     */
    abstract public function getClientSecret();

    /**
     * Retrieves the redirect URI from the configuration.
     *
     * @return string
     */
    abstract public function getRedirectUri();

    /**
     * Retrieves the scopes from the configuration.
     *
     * @return array
     */
    abstract public function getScopes();

    /**
     * @return string
     */
    abstract public function getDomain();

    /**
     * @return string
     */
    abstract public function getTenantId();
}