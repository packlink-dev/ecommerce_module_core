<?php

namespace Logeecom\Tests\BusinessLogic\CashOnDelivery;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\Brands\Packlink\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Packlink\BusinessLogic\CashOnDelivery\Model\Account;
use Packlink\BusinessLogic\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\CashOnDelivery\Services\CashOnDeliveryService;
use Packlink\BusinessLogic\Http\DTO\CashOnDelivery as CashOnDeliveryDTO;


class CashOnDeliveryServiceTest extends BaseTestWithServices
{
    /** @var RepositoryInterface */
    private $repository;

    /** @var CashOnDeliveryService */
    private $service;

    /**
     * @before
     * @inheritDoc
     * @throws RepositoryNotRegisteredException
     */
    public function before()
    {
        parent::before();

        /** @noinspection PhpUnhandledExceptionInspection */

       RepositoryRegistry::registerRepository(CashOnDelivery::CLASS_NAME, MemoryRepository::getClassName());

       $this->repository = RepositoryRegistry::getRepository(CashOnDelivery::CLASS_NAME);


        $this->service = new CashOnDeliveryService();
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testGetCashOnDeliveryConfigNoEntity()
    {
        $result = $this->service->getCashOnDeliveryConfig();

        $this->assertNull($result);
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testGetCashOnDeliveryConfigReturnEntity()
    {
        $entity = new CashOnDelivery();
        $entity->setSystemId($this->shopConfig->getCurrentSystemId());
        $entity->setEnabled(false);
        $entity->setActive(false);
        $entity->setAccount(new Account());

        $this->repository->save($entity);

        $result = $this->service->getCashOnDeliveryConfig();

        $this->assertFalse($result->isEnabled());
        $this->assertFalse($result->isActive());
        $this->assertNotNull($result->getAccount());
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testEnableSetsEnabledTrue()
    {
        $entity = new CashOnDelivery();
        $entity->setSystemId($this->shopConfig->getCurrentSystemId());
        $entity->setEnabled(false);
        $entity->setActive(true);
        $entity->setAccount(new Account());
        $this->repository->save($entity);

        $result = $this->service->enable();

        $this->assertTrue($result->isEnabled());
        $this->assertTrue($result->isActive());
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testDisableSetsEnabledFalse()
    {
        $entity = new CashOnDelivery();
        $entity->setSystemId($this->shopConfig->getCurrentSystemId());
        $entity->setEnabled(true);
        $entity->setActive(true);
        $entity->setAccount(new Account());
        $this->repository->save($entity);

        $result = $this->service->disable();

        $this->assertFalse($result->isEnabled());
        $this->assertTrue($result->isActive());
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testEnableReturnsNullIfEntityNotFound()
    {
        $result = $this->service->enable();

        $this->assertNull($result);
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testDisableReturnsNullIfEntityNotFound()
    {
        $result = $this->service->disable();

        $this->assertNull($result);
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testSaveConfigCreatesNewEntity()
    {
        /**@var CashOnDeliveryDTO $dto */
        $dto = CashOnDeliveryDTO::fromArray(array(
            'systemId' => $this->shopConfig->getCurrentSystemId(),
            'enabled' => true,
            'active' => true,
            'account' => array('iban' => 'RS35123456789012345678')
        ));

        $id = $this->service->saveConfig($dto);

        $this->assertNotNull($id);
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testSaveConfigUpdatesExistingEntity()
    {
        $entity = new CashOnDelivery();
        $entity->setSystemId($this->shopConfig->getCurrentSystemId());
        $entity->setEnabled(false);
        $entity->setActive(false);
        $entity->setAccount(new Account());

        $this->repository->save($entity);

        /**@var CashOnDeliveryDTO $dto */
        $dto = CashOnDeliveryDTO::fromArray(array(
            'systemId' => $this->shopConfig->getCurrentSystemId(),
            'enabled' => true,
            'active' => true,
            'account' => array('iban' => 'RS35123456789012345678')
        ));

        $id = $this->service->saveConfig($dto);

        $this->assertNotNull($id);
    }

    /**
     * @return void
     * @throws QueryFilterInvalidParamException
     */
    public function testCalculateFeeReturnsPercentageIfGreaterThanMin()
    {
        $fee = $this->service->calculateFee(100, 2.75, 2.35);

        $this->assertEquals(2.75, $fee);
    }

    /**
     * @return void
     * @throws QueryFilterInvalidParamException
     */
    public function testCalculateFeeReturnsMinFeeIfPercentageLower()
    {
        $fee = $this->service->calculateFee(10, 2.75, 2.35);
        $this->assertEquals(2.35, $fee);
    }
}