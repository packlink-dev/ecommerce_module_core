<?php

namespace Packlink\BusinessLogic\CashOnDelivery\Services;

use Packlink\BusinessLogic\ShippingMethod\Models\CashOnDeliveryConfig;
use Packlink\BusinessLogic\Http\DTO\CashOnDelivery;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;

abstract class OfflinePaymentsServices
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;


    /**
     * Gets active offline payment methods.
     *
     * @return array
     */
    abstract public function getOfflinePayments();

    /**
     * @param ShippingService[] $services
     * @param string $fromCountry
     * @param string $toCountry
     *
     * @return ShippingService|null
     */
    public function getCheapestMatchingService($services, $fromCountry, $toCountry)
    {
        $chosen_service = null;

        foreach ($services as $service) {
            if ($service->departureCountry === $fromCountry && $service->destinationCountry === $toCountry) {
                if ($chosen_service === null || $service->basePrice < $chosen_service->basePrice) {
                    $chosen_service = $service;
                }
            }
        }

        return $chosen_service;
    }

    /**
     * @param CashOnDelivery|null $accountConfig
     * @param CashOnDeliveryConfig|null $serviceConfig
     *
     * @return bool
     */
    public function shouldBeHiddenPaymentMethod($accountConfig, $serviceConfig)
    {
        return $serviceConfig &&
            !$serviceConfig->offered
            && $accountConfig !== null
            && $accountConfig->enabled
            && $accountConfig->active
            && $accountConfig->account;
    }

    /**
     * @param CashOnDelivery $accountConfig
     * @param string $paymentMethod
     *
     * @return bool
     */
    public function surchargeCondition($accountConfig, $paymentMethod)
    {
        return $accountConfig &&
            $accountConfig->account
            && $accountConfig->enabled
            && $accountConfig->active
            && $accountConfig->account->getOfflinePaymentMethod() === $paymentMethod;
    }
}