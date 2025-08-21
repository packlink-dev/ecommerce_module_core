<?php

namespace Packlink\BusinessLogic\CashOnDelivery\Interfaces;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Packlink\BusinessLogic\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\Http\DTO\CashOnDelivery as CashOnDeliveryDTO;

interface CashOnDeliveryServiceInterface
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

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