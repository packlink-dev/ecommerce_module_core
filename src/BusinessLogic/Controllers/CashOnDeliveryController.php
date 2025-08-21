<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\Http\DTO\CashOnDelivery as CashOnDeliveryDTO;
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

    public function __construct()
    {
        $this->subscriptionService = ServiceRegister::getService(SubscriptionServiceInterface::CLASS_NAME);
        $this->cashOnDeliveryService = ServiceRegister::getService(CashOnDeliveryServiceInterface::CLASS_NAME);
    }

    /**
     * Saves COD configuration.
     *
     * @param array $rawData
     *
     * @return int ID of saved entity
     *
     * @throws FrontDtoValidationException|QueryFilterInvalidParamException
     */
    public function saveConfig(array $rawData)
    {
        $dto = CashOnDeliveryDTO::fromArray($rawData);

        return $this->cashOnDeliveryService->saveConfig($dto);
    }

    /**
     * Get subscription status and update saved configuration if status changed
     *
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


        if (!$cashOnDelivery) {
            return null;
        }


        return $this->mapEntityToDto($cashOnDelivery);
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