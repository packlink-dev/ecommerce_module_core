<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\Http\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\Http\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\Http\Subscription\Interfaces\SubscriptionServiceInterface;
use Packlink\BusinessLogic\Http\DTO\CashOnDelivery as CashOnDeliveryDTO;

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

    public function __construct(
    ) {
        $this->subscriptionService = ServiceRegister::getService(SubscriptionServiceInterface::CLASS_NAME);
        $this->cashOnDeliveryService = ServiceRegister::getService(CashOnDeliveryServiceInterface::CLASS_NAME);
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

        if (!$cashOnDelivery && $plusSubscription) {
            $cashOnDelivery = $this->cashOnDeliveryService->saveEmptyObject();
        }

        if (!$plusSubscription && $cashOnDelivery->isEnabled()) {
            $this->cashOnDeliveryService->disable();
        }

        if ($plusSubscription && !$cashOnDelivery->isEnabled()) {
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