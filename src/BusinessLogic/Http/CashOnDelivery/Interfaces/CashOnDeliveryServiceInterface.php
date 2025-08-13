<?php

namespace Packlink\BusinessLogic\Http\CashOnDelivery\Interfaces;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Packlink\BusinessLogic\Http\CashOnDelivery\Model\CashOnDelivery;

interface CashOnDeliveryServiceInterface
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves the Cash on Delivery configuration for the given system ID.
     *
     * @param string $systemId
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getCashOnDeliveryConfig($systemId);

    /**
     * Creates an empty CashOnDelivery entity and stores it in the database.
     *
     * @param string $systemId
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function saveEmptyObject($systemId);

    /**
     * Disables the CashOnDelivery for the given system ID.
     *
     * @param string $systemId
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function disable($systemId);

    /**
     * Enables the CashOnDelivery for the given system ID.
     *
     * @param string $systemId
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function enable($systemId);

}