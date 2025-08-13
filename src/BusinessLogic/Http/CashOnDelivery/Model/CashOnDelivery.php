<?php

namespace Packlink\BusinessLogic\Http\CashOnDelivery\Model;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

class CashOnDelivery extends Entity
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var array
     */
    protected $fields = array(
        'id',
        'systemId',
        'enabled',
        'active',
        'account',
    );

    /** @var string */
    protected $id;

    /** @var string */
    protected $systemId;

    /** @var bool */
    protected $enabled = false;

    /** @var bool */
    protected $active = false;

    /** @var Account */
    protected $account;


    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getSystemId()
    {
        return $this->systemId;
    }

    /**
     * @param $systemId
     *
     * @return void
     */
    public function setSystemId($systemId)
    {
        $this->systemId = $systemId;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param $enabled
     * @return void
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param $active
     *
     * @return void
     */
    public function setActive($active)
    {
        $this->active = (bool) $active;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account $account
     *
     * @return void
     */
    public function setAccount(Account $account)
    {
        $this->account = $account;
    }

    /**
     * @return EntityConfiguration
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();

        $indexMap->addStringIndex('systemId');

        return new EntityConfiguration($indexMap, 'CashOnDelivery');
    }
}