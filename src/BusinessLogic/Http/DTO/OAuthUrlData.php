<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

class OAuthUrlData extends DataTransferObject
{
    /**
     * @var string
     */
    private $tenantId;
    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var array
     */
    private $scopes;

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param string $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @var string
     */
    private $domain;

    /**
     * OAuthUrlData constructor.
     *
     * @param string $tenantId
     * @param string $clientId
     * @param string $redirectUri
     * @param array $scopes
     * @param string $domain
     */
    public function __construct($tenantId, $clientId, $redirectUri, $scopes, $domain, $clientSecret)
    {
        $this->tenantId = $tenantId;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->scopes = $scopes;
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getTenantId()
    {
        return $this->tenantId;
    }

    /**
     * @param string $tenantId
     */
    public function setTenantId($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @param string $redirectUri
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    /**
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @param array $scopes
     */
    public function setScopes($scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'tenantId' => $this->tenantId,
            'clientId' => $this->clientId,
            'redirectUri' => $this->redirectUri,
            'scopes' => $this->scopes,
            'domain' => $this->domain,
        );
    }
}