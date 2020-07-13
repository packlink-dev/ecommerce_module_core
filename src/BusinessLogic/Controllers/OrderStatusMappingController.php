<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Language\Translator;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;

/**
 * Class OrderStateMappingController
 *
 * @package Packlink\DemoUI\Controllers
 */
class OrderStatusMappingController
{
    /**
     * Gets system order statuses and saved mappings.
     */
    public function getMappings()
    {
        /** @var \Packlink\BusinessLogic\Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        $mappings = $configService->getOrderStatusMappings();

        if (empty($mappings)) {
            foreach ($this->getPacklinkStatuses() as $status => $label) {
                $mappings[$status] = '';
            }
        }

        return $mappings;
    }

    /**
     * Returns Packlink shipment statuses.
     *
     * @return array Packlink shipment statuses
     */
    public function getPacklinkStatuses()
    {
        $result = array();
        foreach (ShipmentStatus::getPossibleStatuses() as $status) {
            $result[$status] = Translator::translate('orderStatusMapping.' . $status);
        }

        return $result;
    }

    /**
     * Saves order status mappings.
     *
     * @param array $data
     */
    public function setMappings($data)
    {
        /** @var \Packlink\BusinessLogic\Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $configService->setOrderStatusMappings($data);
    }
}
