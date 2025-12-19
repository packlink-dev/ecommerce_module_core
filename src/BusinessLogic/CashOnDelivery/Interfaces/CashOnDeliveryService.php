<?php

namespace Packlink\BusinessLogic\CashOnDelivery\Interfaces;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Packlink\BusinessLogic\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\Http\DTO\CashOnDelivery as CashOnDeliveryDTO;

interface CashOnDeliveryService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Calculate COD surcharge fee if it is not set in the configuration than use from api.
     *
     * @param float $orderTotal Total order amount
     * @param float $percentage Percentage fee
     * @param float $minFee Minimum fee
     *
     * @return float COD surcharge
     * @throws QueryFilterInvalidParamException
     */
    public function calculateFee($orderTotal, $percentage, $minFee);

    /**
     * Retrieves the Cash on Delivery configuration for the given system ID.
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getCashOnDeliveryConfig();

    /**
     * @param CashOnDeliveryDTO $dto
     * @return int
     * @throws QueryFilterInvalidParamException
     */
    public function saveConfig(CashOnDeliveryDTO $dto);

    /**
     * Disables the CashOnDeliveryController for the given system ID.
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function disable();

    /**
     * Enables the CashOnDeliveryController for the given system ID.
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function enable();

}