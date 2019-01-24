<?php

namespace Packlink\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\ShippingService;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDeliveryDetails;
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
                return new Proxy($me->shopConfig->getAuthorizationToken(), $me->httpClient);
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
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );

        self::assertNotNull($shippingMethod, 'Failed to create shipping method!');
        self::assertEquals(1, $shippingMethod->getServiceId(), 'Failed to set shipping method service ID!');
        $id = $shippingMethod->getId();
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
        $shippingService = $this->getShippingService(1);
        $serviceDetails = $this->getShippingServiceDetails(1);
        $shippingMethod = $this->shippingMethodService->add($shippingService, $serviceDetails);

        $shippingService->serviceName = 'changed name';
        $this->shippingMethodService->update($shippingService, $serviceDetails);

        $updatedShippingMethod = $this->shippingMethodService->getShippingMethod($shippingMethod->getId());

        self::assertNotEquals($shippingMethod->getServiceName(), $updatedShippingMethod->getServiceName());
        self::assertEquals('changed name', $updatedShippingMethod->getServiceName());

        $shippingService->serviceName = 'changed name';
        $this->shippingMethodService->update($shippingService, $serviceDetails);

        self::assertCount(
            0,
            $this->testShopShippingMethodService->callHistory,
            'Inactive service should not be updated in shop!'
        );
    }

    public function testDeleteMethod()
    {
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );

        self::assertNotNull($shippingMethod, 'Failed to create shipping method!');
        self::assertEquals(1, $shippingMethod->getServiceId(), 'Failed to set shipping method service ID!');
        $id = $shippingMethod->getId();
        self::assertGreaterThan(0, $id, 'Failed to set shipping method ID!');

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
        $serviceId = 1;
        $shippingService = $this->getShippingService($serviceId);
        $serviceDetails = $this->getShippingServiceDetails($serviceId);
        $this->shippingMethodService->add($shippingService, $serviceDetails);

        self::assertCount(0, $this->shippingMethodService->getActiveMethods());
        self::assertCount(1, $this->shippingMethodService->getAllMethods());

        $this->shippingMethodService->activate($serviceId);

        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory,
            'Activation should be triggered in shop!'
        );

        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['add'],
            'Activation should be triggered in shop!'
        );

        self::assertTrue($this->shippingMethodService->isAnyMethodActive());
        self::assertCount(1, $this->shippingMethodService->getActiveMethods());

        $shippingService->serviceName = 'changed name';
        $this->shippingMethodService->update($shippingService, $serviceDetails);

        self::assertCount(
            2,
            $this->testShopShippingMethodService->callHistory,
            'Update should be propagated to shop when instance is active!'
        );
        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['update'],
            'Update should be propagated to shop when instance is active!'
        );
    }

    public function testDeactivateMethod()
    {
        $serviceId = 1;
        $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );

        self::assertCount(0, $this->shippingMethodService->getActiveMethods());
        self::assertCount(1, $this->shippingMethodService->getAllMethods());

        $this->shippingMethodService->activate($serviceId);

        self::assertCount(1, $this->shippingMethodService->getActiveMethods());

        $this->shippingMethodService->deactivate($serviceId);
        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['delete'],
            'Deactivation should be triggered in shop!'
        );

        self::assertCount(0, $this->shippingMethodService->getActiveMethods());
    }

    public function testDeleteActiveMethod()
    {
        $serviceId = 1;
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );

        $this->shippingMethodService->activate($serviceId);

        self::assertCount(1, $this->shippingMethodService->getActiveMethods());

        $shippingMethod = $this->shippingMethodService->getShippingMethod($shippingMethod->getId());
        $this->shippingMethodService->delete($shippingMethod);
        self::assertCount(
            1,
            $this->testShopShippingMethodService->callHistory['delete'],
            'Deletion should be triggered in shop!'
        );

        self::assertCount(0, $this->shippingMethodService->getActiveMethods());
    }

    public function testShippingServiceSearchFromArray()
    {
        /** @var ParcelInfo[] $parcels */
        $parcels = array();

        $firstParcel = ParcelInfo::defaultParcel();
        $secondParcel = ParcelInfo::defaultParcel();
        $firstParcel->weight = 1;
        $secondParcel->weight = 10;
        $parcels[] = $firstParcel;
        $parcels[] = $secondParcel;

        $data = array(
            'service_id' => 20339,
            'from[country]' => 'IT',
            'from[zip]' => '00118',
            'to[country]' => 'IT',
            'to[zip]' => '00118',
            'packages[0][height]' => $parcels[0]->height,
            'packages[0][width]' => $parcels[0]->width,
            'packages[0][length]' => $parcels[0]->length,
            'packages[0][weight]' => $parcels[0]->weight,
            'packages[1][height]' => $parcels[1]->height,
            'packages[1][width]' => $parcels[1]->width,
            'packages[1][length]' => $parcels[1]->length,
            'packages[1][weight]' => $parcels[1]->weight,
        );

        $serviceSearch = ShippingServiceSearch::fromArray($data);

        self::assertEquals(20339, $serviceSearch->serviceId, 'Error in array to object conversion');
        self::assertEquals(
            $parcels[1]->weight,
            $serviceSearch->parcels[1]->weight,
            'Error in array to object conversion'
        );
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

    private function getShippingServiceDetails($id)
    {
        $details = ShippingServiceDeliveryDetails::fromArray(
            array(
                'id' => $id,
                'carrier_name' => 'test carrier',
                'service_name' => 'test service',
                'currency' => 'EUR',
                'country' => 'IT',
                'dropoff' => false,
                'delivery_to_parcelshop' => false,
                'category' => 'express',
                'transit_time' => '3 DAYS',
                'transit_hours' => 72,
                'first_estimated_delivery_date' => '2019-01-05',
                'price' => array(
                    'total_price' => 13.76,
                    'base_price' => 10.76,
                    'tax_price' => 3,
                ),
            )
        );

        $details->departureCountry = 'IT';
        $details->destinationCountry = 'IT';

        return $details;
    }
}
