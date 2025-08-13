<?php

namespace Controllers;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\CashOnDelivery\TestCashOnDeliveryService;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Subscription\TestSubscriptionService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Packlink\BusinessLogic\Controllers\CashOnDeliveryController;
use Packlink\BusinessLogic\Http\CashOnDelivery\Exeption\CashOnDeliveryNotFoundException;
use Packlink\BusinessLogic\Http\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\Http\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\Http\Subscription\Interfaces\SubscriptionServiceInterface;

class CashOnDeliveryControllerTest extends BaseTestWithServices
{
    /** @var RepositoryInterface */
    private $repository;


    /** @var TestCashOnDeliveryService $cashOnDeliveryService*/

    private $cashOnDeliveryService;


    /** @var TestSubscriptionService $subscriptionService*/
    private $subscriptionService;

    /** @var CashOnDeliveryController */
    private $controller;

    /**
     * @before
     * @inheritDoc
     * @throws RepositoryNotRegisteredException
     */
    public function before()
    {
        parent::before();

        $me = $this;

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(CashOnDelivery::CLASS_NAME, MemoryRepository::getClassName());

        $this->subscriptionService = new TestSubscriptionService();
        ServiceRegister::registerService(
            SubscriptionServiceInterface::CLASS_NAME,
            function () use ($me) {
                return $me->subscriptionService;
            }
        );

        $this->cashOnDeliveryService = new TestCashOnDeliveryService();
        ServiceRegister::registerService(
            CashOnDeliveryServiceInterface::CLASS_NAME,
            function () use ($me) {
                return $me->cashOnDeliveryService;
            }
        );

        $this->repository = RepositoryRegistry::getRepository(CashOnDelivery::CLASS_NAME);

        $this->controller = new CashOnDeliveryController();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws CashOnDeliveryNotFoundException
     */
    public function testCreatesEmptyConfigWhenNoneExists()
    {
        $this->subscriptionService->setValue(false);
        $this->cashOnDeliveryService->setEntity(null);

        $dto = $this->controller->getCashOnDeliveryConfiguration('sys-1');

        $this->assertFalse($dto->enabled);
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws CashOnDeliveryNotFoundException
     */
    public function testDisablesWhenNoPlusSubscription()
    {
        $entity = new CashOnDelivery();
        $entity->setSystemId('sys-2');
        $entity->setEnabled(true);

        $this->cashOnDeliveryService->setEntity($entity);
        $this->subscriptionService->setValue(false);

        $dto = $this->controller->getCashOnDeliveryConfiguration('sys-2');

        $this->assertFalse($dto->enabled);
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws CashOnDeliveryNotFoundException
     */
    public function testEnablesWhenPlusSubscription()
    {
        $entity = new CashOnDelivery();
        $entity->setSystemId('sys-3');
        $entity->setEnabled(false);

        $this->cashOnDeliveryService->setEntity($entity);
        $this->subscriptionService->setValue(true);

        $dto = $this->controller->getCashOnDeliveryConfiguration('sys-3');

        $this->assertTrue($dto->enabled);
    }

}