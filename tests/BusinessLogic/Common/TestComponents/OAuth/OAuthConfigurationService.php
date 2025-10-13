<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\OAuth;

use Packlink\BusinessLogic\OAuth\Services\OAuthConfiguration;

class OAuthConfigurationService extends OAuthConfiguration
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    public function __construct()
    {
        parent::__construct();

        static::$instance = $this;
    }

    protected $clientId;

    protected $clientSecret;
    protected $redirectUri;
    protected $scopes;
    protected $tenantId;
    protected $domain;
    protected $returnUrl;

    /**
     * @param mixed $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @param mixed $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @param mixed $redirectUri
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    /**
     * @param mixed $scopes
     */
    public function setScopes($scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * @param mixed $tenantId
     */
    public function setTenantId($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * @param mixed $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @param $returnUrl
     * @return void
     */
    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }


    /**
     * @inheritDoc
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @inheritDoc
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function getReturnUrl()
    {
        return $this->returnUrl;
    }


    public function getTenantId()
    {
        return $this->tenantId;
    }
}