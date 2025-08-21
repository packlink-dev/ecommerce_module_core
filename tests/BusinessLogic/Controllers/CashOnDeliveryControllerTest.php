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
use Packlink\BusinessLogic\Http\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\Http\CashOnDelivery\Model\Account;
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
     */
    public function testGetCODNoneExists()
    {
        $dto = $this->controller->getCashOnDeliveryConfiguration();

        $this->assertNull($dto);
    }

    public function testGetCashOnDelivery()
    {
        $entity = new CashOnDelivery();
        $entity->setSystemId($this->shopConfig->getCurrentSystemId());
        $entity->setEnabled(true);

        $this->cashOnDeliveryService->setEntity($entity);

        $dto = $this->controller->getCashOnDeliveryConfiguration();

        $this->assertTrue($dto->enabled);
    }

    public function testSaveConfigCreatesEntity()
    {
        $rawData = array(
            'systemId' => $this->shopConfig->getCurrentSystemId(),
            'enabled' => true,
            'active' => true,
            'account' => array('iban' => 'RS35123456789012345678'),
        );

        $id = $this->controller->saveConfig($rawData);

        $this->assertNotNull($id);

        $entity = $this->cashOnDeliveryService->getCashOnDeliveryConfig();
        $this->assertTrue($entity->isEnabled());
        $this->assertTrue($entity->isActive());
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testGetAndUpdateSubscriptionCreatesEntityIfNoneExistsAndPlusSubscription()
    {
        $this->subscriptionService->setValue(true);

        $result = $this->controller->getAndUpdateSubscription();

        $this->assertTrue($result);
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testGetAndUpdateSubscriptionDisablesWhenNoSubscription()
    {
        $entity = new CashOnDelivery();
        $entity->setSystemId($this->shopConfig->getCurrentSystemId());
        $entity->setEnabled(true);
        $entity->setActive(true);
        $entity->setAccount(new Account());

        $this->cashOnDeliveryService->setEntity($entity);
        $this->subscriptionService->setValue(false);

        $result = $this->controller->getAndUpdateSubscription();

        $this->assertFalse($result);
        $this->assertFalse($this->cashOnDeliveryService->getCashOnDeliveryConfig()->isEnabled());
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testGetAndUpdateSubscriptionEnablesWhenHasSubscription()
    {
        $entity = new CashOnDelivery();
        $entity->setSystemId($this->shopConfig->getCurrentSystemId());
        $entity->setEnabled(false);
        $entity->setActive(true);
        $entity->setAccount(new Account());

        $this->cashOnDeliveryService->setEntity($entity);
        $this->subscriptionService->setValue(true);

        $result = $this->controller->getAndUpdateSubscription();

        $this->assertTrue($result);
        $this->assertTrue($this->cashOnDeliveryService->getCashOnDeliveryConfig()->isEnabled());
    }
}