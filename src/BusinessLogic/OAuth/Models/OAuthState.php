<?php

namespace Packlink\BusinessLogic\OAuth\Models;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

class OAuthState extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array(
        'id',
        'tenantId',
        'state'
    );

    /**
     * @var string id
     */
    protected $id;

    /**
     * @var string $tenantId
     */
    protected $tenantId;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @var string $state
     */
    protected $state;

    /**
     * @return EntityConfiguration
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();

        $indexMap->addStringIndex('tenantId')
        ->addStringIndex('state');

        return new EntityConfiguration($indexMap, 'OAuthState');
    }
}