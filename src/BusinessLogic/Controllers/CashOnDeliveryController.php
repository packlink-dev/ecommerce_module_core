<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\Http\DTO\CashOnDelivery as CashOnDeliveryDTO;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
use Packlink\BusinessLogic\ShippingMethod\ShippingCostCalculator;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\Subscription\Interfaces\SubscriptionServiceInterface;

class CashOnDeliveryController
{
    /**
     * @var SubscriptionServiceInterface
     */
    protected $subscriptionService;

    /**
     * @var CashOnDeliveryServiceInterface
     */
    protected $cashOnDeliveryService;

    /**
     * @var Configuration
     */
    protected $configuration;

    public function __construct(
    ) {
        $this->subscriptionService = ServiceRegister::getService(SubscriptionServiceInterface::CLASS_NAME);
        $this->cashOnDeliveryService = ServiceRegister::getService(CashOnDeliveryServiceInterface::CLASS_NAME);
        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Calculate COD surcharge fee based on Packlink rules.
     *
     * @param Order $order
     *
     * @return float COD surcharge
     * @throws QueryFilterInvalidParamException
     */
    public function calculateFee($order)
    {
        $cod = $this->cashOnDeliveryService->getCashOnDeliveryConfig();

        if($cod && $cod->getAccount() && $cod->getAccount()->getCashOnDeliveryFee() !== null)
        {
            return $cod->getAccount()->getCashOnDeliveryFee();
        }

        $service = $this->getCheapestService($order->getShippingMethodId(), $order);

        if($service && $service->cashOnDeliveryConfig){
            return $this->cashOnDeliveryService->calculateFee($order->getTotalPrice(),
                $service->cashOnDeliveryConfig->applyPercentageCashOnDelivery,
                $service->cashOnDeliveryConfig->maxCashOnDelivery);
        }

        return null;
    }

    /**
     * Saves COD configuration.
     *
     * @param array $rawData
     *
     * @return int ID of saved entity
     * @throws FrontDtoValidationException
     */
    public function saveConfig(array $rawData)
    {
        $dto = CashOnDeliveryDTO::fromArray($rawData);

        return $this->cashOnDeliveryService->saveConfig($dto);
    }

    /**
     * @return bool
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getAndUpdateSubscription()
    {
        $plusSubscription = $this->subscriptionService->hasPlusSubscription();
        $cashOnDelivery = $this->cashOnDeliveryService->getCashOnDeliveryConfig();

        if (!$plusSubscription && $cashOnDelivery && $cashOnDelivery->isEnabled()) {
            $this->cashOnDeliveryService->disable();
        }

        if ($plusSubscription && $cashOnDelivery && !$cashOnDelivery->isEnabled()) {
            $this->cashOnDeliveryService->enable();
        }

        return $plusSubscription;
    }

    /**
     * @return  CashOnDeliveryDTO|null $entity
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getCashOnDeliveryConfiguration()
    {
        $cashOnDelivery = $this->cashOnDeliveryService->getCashOnDeliveryConfig();


        if(!$cashOnDelivery) {
            return null;
        }


        return $this->mapEntityToDto($cashOnDelivery);
    }

    /**
     * @param $methodId
     * @param $order
     *
     * @return \Packlink\BusinessLogic\ShippingMethod\Models\ShippingService|null
     */
    private function getCheapestService($methodId, $order)
    {
        /** @var ShippingMethodService $shippingService */
        $shippingService = ServiceRegister::getService(ShippingMethodService::CLASS_NAME);
        $shippingMethod = $shippingService->getShippingMethod($methodId);
        if ($shippingMethod !== null) {
            try {
                /** @var \Packlink\BusinessLogic\Warehouse\Warehouse $warehouse */
                $warehouse = $this->configuration->getDefaultWarehouse();
                $address = $order->getShippingAddress();
                return ShippingCostCalculator::getCheapestShippingService(
                    $shippingMethod,
                    $warehouse->country,
                    $warehouse->postalCode,
                    $address->getCountry(),
                    $address->getZipCode(),
                    $this->getPackage($order)
                );

            } catch (\InvalidArgumentException $e) {
                Logger::logWarning(
                    "Invalid service method $methodId selected for order " . $order->getId()
                    . ' because this method does not support order\'s destination country.'
                );
            }
        }

        return null;
    }
    private function getPackage($order)
    {
        $packages = array();

        foreach ($order->getItems() as $item) {
            $quantity = $item->getQuantity() ?: 1;
            for ($i = 0; $i < $quantity; $i++) {
                $packages[] = new Package(
                    $item->getWeight(),
                    $item->getWidth(),
                    $item->getHeight(),
                    $item->getLength()
                );
            }
        }

        /** @var PackageTransformer $transformer */
        $transformer = ServiceRegister::getService(PackageTransformer::CLASS_NAME);
        return array($transformer->transform($packages));
    }

    /**
     * @param CashOnDelivery $entity
     *
     * @return CashOnDeliveryDTO
     */
    private function mapEntityToDto($entity)
    {
        $dto = new CashOnDeliveryDTO();
        $dto->enabled = $entity->isEnabled();
        $dto->active = $entity->isActive();
        $dto->account = $entity->getAccount();

        return $dto;
    }
}