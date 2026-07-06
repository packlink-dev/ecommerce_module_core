<?php

namespace Logeecom\Tests\BusinessLogic\ShipmentDocument;

use Packlink\BusinessLogic\Order\OrderService;

/**
 * Class TestOrderService.
 *
 * Test double for the methods that ShipmentDocumentService consumes
 * (`isReadyToFetchShipmentLabels` and `getShipmentLabels`). Skips the real
 * constructor so the test does not need to wire up ShopOrderService,
 * CashOnDeliveryServiceInterface, etc.
 *
 * @package Logeecom\Tests\BusinessLogic\ShipmentDocument
 */
class TestOrderService extends OrderService
{
    /**
     * Whether `isReadyToFetchShipmentLabels` should return true.
     *
     * @var bool
     */
    public $isReady = false;
    /**
     * Labels returned from `getShipmentLabels`.
     *
     * @var \Packlink\BusinessLogic\Http\DTO\ShipmentLabel[]
     */
    public $labels = array();
    /**
     * Number of times `getShipmentLabels` was called.
     *
     * @var int
     */
    public $getShipmentLabelsCalls = 0;

    /**
     * Bypass the parent constructor's dependency resolution.
     */
    public function __construct()
    {
    }

    /**
     * @param string $status
     *
     * @return bool
     */
    public function isReadyToFetchShipmentLabels($status)
    {
        return $this->isReady;
    }

    /**
     * @param string $reference
     *
     * @return \Packlink\BusinessLogic\Http\DTO\ShipmentLabel[]
     */
    public function getShipmentLabels($reference)
    {
        $this->getShipmentLabelsCalls++;

        return $this->labels;
    }
}
