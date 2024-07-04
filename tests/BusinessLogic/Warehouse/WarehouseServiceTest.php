<?php

namespace BusinessLogic\Warehouse;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Warehouse\WarehouseService;

/**
 * Class WarehouseServiceTest.
 *
 * @package BusinessLogic\Warehouse
 */
class WarehouseServiceTest extends BaseTestWithServices
{
    public function testGetDefault()
    {
        /** @var WarehouseService $service */
        $service = ServiceRegister::getService(WarehouseService::CLASS_NAME);

        $this->assertNull($service->getWarehouse(false), 'Warehouse should not be returned if not set nor created.');

        $warehouse = $service->getWarehouse();
        $this->assertNotNull($warehouse);
        $this->assertEmpty($warehouse->country, 'Country should not be set if user is not set.');

        $user = $this->getUser();
        $this->shopConfig->setUserInfo($user);
        $warehouse = $service->getWarehouse();
        $this->assertNotNull($warehouse);
        $this->assertEquals($user->country, $warehouse->country, 'Country should be set from user.');
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function testSave()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/postalCodes.json');

        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        /** @var WarehouseService $service */
        $service = ServiceRegister::getService(WarehouseService::CLASS_NAME);

        $data = json_decode(file_get_contents(__DIR__ . '/../Common/ApiResponses/warehouses.json'), true);
        $service->updateWarehouseData($data[0]);

        $warehouse = $service->getWarehouse(false);

        $this->assertNotNull($warehouse, 'Warehouse should be stored.');
        $this->assertEquals($data[0]['country'], $warehouse->country);
        $this->assertEquals($data[0]['postal_code'], $warehouse->postalCode);
        $this->assertEquals($data[0]['default_selection'], $warehouse->default);
    }

    /**
     * @return void
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testSaveInvalid()
    {
        /** @var WarehouseService $service */
        $service = ServiceRegister::getService(WarehouseService::CLASS_NAME);
        $exThrown = null;
        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $service->updateWarehouseData(array('country' => 'ES', 'name' => 'test'));
        } catch (\Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }

    /**
     * @return void
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testSaveInvalidPostalCode()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '{}')));

        /** @var WarehouseService $service */
        $service = ServiceRegister::getService(WarehouseService::CLASS_NAME);

        $data = json_decode(file_get_contents(__DIR__ . '/../Common/ApiResponses/warehouses.json'), true);
        $exThrown = null;
        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $service->updateWarehouseData($data[0]);
        } catch (\Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }

    /**
     * @return void
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testSaveInvalidPostalCodeProxyResponse()
    {
        /** @var WarehouseService $service */
        $service = ServiceRegister::getService(WarehouseService::CLASS_NAME);

        $data = json_decode(file_get_contents(__DIR__ . '/../Common/ApiResponses/warehouses.json'), true);
        $data[0]['postal_code'] = '11111';
        $exThrown = null;
        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $service->updateWarehouseData($data[0]);
        } catch (\Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }

    /**
     * @return \Packlink\BusinessLogic\Http\DTO\User
     */
    protected function getUser()
    {
        $user = new User();
        $user->country = 'ES';
        $user->firstName = 'Test';
        $user->lastName = 'User';
        $user->email = 'test.user@example.com';

        return $user;
    }
}
