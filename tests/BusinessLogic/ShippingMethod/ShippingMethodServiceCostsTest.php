<?php

namespace Packlink\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Common\BaseTestWithServices;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\ShippingService;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDeliveryDetails;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\FixedPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\PercentPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

class ShippingMethodServiceCostsTest extends BaseTestWithServices
{
    /**
     * @var ShippingMethodService
     */
    private $shippingMethodService;
    /**
     * @var TestShopShippingMethodService
     */
    private $testShopShippingMethodService;
    /**
     * @var TestHttpClient
     */
    private $httpClient;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        $me = $this;
        $this->shopConfig->setAuthorizationToken('test_token');

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

    public function testGetCostsFromUnknownService()
    {
        // first service from this response has id 20339 and cost of 4.94
        $response = file_get_contents(
            __DIR__ . '/../../Common/ApiResponses/ShippingServices/ShippingServiceDetails-IT-IT.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        // since method is not inserted to database, this should return 0;
        $cost = $this->shippingMethodService->getShippingCost(
            20339,
            'IT',
            '00118',
            'IT',
            '00118',
            ParcelInfo::defaultParcel()
        );

        self::assertEquals(0, $cost, 'Shipping cost should not be returned for unknown service.');
        self::assertNull($this->httpClient->getHistory(), 'API should not be called for unknown service.');
    }

    public function testGetCostsFromProxy()
    {
        // this method has costs of 10.76
        $this->shippingMethodService->add(
            $this->getShippingService(20339),
            $this->getShippingServiceDetails(20339)
        );

        // first service from this response has id 20339 and cost of 4.94
        $response = file_get_contents(
            __DIR__ . '/../../Common/ApiResponses/ShippingServices/ShippingServiceDetails-IT-IT.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $cost = $this->shippingMethodService->getShippingCost(
            20339,
            'IT',
            '00118',
            'IT',
            '00118',
            ParcelInfo::defaultParcel()
        );

        self::assertEquals(4.94, $cost, 'Failed to get cost from API!');
    }

    public function testGetCostsFallbackToShippingMethod()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(20339),
            $this->getShippingServiceDetails(20339)
        );

        // first service from this response has id 20339 and cost of 4.94
        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));

        $cost = $this->shippingMethodService->getShippingCost(
            20339,
            'IT',
            '00118',
            'IT',
            '00118',
            ParcelInfo::defaultParcel()
        );

        $costs = $shippingMethod->getShippingCosts();
        self::assertEquals($costs[0]->basePrice, $cost, 'Failed to get default cost from local method!');
    }

    public function testGetCostsNoFallback()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );

        // first service from this response has id 20339 and cost of 4.94
        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));

        $cost = $this->shippingMethodService->getShippingCost(
            20339,
            'IT',
            '00118',
            'IT',
            '00118',
            ParcelInfo::defaultParcel()
        );

        $costs = $shippingMethod->getShippingCosts();
        self::assertNotEquals($costs[0]->basePrice, $cost, 'Failed to get default cost!');
        self::assertEquals(0, $cost);
    }

    public function testCalculateCostFixedPricingPolicy()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', ParcelInfo::defaultParcel());

        $costs = $shippingMethod->getShippingCosts();
        // cost should be calculated, and not default
        self::assertNotEquals($costs[0]->basePrice, $cost, 'Default cost used when calculation should be performed!');
        self::assertEquals(12, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostFixedPricingPolicyOutOfRange()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $parcel = ParcelInfo::defaultParcel();
        $parcel->weight = 100;
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', $parcel);

        $costs = $shippingMethod->getShippingCosts();
        // cost should be calculated, and not default
        self::assertNotEquals($costs[0]->basePrice, $cost, 'Default cost used when calculation should be performed!');
        self::assertEquals(12, $cost, 'Calculated cost is wrong!');

        $fixedPricePolicies[] = new FixedPricePolicy(10, 20, 10);
        $fixedPricePolicies[] = new FixedPricePolicy(20, 30, 8);
        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', $parcel);
        self::assertEquals(8, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostFixedPricingPolicyInRange()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 20, 10);
        $fixedPricePolicies[] = new FixedPricePolicy(20, 30, 8);

        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $parcel = ParcelInfo::defaultParcel();

        $parcel->weight = 8;
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', $parcel);
        self::assertEquals(12, $cost, 'Calculated cost is wrong!');

        $parcel->weight = 10;
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', $parcel);
        self::assertEquals(10, $cost, 'Calculated cost is wrong!');

        $parcel->weight = 14;
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', $parcel);
        self::assertEquals(10, $cost, 'Calculated cost is wrong!');

        $parcel->weight = 20;
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', $parcel);
        self::assertEquals(8, $cost, 'Calculated cost is wrong!');

        $parcel->weight = 25;
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', $parcel);
        self::assertEquals(8, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostPercentPricingPolicyIncreased()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(true, 14));
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', ParcelInfo::defaultParcel());
        self::assertEquals(12.27, $cost, 'Calculated cost is wrong!');

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(true, 50));
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', ParcelInfo::defaultParcel());
        self::assertEquals(16.14, $cost, 'Calculated cost is wrong!');

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(true, 120));
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', ParcelInfo::defaultParcel());
        self::assertEquals(23.67, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostPercentPricingPolicyDecreased()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(false, 14));
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', ParcelInfo::defaultParcel());
        self::assertEquals(9.25, $cost, 'Calculated cost is wrong!');

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(false, 50));
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', ParcelInfo::defaultParcel());
        self::assertEquals(5.38, $cost, 'Calculated cost is wrong!');

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(false, 80));
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', ParcelInfo::defaultParcel());
        self::assertEquals(2.15, $cost, 'Calculated cost is wrong!');
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
