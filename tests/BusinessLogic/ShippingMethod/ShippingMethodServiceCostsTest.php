<?php

namespace Packlink\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\Package;
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
    public $shippingMethodService;
    /**
     * @var TestShopShippingMethodService
     */
    public $testShopShippingMethodService;
    /**
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient
     */
    public $httpClient;

    /**
     * @inheritdoc
     */
    protected function setUp()
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

        $serviceIds = array(20203, 20945, 20189);
        foreach ($serviceIds as $serviceId) {
            $shippingService = $this->getShippingService($serviceId);
            $serviceDetails = $this->getShippingServiceDetails($serviceId);
            $this->shippingMethodService->add($shippingService, $serviceDetails);
            $this->shippingMethodService->activate($serviceId);
        }
    }

    protected function tearDown()
    {
        $serviceIds = array(20203, 20945, 20189);
        foreach ($serviceIds as $serviceId) {
            $this->shippingMethodService->deactivate($serviceId);
        }

        ShippingMethodService::resetInstance();

        parent::tearDown();
    }

    public function testGetCostFromUnknownService()
    {
        // first service from this response has id 20339 and cost of 4.94
        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServiceDetails-IT-IT.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        // since method is not inserted to database, this should return 0;
        $cost = $this->shippingMethodService->getShippingCost(
            20339,
            'IT',
            '00118',
            'IT',
            '00118',
            array(Package::defaultPackage())
        );

        self::assertEquals(0, $cost, 'Shipping cost should not be returned for unknown service.');
        self::assertNull($this->httpClient->getHistory(), 'API should not be called for unknown service.');
    }

    public function testGetCostsFromUnknownService()
    {
        // first service from this response has id 20339 and cost of 5.06
        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServicesDetails-IT-IT.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));
        // since method is not inserted to database, returned array should not have costs for this method.
        $package = Package::defaultPackage();
        $costs = $this->shippingMethodService->getShippingCosts('IT', '00118', 'IT', '00118', array($package));

        self::assertArrayNotHasKey(20339, $costs);
    }

    public function testGetCostFromProxy()
    {
        // this method has costs of 10.76
        $this->shippingMethodService->add(
            $this->getShippingService(20339),
            $this->getShippingServiceDetails(20339)
        );
        $this->shippingMethodService->activate(20339);

        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServiceDetails-IT-IT.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $package = Package::defaultPackage();
        $cost = $this->shippingMethodService->getShippingCost(20339, 'IT', '00118', 'IT', '00118', array($package));

        self::assertEquals(4.94, $cost, 'Failed to get cost from API!');
    }

    public function testGetCostForInactiveService()
    {
        // this method has costs of 10.76
        $this->shippingMethodService->add(
            $this->getShippingService(20339),
            $this->getShippingServiceDetails(20339)
        );

        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServiceDetails-IT-IT.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $package = Package::defaultPackage();
        $cost = $this->shippingMethodService->getShippingCost(20339, 'IT', '00118', 'IT', '00118', array($package));

        self::assertEquals(0, $cost, 'Failed to get cost from API!');
        self::assertEmpty($this->httpClient->getHistory(), 'API should not be called for inactive service');
    }

    public function testGetCostsFromProxy()
    {
        $serviceId = 20339;
        $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );
        $this->shippingMethodService->activate($serviceId);

        // first service from this response has id 20339 and cost of 5.06
        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServicesDetails-IT-IT.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $package = Package::defaultPackage();
        $costs = $this->shippingMethodService->getShippingCosts('IT', '00118', 'IT', '00118', array($package));

        self::assertEquals(5.06, $costs[$serviceId], 'Failed to get cost from API!');
    }

    public function testGetCostsFromProxyForMultipleServices()
    {
        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServicesDetails-IT-IT.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $package = Package::defaultPackage();
        $costs = $this->shippingMethodService->getShippingCosts('IT', '00118', 'IT', '00118', array($package));

        self::assertArrayHasKey(20203, $costs, 'Shipping cost for one of the services missing!');
        self::assertArrayHasKey(20945, $costs, 'Shipping cost for one of the services missing!');
        self::assertArrayHasKey(20189, $costs, 'Shipping cost for one of the services missing!');

        self::assertEquals(6.28, $costs[20203], 'Calculated cost is wrong!');
        self::assertEquals(7.94, $costs[20945], 'Calculated cost is wrong!');
        self::assertEquals(9.04, $costs[20189], 'Calculated cost is wrong!');
    }

    public function testGetCostFromProxyForMultiplePackages()
    {
        // this method has costs of 10.76
        $this->shippingMethodService->add(
            $this->getShippingService(20339),
            $this->getShippingServiceDetails(20339)
        );
        $this->shippingMethodService->activate(20339);

        // first service from this response has id 20339 and total base price of 14.16
        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServiceDetails-MultipleParcels.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $firstPackage = new Package(1, 10, 10, 10);
        $secondPackage = new Package(10, 10, 10, 10);

        $cost = $this->shippingMethodService->getShippingCost(
            20339,
            'IT',
            '00118',
            'IT',
            '00118',
            array($firstPackage, $secondPackage)
        );

        self::assertEquals(14.16, $cost, 'Failed to get cost from API!');
    }

    public function testGetCostsFromProxyForMultiplePackages()
    {
        $serviceId = 20339;
        $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );
        $this->shippingMethodService->activate($serviceId);

        // first service from this response has id 20339 and total base price of 41.43
        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServicesDetails-MultipleParcels.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $firstPackage = new Package(1, 10, 10, 10);
        $secondPackage = new Package(10, 10, 10, 10);

        $costs = $this->shippingMethodService->getShippingCosts(
            'IT',
            '00118',
            'IT',
            '00118',
            array($firstPackage, $secondPackage, $secondPackage, $secondPackage)
        );

        self::assertEquals(41.43, $costs[$serviceId], 'Failed to get cost from API!');
    }

    public function testGetCostFallbackToShippingMethod()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(20339),
            $this->getShippingServiceDetails(20339)
        );
        $this->shippingMethodService->activate(20339);

        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));

        $cost = $this->shippingMethodService->getShippingCost(
            20339,
            'IT',
            '00118',
            'IT',
            '00118',
            array(Package::defaultPackage())
        );

        $costs = $shippingMethod->getShippingCosts();
        self::assertEquals($costs[0]->basePrice, $cost, 'Failed to get default cost from local method!');
    }

    public function testGetCostsFallbackToShippingMethod()
    {
        $serviceId = 20339;
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );
        $this->shippingMethodService->activate($serviceId);

        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));

        $costs = $this->shippingMethodService->getShippingCosts(
            'IT',
            '00118',
            'IT',
            '00118',
            array(Package::defaultPackage())
        );

        $defaultCosts = $shippingMethod->getShippingCosts();
        self::assertEquals(
            $costs[$serviceId],
            $defaultCosts[0]->basePrice,
            'Failed to get default cost from local method!'
        );
    }

    public function testGetCostNoFallback()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );
        $this->shippingMethodService->activate(1);

        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));

        $cost = $this->shippingMethodService->getShippingCost(
            1234,
            'IT',
            '00118',
            'IT',
            '00118',
            array(Package::defaultPackage())
        );

        $costs = $shippingMethod->getShippingCosts();
        self::assertNotEquals($costs[0]->basePrice, $cost, 'Failed to get default cost!');
        self::assertEquals(0, $cost);
    }

    public function testGetCostsNoFallback()
    {
        $serviceId = 1;
        $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );

        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));

        $costs = $this->shippingMethodService->getShippingCosts(
            'IT',
            '00118',
            'IT',
            '00118',
            array(Package::defaultPackage())
        );

        self::assertArrayNotHasKey($serviceId, $costs);
    }

    public function testGetCostsNoFallbackForInactiveMethod()
    {
        // API has service 20339
        $serviceId = 20339;
        $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );

        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));

        $costs = $this->shippingMethodService->getShippingCosts(
            'IT',
            '00118',
            'IT',
            '00118',
            array(Package::defaultPackage())
        );

        self::assertArrayNotHasKey($serviceId, $costs, 'Cost should not be calculated for inactive service.');
    }

    public function testCalculateCostFixedPricingPolicy()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );
        $shippingMethod->setActivated(true);

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array(Package::defaultPackage()));

        $costs = $shippingMethod->getShippingCosts();
        // cost should be calculated, and not default
        self::assertNotEquals($costs[0]->basePrice, $cost, 'Default cost used when calculation should be performed!');
        self::assertEquals(12, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostsFixedPricingPolicy()
    {
        $serviceId = 20339;
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );
        $shippingMethod->setActivated(true);

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array(Package::defaultPackage()));

        $defaultCosts = $shippingMethod->getShippingCosts();
        self::assertNotEquals(
            $defaultCosts[0]->basePrice,
            $costs[$serviceId],
            'Default cost used when calculation should be performed!'
        );
        self::assertEquals(12, $costs[$serviceId], 'Calculated cost is wrong!');
    }

    public function testCalculateCostFixedPricingPolicyOutOfRange()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );
        $shippingMethod->setActivated(true);

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $package = new Package(100, 10, 10, 10);
        $this->httpClient->setMockResponses(
            array(new HttpResponse(400, array(), ''), new HttpResponse(400, array(), ''))
        );
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));

        $costs = $shippingMethod->getShippingCosts();
        // cost should be calculated, and not default
        self::assertNotEquals($costs[0]->basePrice, $cost, 'Default cost used when calculation should be performed!');
        self::assertEquals(12, $cost, 'Calculated cost is wrong!');

        $fixedPricePolicies[] = new FixedPricePolicy(10, 20, 10);
        $fixedPricePolicies[] = new FixedPricePolicy(20, 30, 8);
        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));
        self::assertEquals(8, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostsFixedPricingPolicyOutOfRange()
    {
        $serviceId = 20339;
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );
        $shippingMethod->setActivated(true);

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $package = new Package(100, 10, 10, 10);
        $this->httpClient->setMockResponses(
            array(new HttpResponse(400, array(), ''), new HttpResponse(400, array(), ''))
        );
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));

        $defaultCosts = $shippingMethod->getShippingCosts();
        self::assertNotEquals(
            $defaultCosts[0]->basePrice,
            $costs[$serviceId],
            'Default cost used when calculation should be performed!'
        );
        self::assertEquals(12, $costs[$serviceId], 'Calculated cost is wrong!');

        $fixedPricePolicies[] = new FixedPricePolicy(10, 20, 10);
        $fixedPricePolicies[] = new FixedPricePolicy(20, 30, 8);
        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));
        self::assertEquals(8, $costs[$serviceId], 'Calculated cost is wrong!');
    }

    public function testCalculateCostFixedPricingPolicyInRange()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );
        $shippingMethod->setActivated(true);

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 20, 10);
        $fixedPricePolicies[] = new FixedPricePolicy(20, 30, 8);

        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $package = new Package(8, 10, 10, 10);
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
            )
        );
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));
        self::assertEquals(12, $cost, 'Calculated cost is wrong!');

        $package->weight = 10;
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));
        self::assertEquals(10, $cost, 'Calculated cost is wrong!');

        $package->weight = 14;
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));
        self::assertEquals(10, $cost, 'Calculated cost is wrong!');

        $package->weight = 20;
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));
        self::assertEquals(8, $cost, 'Calculated cost is wrong!');

        $package->weight = 25;
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));
        self::assertEquals(8, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostsFixedPricingPolicyInRange()
    {
        $serviceId = 20339;
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );
        $shippingMethod->setActivated(true);

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 20, 10);
        $fixedPricePolicies[] = new FixedPricePolicy(20, 30, 8);

        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $package = new Package(8, 10, 10, 10);
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
            )
        );
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));
        self::assertEquals(12, $costs[$serviceId], 'Calculated cost is wrong!');

        $package->weight = 10;
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));
        self::assertEquals(10, $costs[$serviceId], 'Calculated cost is wrong!');

        $package->weight = 14;
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));
        self::assertEquals(10, $costs[$serviceId], 'Calculated cost is wrong!');

        $package->weight = 20;
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));
        self::assertEquals(8, $costs[$serviceId], 'Calculated cost is wrong!');

        $package->weight = 25;
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));
        self::assertEquals(8, $costs[$serviceId], 'Calculated cost is wrong!');
    }

    public function testCalculateCostFixedPricingPolicyInRangeMultiple()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );
        $shippingMethod->setActivated(true);

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 20, 10);
        $fixedPricePolicies[] = new FixedPricePolicy(20, 30, 8);

        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
            )
        );
        $packages = array();
        // First range.
        $firstPackage = Package::defaultPackage();
        $secondPackage = Package::defaultPackage();
        $firstPackage->weight = 2;
        $secondPackage->weight = 4;
        $packages[] = $firstPackage;
        $packages[] = $secondPackage;

        $this->checkShippingCostMatchesExpectedCost($packages, 12);

        // Second range.
        $thirdPackage = Package::defaultPackage();
        $thirdPackage->weight = 10;
        $packages[] = $thirdPackage;

        $this->checkShippingCostMatchesExpectedCost($packages, 10);

        // Third range.
        $fourthPackage = Package::defaultPackage();
        $fourthPackage->weight = 7;
        $packages[] = $fourthPackage;

        $this->checkShippingCostMatchesExpectedCost($packages, 8);
    }

    public function testCalculateCostsFixedPricingPolicyInRangeMultiple()
    {
        $serviceId = 20339;
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );
        $shippingMethod->setActivated(true);

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 20, 10);
        $fixedPricePolicies[] = new FixedPricePolicy(20, 30, 8);

        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
            )
        );
        $packages = array();

        // First range.
        $firstPackage = Package::defaultPackage();
        $secondPackage = Package::defaultPackage();
        $firstPackage->weight = 2;
        $secondPackage->weight = 4;
        $packages[] = $firstPackage;
        $packages[] = $secondPackage;

        $this->checkShippingCostsMatchExpectedCost($packages, 12, $serviceId);

        // Second range.
        $thirdPackage = Package::defaultPackage();
        $thirdPackage->weight = 10;
        $packages[] = $thirdPackage;

        $this->checkShippingCostsMatchExpectedCost($packages, 10, $serviceId);

        // Third range.
        $fourthPackage = Package::defaultPackage();
        $fourthPackage->weight = 7;
        $packages[] = $fourthPackage;

        $this->checkShippingCostsMatchExpectedCost($packages, 8, $serviceId);
    }

    public function testCalculateCostPercentPricingPolicyIncreased()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );
        $shippingMethod->setActivated(true);

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(true, 14));
        $this->shippingMethodService->save($shippingMethod);

        $package = Package::defaultPackage();
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
            )
        );
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));
        self::assertEquals(12.27, $cost, 'Calculated cost is wrong!');

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(true, 50));
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));
        self::assertEquals(16.14, $cost, 'Calculated cost is wrong!');

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(true, 120));
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));
        self::assertEquals(23.67, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostsPercentPricingPolicyIncreased()
    {
        $serviceId = 20339;
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );
        $shippingMethod->setActivated(true);

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(true, 14));
        $this->shippingMethodService->save($shippingMethod);

        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array(Package::defaultPackage()));
        self::assertEquals(12.27, $costs[$serviceId], 'Calculated cost is wrong!');
    }

    public function testCalculateCostPercentPricingPolicyDecreased()
    {
        // this method has costs of 10.76
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService(1),
            $this->getShippingServiceDetails(1)
        );
        $shippingMethod->setActivated(true);

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(false, 14));
        $this->shippingMethodService->save($shippingMethod);

        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
                new HttpResponse(400, array(), ''),
            )
        );
        $package = Package::defaultPackage();
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));
        self::assertEquals(9.25, $cost, 'Calculated cost is wrong!');

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(false, 50));
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));
        self::assertEquals(5.38, $cost, 'Calculated cost is wrong!');

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(false, 80));
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', array($package));
        self::assertEquals(2.15, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostsPercentPricingPolicyDecreased()
    {
        $serviceId = 20339;
        $shippingMethod = $this->shippingMethodService->add(
            $this->getShippingService($serviceId),
            $this->getShippingServiceDetails($serviceId)
        );
        $shippingMethod->setActivated(true);

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy(false, 14));
        $this->shippingMethodService->save($shippingMethod);

        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array(Package::defaultPackage()));

        self::assertEquals(9.25, $costs[$serviceId], 'Calculated cost is wrong!');
    }

    /**
     * @param Package[] $packages
     * @param float $expectedCost
     */
    protected function checkShippingCostMatchesExpectedCost(array $packages, $expectedCost)
    {
        $cost = $this->shippingMethodService->getShippingCost(1, 'IT', '', 'IT', '', $packages);

        self::assertEquals($expectedCost, $cost, 'Calculated cost is wrong!');
    }

    /**
     * @param Package[] $packages
     * @param float $expectedCost
     * @param int $serviceId
     */
    protected function checkShippingCostsMatchExpectedCost(array $packages, $expectedCost, $serviceId)
    {
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', $packages);

        self::assertEquals($expectedCost, $costs[$serviceId], 'Calculated cost is wrong!');
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
