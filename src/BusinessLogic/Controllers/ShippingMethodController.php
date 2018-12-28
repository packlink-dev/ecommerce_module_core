<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Controllers\DTO\FixedPricePolicy;
use Packlink\BusinessLogic\Controllers\DTO\PercentPricePolicy;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethod;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodResponse;
use Packlink\BusinessLogic\ShippingMethod\Models\FixedPricePolicy as FixedPricePolicyModel;
use Packlink\BusinessLogic\ShippingMethod\Models\PercentPricePolicy as PercentPricePolicyModel;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod as ShippingMethodModel;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class ShippingMethodController
 * @package Packlink\BusinessLogic\Controllers
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
        ShippingMethodModel::PRICING_POLICY_PACKLINK,
        ShippingMethodModel::PRICING_POLICY_PERCENT,
        ShippingMethodModel::PRICING_POLICY_FIXED,
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
     * @param ShippingMethod $shippingMethod Shipping method object.
     *
     * @return bool Returns true when method is saved, false otherwise.
     */
    public function save(ShippingMethod $shippingMethod)
    {
        if (!$this->isValid($shippingMethod)) {
            return false;
        }

        $model = $this->shippingMethodService->getShippingMethod($shippingMethod->id);
        if (!$model) {
            Logger::logError("Shipping method with id {$shippingMethod->id} not found!");

            return false;
        }

        $result = true;
        try {
            $this->updateModelData($shippingMethod, $model);
            $this->shippingMethodService->save($model);
        } catch (\Exception $e) {
            Logger::logError($e->getMessage(), 'Core', $shippingMethod->toArray());
            $result = false;
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
        $model = $this->shippingMethodService->getShippingMethod($id);
        if (!$model) {
            Logger::logError("Shipping method with id {$id} not found!");

            return false;
        }

        return $this->shippingMethodService->activate($model->getServiceId());
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
        $model = $this->shippingMethodService->getShippingMethod($id);
        if (!$model) {
            Logger::logError("Shipping method with id {$id} not found!");

            return false;
        }

        return $this->shippingMethodService->deactivate($model->getServiceId());
    }

    /**
     * Transforms ShippingMethod model class to ShippingMethod DTO.
     *
     * @param ShippingMethodModel $item Shipping method model to be transformed.
     *
     * @return ShippingMethodResponse Shipping method DTO.
     */
    private function transformShippingMethodModelToDto(ShippingMethodModel $item)
    {
        $shippingMethod = new ShippingMethodResponse();
        $shippingMethod->id = $item->getId();
        $shippingMethod->selected = $item->isActivated();
        $shippingMethod->logoUrl = $item->getLogoUrl();
        $shippingMethod->showLogo = $item->isDisplayLogo();
        $shippingMethod->title = $item->isNational() ? static::NATIONAL : static::INTERNATIONAL;
        $shippingMethod->deliveryType = $item->isExpressDelivery() ? static::EXPRESS : static::ECONOMIC;
        $shippingMethod->name = $item->getTitle();
        $shippingMethod->parcelDestination = $item->isDestinationDropOff() ? static::DROP_OFF : static::PICKUP;
        $shippingMethod->parcelOrigin = $item->isDepartureDropOff() ? static::DROP_OFF : static::PICKUP;

        $pricingPolicy = $item->getPricingPolicy();
        $percentPolicy = $item->getPercentPricePolicy();
        $shippingMethod->pricePolicy = $pricingPolicy;
        if ($pricingPolicy === ShippingMethodModel::PRICING_POLICY_PERCENT && $percentPolicy) {
            $shippingMethod->percentPricePolicy = new PercentPricePolicy();
            $shippingMethod->percentPricePolicy->amount = $percentPolicy->amount;
            $shippingMethod->percentPricePolicy->increase = $percentPolicy->increase;
        }

        $fixedPolicy = $item->getFixedPricePolicy();
        if ($pricingPolicy === ShippingMethodModel::PRICING_POLICY_FIXED && !empty($fixedPolicy)) {
            $shippingMethod->fixedPricePolicy = array();
            foreach ($fixedPolicy as $fixed) {
                $fixedDto = new FixedPricePolicy();
                $fixedDto->amount = $fixed->amount;
                $fixedDto->to = $fixed->to;
                $fixedDto->from = $fixed->from;

                $shippingMethod->fixedPricePolicy[] = $fixedDto;
            }
        }

        return $shippingMethod;
    }

    /**
     * Validates shipping method data.
     *
     * @param ShippingMethod $data Shipping method data object.
     *
     * @return bool Returns true if shipping method data is valid, false otherwise.
     */
    private function isValid(ShippingMethod $data)
    {
        if (!isset($data->id, $data->name, $data->showLogo, $data->pricePolicy)) {
            return false;
        }

        if (!is_bool($data->showLogo) || !in_array($data->pricePolicy, static::$policies, false)) {
            return false;
        }

        if ($data->pricePolicy === ShippingMethodModel::PRICING_POLICY_PERCENT && !isset($data->percentPricePolicy)) {
            return false;
        }

        if ($data->pricePolicy === ShippingMethodModel::PRICING_POLICY_FIXED && empty($data->fixedPricePolicy)) {
            return false;
        }

        return true;
    }

    /**
     * Updates model data from data transfer object.
     *
     * @param ShippingMethod $shippingMethod Shipping method DTO.
     * @param ShippingMethodModel $model Shipping method model.
     */
    private function updateModelData(ShippingMethod $shippingMethod, ShippingMethodModel $model)
    {
        $model->setTitle($shippingMethod->name);
        $model->setDisplayLogo($shippingMethod->showLogo);
        switch ($shippingMethod->pricePolicy) {
            case ShippingMethodModel::PRICING_POLICY_PACKLINK:
                $model->setPacklinkPricePolicy();
                break;
            case ShippingMethodModel::PRICING_POLICY_PERCENT:
                $model->setPercentPricePolicy(
                    new PercentPricePolicyModel(
                        $shippingMethod->percentPricePolicy->increase,
                        $shippingMethod->percentPricePolicy->amount
                    )
                );
                break;
            case ShippingMethodModel::PRICING_POLICY_FIXED:
                $policies = array();
                foreach ($shippingMethod->fixedPricePolicy as $item) {
                    $policies[] = new FixedPricePolicyModel($item->from, $item->to, $item->amount);
                }

                $model->setFixedPricePolicy($policies);
                break;
        }
    }
}
