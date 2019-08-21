<?php

namespace Packlink\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ShippingService;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

class ShippingMethodServiceTest extends BaseTestWithServices
{
    /**
     * @var ShippingMethodService
     */
    public $shippingMethodService;
    /**
     * @var TestShopShippingMethodService
     */
    public $testShopShippingMethodService;
    /**
     * @var TestHttpClient
     */
    public $httpClient;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        $me = $this;
        $me->shopConfig->setAuthorizationToken('test_token');

        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

        $this->testShopShippingMethodService = new TestShopShippingMethodService();
        TestServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () use ($me) {
                return $me->testShopShippingMethodService;
            }
        );

        $this->shippingMethodService = ShippingMethodService::getInstance();
        TestServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () use ($me) {
                return $me->shippingMethodService;
            }
        );

        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($me) {
                return new Proxy($me->shopConfig, $me->httpClient);
            }
        );
    }

    protected function tearDown()
    {
        ShippingMethodService::resetInstance();

        parent::tearDown();
    }

    public function testGetAllMethodsEmpty()
    {
        self::assertCount(0, $this->shippingMethodService->getAllMethods());
    }

    public function testAddNewMethod()
    {
        $shippingMethod = $this->shippingMethodService->add($this->getShippingServiceDetails(1));
        $id = $shippingMethod->getId();

        self::assertNotNull($shippingMethod, 'Failed to create shipping method!');
        $allServices = $shippingMethod->getShippingServices();
        /** @var \Packlink\BusinessLogic\ShippingMethod\Models\ShippingService $firstService */
        $firstService = current($allServices);
        self::assertEquals(1, $firstService->serviceId, 'Failed to set shipping method service ID!');
        self::assertGreaterThan(0, $id, 'Failed to set shipping method ID!');

        $shippingMethod = $this->shippingMethodService->getShippingMethod($id);
        self::assertNotNull($shippingMethod, 'Failed to retrieve created shipping method!');

        self::assertCount(1, $this->shippingMethodService->getAllMethods());

        self::assertCount(
            0,
            $this->testShopShippingMethodService->callHistory,
            'Inactive service should not be updated in shop!'
        );
    }

    public function testUpdateMethod()
    {
        $serviceDetails = $this->getShippingServiceDetails(1);
        $defaultName = $serviceDetails->serviceName;
        $shippingMethod = $this->shippingMethodService->add($this->getShippingServiceDetails(1));

        $serviceDetails->serviceName = 'changed name';
        $this->shippingMethodService->update($serviceDetails);

        $updatedShippingMethod = $this->shippingMethodService->getShippingMethod($shippingMethod->getId());
        $allServices = $updatedShippingMethod->getShippingServices();
        /** @var \Packlink\BusinessLogic\ShippingMethod\Models\ShippingService $firstService */
        $firstService = current($allServices);

        self::assertNotEquals($defaultName, $firstService->serviceName);
        self::assertEquals('changed name', $firstService->serviceName);

        $serviceDetails->serviceName = 'changed name';
        $this->shippingMethodService->update($serviceDetails);

        self::assertCount(
            0,
            $this->testShopShippingMethodService->callHistory,
            'Inactive service should not be updated in shop!'
        );
    }

    public function testDeleteMethod()
    {
        $shippingMethod = $this->shippingMethodService->add($this->getShippingServiceDetails(1));

        self::assertNotNull($shippingMethod, 'Failed to create shipping method!');

        $this->shippingMethodService->delete($shippingMethod);
        self::assertCount(0, $this->shippingMethodService->getAllMethods());

        self::assertCount(
            0,
            $this->testShopShippingMethodService->callHistory,
            'Inactive service should not be updated in shop!'
        );
    }

    public function testActivateMethod()
    {
        $serviceDetails = $this->getShippingServiceDetails(1);
        $secondDetails = $this->getShippingServiceDetails(2, 'new_carier');
        $method = $this->shippingMethodService->add($serviceDetails);
        $method2 = $this->shippingMethodService->add($secondDetails);

        self::assertCount(0, $this->shippingMethodService->getActiveMethods());
        self::assertCount(2, $this->shippingMethodService->getAllMethods());

        $this->shippingMethodService->activate($method->getId());

        self::assertTrue($this->shippingMethodService->isAnyMethodActive());
        self::assertCount(
            2,
            $this->testShopShippingMethodService->callHistory,
            'Activation should be triggered in shop!'
        );

        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['add'],
            'Activation should be triggered in shop!'
        );

        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['addBackup'],
            'Backup shipping method should be added in shop.'
        );

        $this->shippingMethodService->activate($method2->getId());

        self::assertCount(
            2,
            $this->testShopShippingMethodService->callHistory['add'],
            'Activation should be triggered in shop!'
        );

        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['addBackup'],
            'Backup shipping method should not be added in shop if there are already active methods.'
        );

        $serviceDetails->serviceName = 'changed name';
        $this->shippingMethodService->update($serviceDetails);

        self::assertCount(
            3,
            $this->testShopShippingMethodService->callHistory,
            'Update should be propagated to shop when instance is active!'
        );
        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['update'],
            'Update should be propagated to shop when instance is active!'
        );
        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['addBackup'],
            'Backup shipping method should not be added in shop on update.'
        );
    }

    public function testDeactivateMethod()
    {
        $method = $this->shippingMethodService->add($this->getShippingServiceDetails(1));

        self::assertCount(0, $this->shippingMethodService->getActiveMethods());
        self::assertCount(1, $this->shippingMethodService->getAllMethods());

        $this->shippingMethodService->activate($method->getId());

        self::assertCount(1, $this->shippingMethodService->getActiveMethods());

        $this->shippingMethodService->deactivate($method->getId());
        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['delete'],
            'Deactivation should be triggered in shop!'
        );

        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['deleteBackup'],
            'Deleting backup shipping should be triggered in shop!'
        );

        self::assertCount(0, $this->shippingMethodService->getActiveMethods());

        $method2 = $this->shippingMethodService->add($this->getShippingServiceDetails(2, 'second_carrier'));

        self::assertCount(2, $this->shippingMethodService->getAllMethods());

        $this->shippingMethodService->activate($method->getId());
        $this->shippingMethodService->activate($method2->getId());

        self::assertCount(2, $this->shippingMethodService->getActiveMethods());

        $this->shippingMethodService->deactivate($method->getId());

        self::assertCount(1, $this->shippingMethodService->getActiveMethods());

        self::assertCount(
            2,
            $this->testShopShippingMethodService->callHistory['delete'],
            'Deactivation should be triggered in shop!'
        );

        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['deleteBackup'],
            'Deleting backup shipping should not be triggered in shop when there are active methods!'
        );

        $this->shippingMethodService->deactivate($method2->getId());

        self::assertCount(
            3,
            $this->testShopShippingMethodService->callHistory['delete'],
            'Deactivation should be triggered in shop!'
        );

        self::assertCount(
            2,
            $this->testShopShippingMethodService->callHistory['deleteBackup'],
            'Deleting backup shipping should be triggered in shop!'
        );

        self::assertCount(0, $this->shippingMethodService->getActiveMethods());
    }

    public function testDeleteActiveMethod()
    {
        $method = $this->shippingMethodService->add($this->getShippingServiceDetails(1));

        $this->shippingMethodService->activate($method->getId());

        self::assertCount(1, $this->shippingMethodService->getActiveMethods());

        $shippingMethod = $this->shippingMethodService->getShippingMethod($method->getId());
        $this->shippingMethodService->delete($shippingMethod);
        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['delete'],
            'Deletion should be triggered in shop!'
        );

        self::assertCount(0, $this->shippingMethodService->getActiveMethods());
    }

    public function testGetByServiceId()
    {
        $service = $this->getShippingServiceDetails(1123);
        $this->shippingMethodService->add($service);
        $shippingMethod = $this->shippingMethodService->getShippingMethodForService($service);

        self::assertNotNull($shippingMethod, 'Failed to retrieve created shipping method!');
    }

    public function testShippingServiceSearchFromArray()
    {
        /** @var Package[] $packages */
        $packages = array();

        $firstPackage = Package::defaultPackage();
        $secondPackage = Package::defaultPackage();
        $firstPackage->weight = 1;
        $secondPackage->weight = 10;
        $packages[] = $firstPackage;
        $packages[] = $secondPackage;

        $data = array(
            'service_id' => 20339,
            'from[country]' => 'IT',
            'from[zip]' => '00118',
            'to[country]' => 'IT',
            'to[zip]' => '00118',
            'packages[0][height]' => $packages[0]->height,
            'packages[0][width]' => $packages[0]->width,
            'packages[0][length]' => $packages[0]->length,
            'packages[0][weight]' => $packages[0]->weight,
            'packages[1][height]' => $packages[1]->height,
            'packages[1][width]' => $packages[1]->width,
            'packages[1][length]' => $packages[1]->length,
            'packages[1][weight]' => $packages[1]->weight,
        );

        $serviceSearch = ShippingServiceSearch::fromArray($data);

        self::assertEquals(20339, $serviceSearch->serviceId, 'Error in array to object conversion');
        self::assertEquals(
            $packages[1]->weight,
            $serviceSearch->packages[1]->weight,
            'Error in array to object conversion'
        );
    }

    public function testShippingServiceFromArray()
    {
        $service = $this->getShippingService(123);
        $service->departureDropOff = true;
        $service->destinationDropOff = false;

        $array = $service->toArray();

        self::assertEquals($service->id, $array['service_id']);
        self::assertEquals($service->enabled, $array['enabled']);
        self::assertEquals($service->carrierName, $array['carrier_name']);
        self::assertEquals($service->serviceName, $array['service_name']);
        self::assertEquals($service->logoUrl, $array['service_logo']);
        self::assertEquals('drop-off', $array['departure_type']);
        self::assertEquals('home', $array['destination_type']);
        self::assertEquals($service->serviceDetails, $array['service_details']);
        self::assertEquals($service->packlinkInfo, $array['packlink_info']);
    }

    private function getShippingService($id)
    {
        return ShippingService::fromArray(
            array(
                'service_id' => $id,
                'enabled' => true,
                'carrier_name' => 'test carrier',
                'service_name' => 'test service',
                'service_logo' => '',
                'departure_type' => 'pick-up',
                'destination_type' => 'drop-off',
            )
        );
    }

    private function getShippingServiceDetails($id, $carrierName = 'test carrier')
    {
        $details = ShippingServiceDetails::fromArray(
            array(
                'id' => $id,
                'carrier_name' => $carrierName,
                'service_name' => 'test service',
                'currency' => 'EUR',
                'country' => 'IT',
                'dropoff' => false,
                'delivery_to_parcelshop' => false,
                'category' => 'express',
                'transit_time' => '3 DAYS',
                'transit_hours' => 72,
                'first_estimated_delivery_date' => '2019-01-05',
                'national' => true,
                'price' => array(
                    'total_price' => 13.76,
                    'base_price' => 10.76,
                    'tax_price' => 3,
                ),
            )
        );

        $details->departureCountry = 'IT';
        $details->destinationCountry = 'IT';
        $details->national = true;

        return $details;
    }
}
