<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Http\CashOnDelivery\Exeption\CashOnDeliveryNotFoundException;
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
     * @throws QueryFilterInvalidParamException
     *
     * @throws CashOnDeliveryNotFoundException
     */
    public function getCashOnDeliveryConfiguration($systemId)
    {
        $plusSubscription = $this->subscriptionService->hasPlusSubscription();

        $cashOnDelivery = $this->cashOnDeliveryService->getCashOnDeliveryConfig($systemId);


        if(!$cashOnDelivery) {
            $cashOnDelivery = $this->cashOnDeliveryService->saveEmptyObject($systemId);
        }

        if(!$plusSubscription && $cashOnDelivery->isEnabled()) {
            $cashOnDelivery = $this->cashOnDeliveryService->disable($systemId);
        }

        if($plusSubscription && !$cashOnDelivery->isEnabled()) {
            $cashOnDelivery =  $this->cashOnDeliveryService->enable($systemId);
        }

        return $this->mapEntityToDto($cashOnDelivery);
    }

    /**
     * @param CashOnDelivery|null $entity
     *
     * @return CashOnDeliveryDTO
     *
     * @throws CashOnDeliveryNotFoundException
     */
    private function mapEntityToDto($entity)
    {
        if ($entity === null) {
            throw new CashOnDeliveryNotFoundException('CashOnDelivery entity not found.');
        }

        $dto = new CashOnDeliveryDTO();
        $dto->enabled = $entity->isEnabled();
        $dto->active = $entity->isActive();
        $dto->account = $entity->getAccount();

        return $dto;
    }
}