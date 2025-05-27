<?php

namespace Packlink\BusinessLogic\OAuth\Models;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

class OAuthInfo extends Entity
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var array
     */
    protected $fields = array(
        'id',
        'tenantId',
        'accessToken',
        'refreshToken',
        'expiresIn',
        'createdAt'
    );

    /** @var string */
    protected $id;

    /** @var string */
    protected $tenantId;

    /** @var string */
    protected $accessToken;

    /** @var string */
    protected $refreshToken;

    /** @var int */
    protected $expiresIn;

    /** @var int */
    protected $createdAt;

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getTenantId()
    {
        return $this->tenantId;
    }

    public function setTenantId($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    public function setExpiresIn($expiresIn)
    {
        $this->expiresIn = $expiresIn;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($timestamp)
    {
        $this->createdAt = $timestamp;
    }

    /**
     * @return EntityConfiguration
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();
        $indexMap->addStringIndex('tenantId');

        return new EntityConfiguration($indexMap, 'OAuthInfo');
    }
}
