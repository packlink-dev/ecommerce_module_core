<?php

namespace Logeecom\Tests\BusinessLogic\CashOnDelivery;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\Brands\Packlink\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Packlink\BusinessLogic\Http\CashOnDelivery\Model\Account;
use Packlink\BusinessLogic\Http\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\Http\CashOnDelivery\Services\CashOnDeliveryService;

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
        $systemId = 'sys123';

        $result = $this->service->getCashOnDeliveryConfig($systemId);

        $this->assertNull($result);
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testGetCashOnDeliveryConfigReturnEntity()
    {
        $systemId = 'sys123';

        $entity = new CashOnDelivery();
        $entity->setSystemId($systemId);
        $entity->setEnabled(false);
        $entity->setActive(false);
        $entity->setAccount(new Account());

        $this->repository->save($entity);

        $result = $this->service->getCashOnDeliveryConfig($systemId);

        $this->assertSame($systemId, $result->getSystemId());
        $this->assertFalse($result->isEnabled());
        $this->assertFalse($result->isActive());
        $this->assertNotNull($result->getAccount());
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testSaveEmptyObjectCreatesAndReturnsEntity()
    {
        $systemId = 'sys456';
        $result = $this->service->saveEmptyObject($systemId);

        $this->assertSame($systemId, $result->getSystemId());
        $this->assertFalse($result->isEnabled());
        $this->assertFalse($result->isActive());
        $this->assertNotNull($result->getAccount());

        $stored = $this->service->getCashOnDeliveryConfig($systemId);
        $this->assertSame($result->getSystemId(), $stored->getSystemId());
        $this->assertFalse($stored->isEnabled());
        $this->assertFalse($stored->isActive());
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testEnableSetsEnabledTrue()
    {
        $systemId = 'sys789';
        $entity = new CashOnDelivery();
        $entity->setSystemId($systemId);
        $entity->setEnabled(false);
        $entity->setActive(true);
        $entity->setAccount(new Account());
        $this->repository->save($entity);

        $result = $this->service->enable($systemId);

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
        $systemId = 'sys987';
        $entity = new CashOnDelivery();
        $entity->setSystemId($systemId);
        $entity->setEnabled(true);
        $entity->setActive(true);
        $entity->setAccount(new Account());
        $this->repository->save($entity);

        $result = $this->service->disable($systemId);

        $this->assertFalse($result->isEnabled());
        $this->assertTrue($result->isActive()); // active not changed in disable()
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testEnableReturnsNullIfEntityNotFound()
    {
        $systemId = 'nonexistent';
        $result = $this->service->enable($systemId);

        $this->assertNull($result);
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function testDisableReturnsNullIfEntityNotFound()
    {
        $systemId = 'nonexistent';
        $result = $this->service->disable($systemId);

        $this->assertNull($result);
    }
}