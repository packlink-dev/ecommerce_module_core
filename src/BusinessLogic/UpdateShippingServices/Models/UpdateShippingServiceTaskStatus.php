<?php

namespace Packlink\BusinessLogic\UpdateShippingServices\Models;


use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

class UpdateShippingServiceTaskStatus extends Entity
{
	const CLASS_NAME = __CLASS__;

	const TYPE = 'UpdateShippingServiceTaskStatus';

    /**
     * @var int|null
     */
    protected $id = null;

    /**
     * @var string|null
     */
    protected $context = null;

    /**
     * @var string|null
     */
    protected $status = null;

    /**
     * @var string|null
     */
    protected $error = null;

    /**
     * @var int|null
     */
    protected $createdAt = null;

    /**
     * @var int|null
     */
    protected $updatedAt = null;

    /**
     * @var int|null
     */
    protected $finishedAt = null;

    /**
     * @var int|null
     */
    protected $executionId = null;


    /**
     * @var array
     */
    protected $fields = array(
        'id',
        'context',
        'status',
        'error',
        'createdAt',
        'updatedAt',
        'finishedAt',
        'executionId',
    );

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param string|null $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string|null $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return int|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param int|null $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return int|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param int|null $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return int|null
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    /**
     * @param int|null $finishedAt
     */
    public function setFinishedAt($finishedAt)
    {
        $this->finishedAt = $finishedAt;
    }

    /**
     * @return int|null
     */
    public function getExecutionId()
    {
        return $this->executionId;
    }

    /**
     * @param int|null $executionId
     */
    public function setExecutionId($executionId)
    {
        $this->executionId = $executionId;
    }


    /**
     * @return EntityConfiguration
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();

        $indexMap->addStringIndex('context');
        $indexMap->addStringIndex('status');
        $indexMap->addIntegerIndex('createdAt');
        $indexMap->addIntegerIndex('executionId');

        return new EntityConfiguration($indexMap, self::TYPE);
    }
}