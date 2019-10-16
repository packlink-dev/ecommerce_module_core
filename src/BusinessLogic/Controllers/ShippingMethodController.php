<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodResponse;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

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
     * Home constant
     */
    const HOME = 'home';
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
     * Allowed policies.
     *
     * @var array
     */
    private static $policies = array(
        ShippingMethod::PRICING_POLICY_PACKLINK,
        ShippingMethod::PRICING_POLICY_PERCENT,
        ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT,
        ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE,
    );
    /**
     * Shipping method service.
     *
     * @var ShippingMethodService
     */
    private $shippingMethodService;

    /**
     * DashboardController constructor.
     */
    public function __construct()
    {
        $this->shippingMethodService = ServiceRegister::getService(ShippingMethodService::CLASS_NAME);
    }

    /**
     * Returns all shipping methods.
     *
     * @return ShippingMethodResponse[] Array of shipping methods.
     */
    public function getAll()
    {
        $all = $this->shippingMethodService->getAllMethods();
        $result = array();
        foreach ($all as $item) {
            $result[] = $this->transformShippingMethodModelToDto($item);
        }

        return $result;
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
        if (!$this->isValid($shippingMethod)) {
            return null;
        }

        $model = $this->shippingMethodService->getShippingMethod($shippingMethod->id);
        if (!$model) {
            Logger::logError("Shipping method with id {$shippingMethod->id} not found!");

            return null;
        }

        try {
            $this->updateModelData($shippingMethod, $model);
            $this->shippingMethodService->save($model);

            return $this->transformShippingMethodModelToDto($model);
        } catch (\Exception $e) {
            Logger::logError($e->getMessage(), 'Core', $shippingMethod->toArray());
            $result = null;
        }

        return $result;
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
        $shippingMethod->selected = $item->isActivated();
        $shippingMethod->logoUrl = $item->getLogoUrl();
        $shippingMethod->showLogo = $item->isDisplayLogo();
        $shippingMethod->title = $item->isNational() ? static::NATIONAL : static::INTERNATIONAL;
        $shippingMethod->carrierName = $item->getCarrierName();
        $shippingMethod->deliveryDescription = ($item->isExpressDelivery() ? 'Express' : 'Economic') . ' '
            . $item->getDeliveryTime();
        $shippingMethod->deliveryType = $item->isExpressDelivery() ? static::EXPRESS : static::ECONOMIC;
        $shippingMethod->name = $item->getTitle();
        $shippingMethod->parcelDestination = $item->isDestinationDropOff() ? static::DROP_OFF : static::HOME;
        $shippingMethod->parcelOrigin = $item->isDepartureDropOff() ? static::DROP_OFF : static::PICKUP;
        $shippingMethod->taxClass = $item->getTaxClass();
        $shippingMethod->shippingCountries = $item->getShippingCountries();
        $shippingMethod->isShipToAllCountries = $item->isShipToAllCountries();

        $shippingMethod->pricePolicy = $item->getPricingPolicy();
        $shippingMethod->percentPricePolicy = $item->getPercentPricePolicy();
        $shippingMethod->fixedPriceByWeightPolicy = $item->getFixedPriceByWeightPolicy();
        $shippingMethod->fixedPriceByValuePolicy = $item->getFixedPriceByValuePolicy();

        return $shippingMethod;
    }

    /**
     * Validates shipping method data.
     *
     * @param ShippingMethodConfiguration $data Shipping method data object.
     *
     * @return bool Returns true if shipping method data is valid, false otherwise.
     */
    private function isValid(ShippingMethodConfiguration $data)
    {
        return !(!isset($data->id, $data->name, $data->showLogo, $data->pricePolicy)
            || ($data->pricePolicy === ShippingMethod::PRICING_POLICY_PERCENT && !isset($data->percentPricePolicy))
            || ($data->pricePolicy === ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT
                && empty($data->fixedPriceByWeightPolicy))
            || ($data->pricePolicy === ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE
                && empty($data->fixedPriceByValuePolicy))
            || (!is_bool($data->showLogo) || !in_array($data->pricePolicy, static::$policies, false)));
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
        switch ($configuration->pricePolicy) {
            case ShippingMethod::PRICING_POLICY_PACKLINK:
                $model->setPacklinkPricePolicy();
                break;
            case ShippingMethod::PRICING_POLICY_PERCENT:
                $model->setPercentPricePolicy($configuration->percentPricePolicy);
                break;
            case ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT:
                $model->setFixedPriceByWeightPolicy($configuration->fixedPriceByWeightPolicy);
                break;
            case ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE:
                $model->setFixedPriceByValuePolicy($configuration->fixedPriceByValuePolicy);
                break;
        }
    }
}
