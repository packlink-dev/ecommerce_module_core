<?php

namespace Packlink\BusinessLogic\ShipmentDraft\Models;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

/**
 * Class OrderSendTaskDraftMap.
 *
 * @package Packlink\BusinessLogic\ShipmentDraft\Models
 */
class OrderSendDraftTaskMap extends Entity
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
        'orderId',
        'executionId',
    );
    /**
     * Shop order ID.
     *
     * @var string
     */
    protected $orderId;
    /**
     * Task execution ID.
     *
     * @var int|null
     */
    protected $executionId;

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $map = new IndexMap();

        $map->addStringIndex('orderId');
        $map->addIntegerIndex('executionId');

        return new EntityConfiguration($map, 'OrderSendDraftTaskMap');
    }

    /**
     * Gets Order Id.
     *
     * @return string Order Id.
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Sets Order Id.
     *
     * @param string $orderId Order Id.
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Gets the ID of the task execution.
     *
     * @return int|null ID of the task execution.
     */
    public function getExecutionId()
    {
        return $this->executionId;
    }

    /**
     * Sets ID of the task execution.
     *
     * @param int $executionId ID of the task execution.
     */
    public function setExecutionId($executionId)
    {
        $this->executionId = $executionId;
    }
}
