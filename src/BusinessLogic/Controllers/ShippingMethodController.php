<?php

namespace Packlink\BusinessLogic\Controllers;

use Exception;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodResponse;
use Packlink\BusinessLogic\Language\Translator;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService;

/**
 * Class ShippingMethodController.
 *
 * @package Packlink\PacklinkPro\IntegrationCore\BusinessLogic\Controllers
 */
class ShippingMethodController
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Drop-off constant
     */
    const DROP_OFF = 'dropoff';
    /**
     * Pickup constant
     */
    const PICKUP = 'pickup';
    /**
     * Collection constant
     */
    const COLLECTION = 'collection';
    /**
     * Home constant
     */
    const DELIVERY = 'delivery';
    /**
     * Shipping type: national
     */
    const NATIONAL = 'national';
    /**
     * Shipping type: international
     */
    const INTERNATIONAL = 'international';
    /**
     * Shipping delivery type: express
     */
    const EXPRESS = 'express';
    /**
     * Shipping delivery type: economic
     */
    const ECONOMIC = 'economic';
    /**
     * Shipping method service.
     *
     * @var ShippingMethodService
     */
    private $shippingMethodService;
    /**
     * Shop shipping method service.
     *
     * @var ShopShippingMethodService
     */
    private $shopShippingService;
    /**
     * @var SystemInfoService
     */
    private $systemInfoService;

    /**
     * DashboardController constructor.
     */
    public function __construct()
    {
        $this->shippingMethodService = ServiceRegister::getService(ShippingMethodService::CLASS_NAME);
        $this->shopShippingService = ServiceRegister::getService(ShopShippingMethodService::CLASS_NAME);
        $this->systemInfoService = ServiceRegister::getService(SystemInfoService::CLASS_NAME);
    }

    /**
     * Returns all shipping methods.
     *
     * @return ShippingMethodResponse[] Array of shipping methods.
     */
    public function getAll()
    {
        return $this->getResponse($this->shippingMethodService->getAllMethods());
    }

    /**
     * Returns all shipping methods.
     *
     * @return ShippingMethodResponse[] Array of shipping methods.
     */
    public function getActive()
    {
        return $this->getResponse($this->shippingMethodService->getActiveMethods());
    }

    /**
     * Returns all shipping methods.
     *
     * @return ShippingMethodResponse[] Array of shipping methods.
     */
    public function getInactive()
    {
        return $this->getResponse($this->shippingMethodService->getInactiveMethods());
    }

    /**
     * Returns shipping method with the given ID.
     *
     * @param int $id Shipping method ID.
     *
     * @return ShippingMethodResponse|null Shipping method.
     */
    public function getShippingMethod($id)
    {
        $model = $this->shippingMethodService->getShippingMethod($id);
        if (!$model) {
            Logger::logWarning("Shipping method with id {$id} not found!");

            return null;
        }

        return $this->transformShippingMethodModelToDto($model);
    }

    /**
     * Validates and stores shipping method.
     *
     * @param ShippingMethodConfiguration $shippingMethod Shipping method object.
     *
     * @return ShippingMethodResponse | null Returns ShippingMethod object when method is saved, null otherwise.
     */
    public function save(ShippingMethodConfiguration $shippingMethod)
    {
        $model = $this->shippingMethodService->getShippingMethod($shippingMethod->id);
        if (!$model) {
            Logger::logError("Shipping method with id {$shippingMethod->id} not found!");

            return null;
        }

        $details = $this->systemInfoService->getSystemDetails();
        foreach ($details as $detail) {
            if (!$this->shippingMethodService->isCurrencyConfigurationValidForSingleStore($detail, $shippingMethod, $model)) {
                return null;
            }
        }

        try {
            $isFirstServiceActivated = $shippingMethod->activated && !$this->shippingMethodService->isAnyMethodActive();

            $this->updateModelData($shippingMethod, $model);
            $this->shippingMethodService->save($model);

            $result = $this->transformShippingMethodModelToDto($model);

            if ($isFirstServiceActivated) {
                AnalyticsController::sendSetupEvent();
                $this->shopShippingService->addBackupShippingMethod(ShippingMethod::fromArray($model->toArray()));
            }

            return $result;
        } catch (Exception $e) {
            Logger::logError($e->getMessage(), 'Core', $shippingMethod->toArray());
        }

        return null;
    }

    /**
     * Activates shipping method with provided Id.
     *
     * @param int $id Shipping method identifier.
     *
     * @return bool Returns true if shipping method is activated, false otherwise.
     */
    public function activate($id)
    {
        if ($this->shippingMethodService->activate($id)) {
            AnalyticsController::sendSetupEvent();

            return true;
        }

        return false;
    }

    /**
     * Deactivates shipping method with provided Id.
     *
     * @param int $id Shipping method identifier.
     *
     * @return bool Returns true if shipping method is deactivated, false otherwise.
     */
    public function deactivate($id)
    {
        return $this->shippingMethodService->deactivate($id);
    }

    /**
     * Transforms shipping methods to the response.
     *
     * @param ShippingMethod[] $methods Shipping methods to transform.
     *
     * @return ShippingMethodResponse[] Array of shipping methods.
     */
    protected function getResponse($methods)
    {
        $result = array();
        foreach ($methods as $item) {
            $result[] = $this->transformShippingMethodModelToDto($item);
        }

        return $result;
    }

    /**
     * Transforms ShippingMethod model class to ShippingMethod DTO.
     *
     * @param ShippingMethod $item Shipping method model to be transformed.
     *
     * @return ShippingMethodResponse Shipping method DTO.
     */
    private function transformShippingMethodModelToDto(ShippingMethod $item)
    {
        $shippingMethod = new ShippingMethodResponse();
        $shippingMethod->id = $item->getId();
        $shippingMethod->activated = $item->isActivated();
        $shippingMethod->name = $item->getTitle();
        $shippingMethod->logoUrl = $item->getLogoUrl();
        $shippingMethod->showLogo = $item->isDisplayLogo();
        $shippingMethod->type = $item->isNational() ? static::NATIONAL : static::INTERNATIONAL;
        $shippingMethod->carrierName = $item->getCarrierName();
        $shippingMethod->deliveryDescription = mb_strtolower($item->getDeliveryTime()) . ' - '
            . Translator::translate(
                'shippingServices.' . ($item->isExpressDelivery() ? static::EXPRESS : static::ECONOMIC)
            );
        $shippingMethod->deliveryType = $item->isExpressDelivery() ? static::EXPRESS : static::ECONOMIC;
        $shippingMethod->parcelOrigin = $item->isDepartureDropOff() ? static::DROP_OFF : static::COLLECTION;
        $shippingMethod->parcelDestination = $item->isDestinationDropOff() ? static::PICKUP : static::DELIVERY;
        $shippingMethod->taxClass = $item->getTaxClass();
        $shippingMethod->shippingCountries = $item->getShippingCountries();
        $shippingMethod->isShipToAllCountries = $item->isShipToAllCountries();
        $shippingMethod->pricingPolicies = $item->getPricingPolicies();
        $shippingMethod->usePacklinkPriceIfNotInRange = $item->isUsePacklinkPriceIfNotInRange();
        $shippingMethod->currency = $item->getCurrency();
        $shippingMethod->fixedPrices = $item->getFixedPrices();
        $shippingMethod->systemDefaults = $item->getSystemDefaults();

        return $shippingMethod;
    }

    /**
     * Updates model data from data transfer object.
     *
     * @param ShippingMethodConfiguration $configuration Shipping method DTO.
     * @param ShippingMethod $model Shipping method model.
     */
    private function updateModelData(ShippingMethodConfiguration $configuration, ShippingMethod $model)
    {
        $model->setTitle($configuration->name);
        $model->setDisplayLogo($configuration->showLogo);
        $model->setTaxClass($configuration->taxClass);
        $model->setShipToAllCountries($configuration->isShipToAllCountries);
        $model->setShippingCountries($configuration->shippingCountries);
        $model->setActivated($configuration->activated);
        $model->setUsePacklinkPriceIfNotInRange($configuration->usePacklinkPriceIfNotInRange);
        $model->setFixedPrices($configuration->fixedPrices);
        $model->setSystemDefaults($configuration->systemDefaults);
        $this->updatePricingPolicies($configuration, $model);
    }

    /**
     * Updates pricing policies on the shipping method model.
     *
     * @param ShippingMethodConfiguration $configuration
     * @param ShippingMethod $model
     */
    private function updatePricingPolicies(ShippingMethodConfiguration $configuration, ShippingMethod $model)
    {
        $model->resetPricingPolicies();
        foreach ($configuration->pricingPolicies as $policy) {
            $model->addPricingPolicy($policy);
        }
    }
}
