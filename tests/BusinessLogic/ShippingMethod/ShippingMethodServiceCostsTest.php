<?php

namespace Packlink\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\FixedPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\PercentPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingCostCalculator;
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
     * @var array
     */
    protected $serviceIds = array(20203, 20945, 20189);

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

        MemoryStorage::reset();

        foreach ($this->serviceIds as $serviceId) {
            $serviceDetails = $this->getShippingServiceDetails($serviceId, 'carrier ' . $serviceId);
            $method = $this->shippingMethodService->add($serviceDetails);
            $this->shippingMethodService->activate($method->getId());
        }
    }

    protected function tearDown()
    {
        ShippingMethodService::resetInstance();
        MemoryStorage::reset();

        parent::tearDown();
    }

    public function testActivationWrongService()
    {
        self::assertFalse($this->shippingMethodService->activate(12345));
        self::assertNotEmpty($this->shopLogger->loggedMessages);
    }

    public function testActivationErrorInShop()
    {
        $this->testShopShippingMethodService->returnFalse = true;
        self::assertFalse($this->shippingMethodService->activate(20203));
        self::assertNotEmpty($this->shopLogger->loggedMessages);
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
        $shippingMethod = $this->addShippingMethod(20339);

        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServiceDetails-IT-IT.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $package = Package::defaultPackage();
        $cost = $this->shippingMethodService->getShippingCost(
            $shippingMethod->getId(),
            'IT',
            '00118',
            'IT',
            '00118',
            array($package)
        );

        self::assertEquals(4.94, $cost, 'Failed to get cost from API!');
    }

    public function testGetCostForInactiveService()
    {
        // this method has costs of 10.76
        $this->addShippingMethod(20339, false);

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
        $shippingMethod = $this->addShippingMethod(20339);

        // first service from this response has id 20339 and cost of 5.06
        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServicesDetails-IT-IT.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $package = Package::defaultPackage();
        $costs = $this->shippingMethodService->getShippingCosts('IT', '00118', 'IT', '00118', array($package));

        self::assertEquals(5.06, $costs[$shippingMethod->getId()], 'Failed to get cost from API!');
    }

    public function testGetCostsFromProxyForMultipleServices()
    {
        // in test setup 3 services have been added with ids 1, 2 and 3.
        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServicesDetails-IT-IT.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $package = Package::defaultPackage();
        $costs = $this->shippingMethodService->getShippingCosts('IT', '00118', 'IT', '00118', array($package));

        self::assertArrayHasKey(1, $costs, 'Shipping cost for one of the services missing!');
        self::assertArrayHasKey(2, $costs, 'Shipping cost for one of the services missing!');
        self::assertArrayHasKey(3, $costs, 'Shipping cost for one of the services missing!');

        self::assertEquals(6.28, $costs[1], 'Calculated cost is wrong!');
        self::assertEquals(7.94, $costs[2], 'Calculated cost is wrong!');
        self::assertEquals(9.04, $costs[3], 'Calculated cost is wrong!');
    }

    public function testGetCostFromProxyForMultiplePackages()
    {
        // this method has costs of 10.76
        $method = $this->addShippingMethod(20339);

        // first service from this response has id 20339 and total base price of 14.16
        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServiceDetails-MultipleParcels.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $firstPackage = new Package(1, 10, 10, 10);
        $secondPackage = new Package(10, 10, 10, 10);

        $cost = $this->shippingMethodService->getShippingCost(
            $method->getId(),
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
        $method = $this->addShippingMethod(20339);

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

        self::assertEquals(41.43, $costs[$method->getId()], 'Failed to get cost from API!');
    }

    public function testGetCostsForCheapestService()
    {
        // add method with several services and calculate cost. it should take the cheapest one.
        $this->shippingMethodService->add($this->getShippingServiceDetails(1, 'PSP', 'IT', 'FR', false, false, 4.11));
        $this->shippingMethodService->add($this->getShippingServiceDetails(2, 'PSP', 'IT', 'FR', false, false, 6.93));
        $this->shippingMethodService->add($this->getShippingServiceDetails(3, 'PSP', 'IT', 'FR', false, false, 2.1));
        // this should be avoided because destination is different from what we search below
        $this->shippingMethodService->add($this->getShippingServiceDetails(4, 'PSP', 'IT', 'DE', false, false, 1.5));
        $method = $this->shippingMethodService->add(
            $this->getShippingServiceDetails(5, 'PSP', 'IT', 'FR', false, false, 5.4)
        );
        $this->shippingMethodService->activate($method->getId());

        // avoid API costs
        $this->httpClient->setMockResponses(
            array(new HttpResponse(500, array(), ''), new HttpResponse(500, array(), ''))
        );

        $package = Package::defaultPackage();
        $this->checkShippingCostMatchesExpectedCost($method->getId(), array($package), 2.1, 'FR');
        $this->checkShippingCostMatchesExpectedCost($method->getId(), array($package), 1.5, 'DE');
    }

    public function testGetCostFallbackToShippingMethod()
    {
        // this method has costs of 10.76
        $serviceId = 20339;
        $method = $this->addShippingMethod($serviceId);

        $this->httpClient->setMockResponses(array(new HttpResponse(500, array(), '')));

        $packages = array(Package::defaultPackage());
        $cost = $this->shippingMethodService->getShippingCost(
            $method->getId(),
            'IT',
            '00118',
            'IT',
            '00118',
            $packages
        );

        $costs = $method->getShippingServices();
        self::assertEquals($costs[0]->basePrice, $cost, 'Failed to get default cost from local method!');
    }

    public function testGetCostsFallbackToShippingMethod()
    {
        $serviceId = 20339;
        $shippingMethod = $this->addShippingMethod($serviceId);

        $this->httpClient->setMockResponses(array(new HttpResponse(500, array(), '')));

        $packages = array(Package::defaultPackage());
        $costs = $this->shippingMethodService->getShippingCosts('IT', '00118', 'IT', '00118', $packages);

        $defaultCosts = $shippingMethod->getShippingServices();
        self::assertEquals(
            $costs[$shippingMethod->getId()],
            $defaultCosts[0]->basePrice,
            'Failed to get default cost from local method!'
        );
    }

    public function testGetCostNoFallback()
    {
        // this method has costs of 10.76
        $method = $this->addShippingMethod(1);

        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));

        $packages = array(Package::defaultPackage());
        $cost = $this->shippingMethodService->getShippingCost(
            $method->getId(),
            'IT',
            '00118',
            'IT',
            '00118',
            $packages
        );

        $costs = $method->getShippingServices();
        self::assertNotEquals($costs[0]->basePrice, $cost, 'Failed to get default cost!');
        self::assertEquals(0, $cost);
    }

    public function testGetCostsNoFallback()
    {
        $method = $this->addShippingMethod(1, false);

        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));

        $packages = array(Package::defaultPackage());
        $costs = $this->shippingMethodService->getShippingCosts('IT', '00118', 'IT', '00118', $packages);

        self::assertArrayNotHasKey($method->getId(), $costs);
    }

    public function testCalculateCostFixedPricingPolicy()
    {
        $shippingMethod = $this->prepareFixedPricePolicyShippingMethod(1, array(new FixedPricePolicy(0, 10, 12)));

        $this->httpClient->setMockResponses(array(new HttpResponse(404, array(), '')));
        $cost = $this->shippingMethodService->getShippingCost(
            $shippingMethod->getId(),
            'IT',
            '',
            'IT',
            '',
            array(Package::defaultPackage())
        );

        $costs = $shippingMethod->getShippingServices();
        // cost should be calculated, and not default
        self::assertNotEquals($costs[0]->basePrice, $cost, 'Default cost used when calculation should be performed!');
        self::assertEquals(12, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostsFixedPricingPolicy()
    {
        $serviceId = 20339;
        $shippingMethod = $this->prepareFixedPricePolicyShippingMethod(
            $serviceId,
            array(new FixedPricePolicy(0, 10, 12))
        );

        $this->httpClient->setMockResponses(array(new HttpResponse(404, array(), '')));
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array(Package::defaultPackage()));

        $defaultCosts = $shippingMethod->getShippingServices();
        self::assertNotEquals(
            $defaultCosts[0]->basePrice,
            $costs[$shippingMethod->getId()],
            'Default cost used when calculation should be performed!'
        );
        self::assertEquals(12, $costs[$shippingMethod->getId()], 'Calculated cost is wrong!');
    }

    public function testCalculateCostFixedPricingPolicyOutOfRange()
    {
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $shippingMethod = $this->prepareFixedPricePolicyShippingMethod(1, $fixedPricePolicies);

        $packages = array(new Package(100, 10, 10, 10));
        $this->httpClient->setMockResponses($this->getBadHttpResponses(2));
        $cost = $this->shippingMethodService->getShippingCost($shippingMethod->getId(), 'IT', '', 'IT', '', $packages);

        $costs = $shippingMethod->getShippingServices();
        // cost should be calculated, and not default
        self::assertNotEquals($costs[0]->basePrice, $cost, 'Default cost used when calculation should be performed!');
        self::assertEquals(12, $cost, 'Calculated cost is wrong!');

        $fixedPricePolicies[] = new FixedPricePolicy(10, 20, 10);
        $fixedPricePolicies[] = new FixedPricePolicy(20, 30, 8);
        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->shippingMethodService->getShippingCost($shippingMethod->getId(), 'IT', '', 'IT', '', $packages);
        self::assertEquals(8, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostsFixedPricingPolicyOutOfRange()
    {
        $serviceId = 20339;
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $shippingMethod = $this->prepareFixedPricePolicyShippingMethod($serviceId, $fixedPricePolicies);

        $package = new Package(100, 10, 10, 10);
        $this->httpClient->setMockResponses($this->getBadHttpResponses(2));
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));

        $defaultCosts = $shippingMethod->getShippingServices();
        self::assertNotEquals(
            $defaultCosts[0]->basePrice,
            $costs[$shippingMethod->getId()],
            'Default cost used when calculation should be performed!'
        );
        self::assertEquals(12, $costs[$shippingMethod->getId()], 'Calculated cost is wrong!');

        $fixedPricePolicies[] = new FixedPricePolicy(10, 20, 10);
        $fixedPricePolicies[] = new FixedPricePolicy(20, 30, 8);
        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));
        self::assertEquals(8, $costs[$shippingMethod->getId()], 'Calculated cost is wrong!');
    }

    public function testCalculateCostFixedPricingPolicyInRange()
    {
        $method = $this->prepareFixedPricePolicyShippingMethod(
            1,
            array(
                new FixedPricePolicy(0, 10, 12),
                new FixedPricePolicy(10, 20, 10),
                new FixedPricePolicy(20, 30, 8),
            )
        );
        $id = $method->getId();

        $package = new Package(8, 10, 10, 10);
        $this->httpClient->setMockResponses($this->getBadHttpResponses(5));
        $cost = $this->shippingMethodService->getShippingCost($id, 'IT', '', 'IT', '', array($package));
        self::assertEquals(12, $cost, 'Calculated cost is wrong!');

        $package->weight = 10;
        $cost = $this->shippingMethodService->getShippingCost($id, 'IT', '', 'IT', '', array($package));
        self::assertEquals(10, $cost, 'Calculated cost is wrong!');

        $package->weight = 14;
        $cost = $this->shippingMethodService->getShippingCost($id, 'IT', '', 'IT', '', array($package));
        self::assertEquals(10, $cost, 'Calculated cost is wrong!');

        $package->weight = 20;
        $cost = $this->shippingMethodService->getShippingCost($id, 'IT', '', 'IT', '', array($package));
        self::assertEquals(8, $cost, 'Calculated cost is wrong!');

        $package->weight = 25;
        $cost = $this->shippingMethodService->getShippingCost($id, 'IT', '', 'IT', '', array($package));
        self::assertEquals(8, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostsFixedPricingPolicyInRange()
    {
        $method = $this->prepareFixedPricePolicyShippingMethod(
            20339,
            array(
                new FixedPricePolicy(0, 10, 12),
                new FixedPricePolicy(10, 20, 10),
                new FixedPricePolicy(20, 30, 8),
            )
        );

        $package = new Package(8, 10, 10, 10);
        $this->httpClient->setMockResponses($this->getBadHttpResponses(5));
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));
        self::assertEquals(12, $costs[$method->getId()], 'Calculated cost is wrong!');

        $package->weight = 10;
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));
        self::assertEquals(10, $costs[$method->getId()], 'Calculated cost is wrong!');

        $package->weight = 14;
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));
        self::assertEquals(10, $costs[$method->getId()], 'Calculated cost is wrong!');

        $package->weight = 20;
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));
        self::assertEquals(8, $costs[$method->getId()], 'Calculated cost is wrong!');

        $package->weight = 25;
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array($package));
        self::assertEquals(8, $costs[$method->getId()], 'Calculated cost is wrong!');
    }

    public function testCalculateCostFixedPricingPolicyInRangeMultiple()
    {
        // this method has costs of 10.76
        $method = $this->prepareFixedPricePolicyShippingMethod(
            1,
            array(
                new FixedPricePolicy(0, 10, 12),
                new FixedPricePolicy(10, 20, 10),
                new FixedPricePolicy(20, 30, 8),
            )
        );

        $this->httpClient->setMockResponses($this->getBadHttpResponses(3));
        $packages = array();
        // First range.
        $firstPackage = Package::defaultPackage();
        $secondPackage = Package::defaultPackage();
        $firstPackage->weight = 2;
        $secondPackage->weight = 4;
        $packages[] = $firstPackage;
        $packages[] = $secondPackage;

        $this->checkShippingCostMatchesExpectedCost($method->getId(), $packages, 12);

        // Second range.
        $thirdPackage = Package::defaultPackage();
        $thirdPackage->weight = 10;
        $packages[] = $thirdPackage;

        $this->checkShippingCostMatchesExpectedCost($method->getId(), $packages, 10);

        // Third range.
        $fourthPackage = Package::defaultPackage();
        $fourthPackage->weight = 7;
        $packages[] = $fourthPackage;

        $this->checkShippingCostMatchesExpectedCost($method->getId(), $packages, 8);
    }

    public function testCalculateCostPercentPricingPolicyIncreased()
    {
        $method = $this->preparePercentPricePolicyShippingMethod(1, true);

        $package = Package::defaultPackage();
        $this->httpClient->setMockResponses($this->getBadHttpResponses(3));
        $cost = $this->shippingMethodService->getShippingCost($method->getId(), 'IT', '', 'IT', '', array($package));
        self::assertEquals(12.27, $cost, 'Calculated cost is wrong!');

        $method->setPercentPricePolicy(new PercentPricePolicy(true, 50));
        $this->shippingMethodService->save($method);

        $cost = $this->shippingMethodService->getShippingCost($method->getId(), 'IT', '', 'IT', '', array($package));
        self::assertEquals(16.14, $cost, 'Calculated cost is wrong!');

        $method->setPercentPricePolicy(new PercentPricePolicy(true, 120));
        $this->shippingMethodService->save($method);

        $cost = $this->shippingMethodService->getShippingCost($method->getId(), 'IT', '', 'IT', '', array($package));
        self::assertEquals(23.67, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostsPercentPricingPolicyIncreased()
    {
        $method = $this->preparePercentPricePolicyShippingMethod(20339, true);

        $this->httpClient->setMockResponses(array(new HttpResponse(404, array(), '')));
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', array(Package::defaultPackage()));
        self::assertEquals(12.27, $costs[$method->getId()], 'Calculated cost is wrong!');
    }

    public function testCalculateCostPercentPricingPolicyDecreased()
    {
        $method = $this->preparePercentPricePolicyShippingMethod();

        $this->httpClient->setMockResponses($this->getBadHttpResponses(3));

        $packages = array(Package::defaultPackage());
        $this->checkShippingCostMatchesExpectedCost($method->getId(), $packages, 9.25);

        $method->setPercentPricePolicy(new PercentPricePolicy(false, 50));
        $this->shippingMethodService->save($method);

        $this->checkShippingCostMatchesExpectedCost($method->getId(), $packages, 5.38);

        $method->setPercentPricePolicy(new PercentPricePolicy(false, 80));
        $this->shippingMethodService->save($method);

        $this->checkShippingCostMatchesExpectedCost($method->getId(), $packages, 2.15);
    }

    public function testCalculateCostsPercentPricingPolicyDecreased()
    {
        $method = $this->preparePercentPricePolicyShippingMethod(20339);

        $this->httpClient->setMockResponses(array(new HttpResponse(404, array(), '')));
        $this->checkShippingCostsMatchExpectedCost(array(Package::defaultPackage()), 9.25, $method->getId());
    }

    public function testNoMethodsCalculation()
    {
        self::assertEmpty(ShippingCostCalculator::getShippingCosts(array(), '', '', '', '', array()));
        foreach ($this->serviceIds as $serviceId) {
            $this->shippingMethodService->deactivate($serviceId);
        }

        self::assertEmpty($this->shippingMethodService->getShippingCosts('', '', '', '', array()));
    }

    public function testCostCalculationForUnknownDepartureAndDestination()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));

        $packages = array(Package::defaultPackage());
        $costs = $this->shippingMethodService->getShippingCosts('KK', '00118', 'IT', '00118', $packages);
        self::assertEmpty($costs);

        $costs = $this->shippingMethodService->getShippingCosts('IT', '00118', 'KK', '00118', $packages);
        self::assertEmpty($costs);
    }

    /**
     * @param int $methodId
     * @param Package[] $packages
     * @param float $expectedCost
     * @param string $to Country code.
     */
    protected function checkShippingCostMatchesExpectedCost($methodId, array $packages, $expectedCost, $to = 'IT')
    {
        $cost = $this->shippingMethodService->getShippingCost($methodId, 'IT', '', $to, '', $packages);

        self::assertEquals($expectedCost, $cost, 'Calculated cost is wrong!');
    }

    /**
     * @param Package[] $packages
     * @param float $expectedCost
     * @param int $methodId
     */
    protected function checkShippingCostsMatchExpectedCost(array $packages, $expectedCost, $methodId)
    {
        $costs = $this->shippingMethodService->getShippingCosts('IT', '', 'IT', '', $packages);

        self::assertEquals($expectedCost, $costs[$methodId], 'Calculated cost is wrong!');
    }

    /**
     * @param int $serviceId
     * @param FixedPricePolicy[] $fixedPricePolicies
     *
     * @return \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod
     */
    protected function prepareFixedPricePolicyShippingMethod($serviceId = 1, array $fixedPricePolicies = array())
    {
        $shippingMethod = $this->addShippingMethod($serviceId);

        $shippingMethod->setFixedPricePolicy($fixedPricePolicies);
        $this->shippingMethodService->save($shippingMethod);

        return $shippingMethod;
    }

    /**
     * @param int $serviceId
     * @param bool $increase
     * @param int $percent
     *
     * @return \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod
     */
    protected function preparePercentPricePolicyShippingMethod($serviceId = 1, $increase = false, $percent = 14)
    {
        $shippingMethod = $this->addShippingMethod($serviceId);

        $shippingMethod->setPercentPricePolicy(new PercentPricePolicy($increase, $percent));
        $this->shippingMethodService->save($shippingMethod);

        return $shippingMethod;
    }

    /**
     * @param $serviceId
     *
     * @param bool $active
     *
     * @return \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod
     */
    protected function addShippingMethod($serviceId, $active = true)
    {
        $shippingMethod = $this->shippingMethodService->add($this->getShippingServiceDetails($serviceId, 'PSP'));
        $shippingMethod->setActivated($active);
        $this->shippingMethodService->save($shippingMethod);

        return $shippingMethod;
    }

    private function getShippingServiceDetails(
        $id,
        $carrierName,
        $fromCountry = 'IT',
        $toCountry = 'IT',
        $originDropOff = false,
        $destinationDropOff = false,
        $basePrice = 10.76
    ) {
        $details = ShippingServiceDetails::fromArray(
            array(
                'id' => $id,
                'carrier_name' => $carrierName,
                'service_name' => 'test service',
                'currency' => 'EUR',
                'country' => $toCountry,
                'dropoff' => $originDropOff,
                'delivery_to_parcelshop' => $destinationDropOff,
                'category' => 'express',
                'transit_time' => '3 DAYS',
                'transit_hours' => 72,
                'first_estimated_delivery_date' => '2019-01-05',
                'price' => array(
                    'tax_price' => 3,
                    'base_price' => $basePrice,
                    'total_price' => $basePrice + 3,
                ),
            )
        );

        $details->departureCountry = $fromCountry;
        $details->destinationCountry = $toCountry;
        $details->national = $fromCountry === $toCountry;

        return $details;
    }

    private function getBadHttpResponses($number)
    {
        $responses = array();
        for ($counter = 0; $counter < $number; $counter++) {
            $responses[] = new HttpResponse(404, array(), '');
        }

        return $responses;
    }
}
