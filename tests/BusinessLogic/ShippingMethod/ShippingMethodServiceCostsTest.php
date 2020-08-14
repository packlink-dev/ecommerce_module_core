<?php

namespace Logeecom\Tests\BusinessLogic\ShippingMethod;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
use Packlink\BusinessLogic\ShippingMethod\ShippingCostCalculator;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class ShippingMethodServiceCostsTest
 *
 * @package Packlink\Tests\BusinessLogic\Tasks
 */
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
        TestFrontDtoFactory::register(ShippingPricePolicy::CLASS_KEY, ShippingPricePolicy::CLASS_NAME);

        $me = $this;
        $this->shopConfig->setAuthorizationToken('test_token');

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
            PackageTransformer::CLASS_NAME,
            function () {
                return PackageTransformer::getInstance();
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
        $cost = $this->getShippingCosts(null, 20339, '00118');

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
        $costs = $this->getShippingCosts();

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

        $cost = $this->getShippingCosts(null, $shippingMethod->getId(), '00118');

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

        $cost = $this->getShippingCosts(null, 20339, '00118');

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

        $costs = $this->getShippingCosts(null, '', '00118');

        self::assertEquals(5.06, $costs[$shippingMethod->getId()], 'Failed to get cost from API!');
    }

    public function testGetCostsFromProxyForMultipleServices()
    {
        // in test setup 3 services have been added with ids 1, 2 and 3.
        $response = file_get_contents(
            __DIR__ . '/../Common/ApiResponses/ShippingServices/ShippingServicesDetails-IT-IT.json'
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $costs = $this->getShippingCosts(null, '', '00118');

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

        $cost = $this->getShippingCosts(array($firstPackage, $secondPackage), $method->getId(), '00118');

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
        $packages = array($firstPackage, $secondPackage, $secondPackage, $secondPackage);

        $costs = $this->getShippingCosts($packages, '', '00118');

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

        $cost = $this->getShippingCosts(null, $method->getId(), '00118');

        $costs = $method->getShippingServices();
        self::assertEquals($costs[0]->basePrice, $cost, 'Failed to get default cost from local method!');
    }

    public function testGetCostsFallbackToShippingMethod()
    {
        $serviceId = 20339;
        $shippingMethod = $this->addShippingMethod($serviceId);

        $this->httpClient->setMockResponses(array(new HttpResponse(500, array(), '')));

        $costs = $this->getShippingCosts(null, '', '00118');

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

        $cost = $this->getShippingCosts(null, $method->getId(), '00118');

        $costs = $method->getShippingServices();
        self::assertNotEquals($costs[0]->basePrice, $cost, 'Failed to get default cost!');
        self::assertEquals(0, $cost);
    }

    public function testGetCostsNoFallback()
    {
        $method = $this->addShippingMethod(1, false);

        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));

        $costs = $this->getShippingCosts(null, '', '00118');

        self::assertArrayNotHasKey($method->getId(), $costs);
    }

    public function testCalculateCostFixedPricingPolicy()
    {
        $fixedPricePolicies[] = array(0, 10, 12);
        $shippingMethod = $this->prepareFixedPricePolicyShippingMethod(1, $fixedPricePolicies);

        $this->httpClient->setMockResponses(array(new HttpResponse(404, array(), '')));
        $cost = $this->getShippingCosts(null, $shippingMethod->getId());

        $costs = $shippingMethod->getShippingServices();
        // cost should be calculated, and not default
        self::assertNotEquals($costs[0]->basePrice, $cost, 'Default cost used when calculation should be performed!');
        self::assertEquals(12, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateFixedPricingPolicyOutOfBounds()
    {
        $fixedPricePolicies[] = array(10, 20, 12);
        $fixedPricePolicies[] = array(5, 10, 12);
        $shippingMethod = $this->prepareFixedPricePolicyShippingMethod(1, $fixedPricePolicies);
        $shippingMethod->setUsePacklinkPriceIfNotInRange(false);
        $this->shippingMethodService->save($shippingMethod);

        $this->httpClient->setMockResponses(array(new HttpResponse(404, array(), '')));
        $cost = $this->getShippingCosts(null, $shippingMethod->getId(), '00127', 'IT', 2);

        $this->assertEmpty($cost);
    }

    public function testCalculateCostsFixedPricingByWeightPolicy()
    {
        $this->calculateCostsFixedPricingPolicy(true);
    }

    public function testCalculateCostsFixedPricingByValuePolicy()
    {
        $this->calculateCostsFixedPricingPolicy(false);
    }

    public function testCalculateCostsByWeightAndPriceRange()
    {
        $shippingMethod = $this->getMethodWithBothRanges();

        $cost = $this->getShippingCosts(null, $shippingMethod->getId());

        $this->assertEquals(5.55, $cost, 'Calculated cost should be taken.');
    }

    public function testCalculateCostsByWeightAndPriceRangeInvalidWeight()
    {
        $shippingMethod = $this->getMethodWithBothRanges();

        // weight is out of range
        $packages = array(new Package(5, 20, 20, 20));
        $cost = $this->getShippingCosts($packages, $shippingMethod->getId());

        $this->assertEquals(10.76, $cost, 'Calculated cost should NOT be taken.');
    }

    public function testCalculateCostsByWeightAndPriceRangeInvalidPrice()
    {
        $shippingMethod = $this->getMethodWithBothRanges();

        // price is out of range
        $cost = $this->getShippingCosts(null, $shippingMethod->getId(), '00118', 'IT', 100);

        $this->assertEquals(10.76, $cost, 'Calculated cost should NOT be taken.');
    }

    public function testCalculateCostFixedPricingPolicyOutOfRange()
    {
        $fixedPricePolicies[] = array(0, 10, 12);
        $shippingMethod = $this->prepareFixedPricePolicyShippingMethod(1, $fixedPricePolicies);

        $packages = array(new Package(100, 10, 10, 10));
        $this->httpClient->setMockResponses($this->getBadHttpResponses(2));
        $cost = $this->getShippingCosts($packages, $shippingMethod->getId());

        $services = $shippingMethod->getShippingServices();
        // default cost should be used when out of range
        self::assertEquals($services[0]->basePrice, $cost, 'Calculation should not be performed!');

        $shippingMethod->addPricingPolicy($this->getFixedPricePolicy(true, 10, 20, 10));
        $shippingMethod->addPricingPolicy($this->getFixedPricePolicy(true, 20, 30, 8));
        $this->shippingMethodService->save($shippingMethod);

        $cost = $this->getShippingCosts($packages, $shippingMethod->getId());
        self::assertEquals($services[0]->basePrice, $cost, 'Calculation should not be performed!');
    }

    public function testCalculateCostFixedPricingPolicyInRange()
    {
        $fixedPricePolicies[] = array(0, 10, 12);
        $fixedPricePolicies[] = array(10, 20, 10);
        $fixedPricePolicies[] = array(20, 30, 8);
        $method = $this->prepareFixedPricePolicyShippingMethod(1, $fixedPricePolicies);
        $id = $method->getId();

        $packages = array(new Package(8, 10, 10, 10));
        $this->httpClient->setMockResponses($this->getBadHttpResponses(5));
        $cost = $this->getShippingCosts($packages, $id);
        self::assertEquals(12, $cost, 'Calculated cost is wrong!');

        $packages[0]->weight = 10;
        $cost = $this->getShippingCosts($packages, $id);
        self::assertEquals(12, $cost, 'Calculated cost is wrong!');

        $packages[0]->weight = 14;
        $cost = $this->getShippingCosts($packages, $id);
        self::assertEquals(10, $cost, 'Calculated cost is wrong!');

        $packages[0]->weight = 20;
        $cost = $this->getShippingCosts($packages, $id);
        self::assertEquals(10, $cost, 'Calculated cost is wrong!');

        $packages[0]->weight = 25;
        $cost = $this->getShippingCosts($packages, $id);
        self::assertEquals(8, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostsFixedPricingPolicyInRange()
    {
        $fixedPricePolicies[] = array(0, 10, 12);
        $fixedPricePolicies[] = array(10, 20, 10);
        $fixedPricePolicies[] = array(20, 30, 8);
        $method = $this->prepareFixedPricePolicyShippingMethod(20339, $fixedPricePolicies);

        $packages = array(new Package(8, 10, 10, 10));
        $this->httpClient->setMockResponses($this->getBadHttpResponses(5));
        $costs = $this->getShippingCosts($packages);
        self::assertEquals(12, $costs[$method->getId()], 'Calculated cost is wrong!');

        $packages[0]->weight = 10;
        $costs = $this->getShippingCosts($packages);
        self::assertEquals(12, $costs[$method->getId()], 'Calculated cost is wrong!');

        $packages[0]->weight = 14;
        $costs = $this->getShippingCosts($packages);
        self::assertEquals(10, $costs[$method->getId()], 'Calculated cost is wrong!');

        $packages[0]->weight = 20;
        $costs = $this->getShippingCosts($packages);
        self::assertEquals(10, $costs[$method->getId()], 'Calculated cost is wrong!');

        $packages[0]->weight = 25;
        $costs = $this->getShippingCosts($packages);
        self::assertEquals(8, $costs[$method->getId()], 'Calculated cost is wrong!');
    }

    public function testCalculateCostFixedPricingPolicyInRangeMultiple()
    {
        // this method has costs of 10.76
        $fixedPricePolicies[] = array(0, 10, 12);
        $fixedPricePolicies[] = array(10, 20, 10);
        $fixedPricePolicies[] = array(20, 30, 8);
        $method = $this->prepareFixedPricePolicyShippingMethod(20339, $fixedPricePolicies);

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

        $this->httpClient->setMockResponses($this->getBadHttpResponses(3));
        $cost = $this->getShippingCosts(null, $method->getId());
        self::assertEquals(12.27, $cost, 'Calculated cost is wrong!');

        $method->resetPricingPolicies();
        $method->addPricingPolicy($this->getPercentPricePolicy(50, true));
        $this->shippingMethodService->save($method);

        $cost = $this->getShippingCosts(null, $method->getId());
        self::assertEquals(16.14, $cost, 'Calculated cost is wrong!');

        $method->resetPricingPolicies();
        $method->addPricingPolicy($this->getPercentPricePolicy(120, true));
        $this->shippingMethodService->save($method);

        $cost = $this->getShippingCosts(null, $method->getId());
        self::assertEquals(23.67, $cost, 'Calculated cost is wrong!');
    }

    public function testCalculateCostsPercentPricingPolicyIncreased()
    {
        $method = $this->preparePercentPricePolicyShippingMethod(20339, true);

        $this->httpClient->setMockResponses(array(new HttpResponse(404, array(), '')));
        $costs = $this->getShippingCosts();
        self::assertEquals(12.27, $costs[$method->getId()], 'Calculated cost is wrong!');
    }

    public function testCalculateCostPercentPricingPolicyDecreased()
    {
        $method = $this->preparePercentPricePolicyShippingMethod();
        $id = $method->getId();

        $this->httpClient->setMockResponses($this->getBadHttpResponses(3));

        $packages = array(Package::defaultPackage());
        $this->checkShippingCostMatchesExpectedCost($id, $packages, 9.25);

        $method->resetPricingPolicies();
        $method->addPricingPolicy($this->getPercentPricePolicy(50, false));
        $this->shippingMethodService->save($method);

        $this->checkShippingCostMatchesExpectedCost($id, $packages, 5.38);

        $method->resetPricingPolicies();
        $method->addPricingPolicy($this->getPercentPricePolicy(80, false));
        $this->shippingMethodService->save($method);
        $this->checkShippingCostMatchesExpectedCost($method->getId(), $packages, 2.15);
    }

    public function testCalculateCostsPercentPricingPolicyDecreased()
    {
        $method = $this->preparePercentPricePolicyShippingMethod(20339);

        $this->httpClient->setMockResponses($this->getBadHttpResponses(1));
        $this->checkShippingCostsMatchExpectedCost(array(Package::defaultPackage()), 9.25, $method->getId());
    }

    public function testNoMethodsCalculation()
    {
        self::assertEmpty(ShippingCostCalculator::getShippingCosts(array(), '', '', '', '', array(), 10));
        foreach ($this->serviceIds as $serviceId) {
            $this->shippingMethodService->deactivate($serviceId);
        }

        self::assertEmpty($this->shippingMethodService->getShippingCosts('', '', '', '', array(), 10));
    }

    public function testCostCalculationForUnknownDepartureAndDestination()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '')));

        $packages = array(Package::defaultPackage());
        $costs = $this->shippingMethodService->getShippingCosts('KK', '00118', 'IT', '00118', $packages, 10);
        self::assertEmpty($costs);

        $costs = $this->shippingMethodService->getShippingCosts('IT', '00118', 'KK', '00118', $packages, 10);
        self::assertEmpty($costs);
    }

    /**
     * @dataProvider wrongParametersProvider
     *
     * @param $fromCountry
     * @param $fromZip
     * @param $toCountry
     * @param $toZip
     * @param $packages
     */
    public function testMissingShippingParameters($fromCountry, $fromZip, $toCountry, $toZip, $packages)
    {
        $result = $this->shippingMethodService->getShippingCosts(
            $fromCountry,
            $fromZip,
            $toCountry,
            $toZip,
            $packages,
            10
        );

        $this->assertEmpty($result);
    }

    /**
     * Calculates fixed price cost.
     *
     * @param float $byWeight
     */
    private function calculateCostsFixedPricingPolicy($byWeight)
    {
        $serviceId = 20339;
        $fixedPricePolicies[] = array(0, 10, 12);
        $shippingMethod = $this->prepareFixedPricePolicyShippingMethod($serviceId, $fixedPricePolicies, $byWeight);

        $this->httpClient->setMockResponses(array(new HttpResponse(404, array(), '')));
        $costs = $this->getShippingCosts();

        $defaultCosts = $shippingMethod->getShippingServices();
        self::assertNotEquals(
            $defaultCosts[0]->basePrice,
            $costs[$shippingMethod->getId()],
            'Default cost used when calculation should be performed!'
        );
        self::assertEquals(12, $costs[$shippingMethod->getId()], 'Calculated cost is wrong!');
    }

    /**
     * @param int $methodId
     * @param Package[] $packages
     * @param float $expectedCost
     * @param string $to Country code.
     */
    protected function checkShippingCostMatchesExpectedCost($methodId, array $packages, $expectedCost, $to = 'IT')
    {
        $cost = $this->getShippingCosts($packages, $methodId, '00127', $to);

        self::assertEquals($expectedCost, $cost, 'Calculated cost is wrong!');
    }

    /**
     * @param Package[] $packages
     * @param float $expectedCost
     * @param int $methodId
     */
    protected function checkShippingCostsMatchExpectedCost(array $packages, $expectedCost, $methodId)
    {
        $costs = $this->getShippingCosts($packages, '', '00127');

        self::assertEquals($expectedCost, $costs[$methodId], 'Calculated cost is wrong!');
    }

    /**
     * @param int $serviceId
     * @param array $prices
     * @param bool $byWeight
     *
     * @return \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod
     */
    protected function prepareFixedPricePolicyShippingMethod(
        $serviceId = 1,
        array $prices = array(),
        $byWeight = true
    ) {
        $shippingMethod = $this->addShippingMethod($serviceId);

        foreach ($prices as $price) {
            $shippingMethod->addPricingPolicy($this->getFixedPricePolicy($byWeight, $price[0], $price[1], $price[2]));
        }

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
        $shippingMethod->addPricingPolicy($this->getPercentPricePolicy($percent, $increase));

        $this->shippingMethodService->save($shippingMethod);

        return $shippingMethod;
    }

    /**
     * @param $serviceId
     * @param bool $active
     * @param string $carrier
     *
     * @return \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod
     */
    protected function addShippingMethod($serviceId, $active = true, $carrier = 'PSP')
    {
        $shippingMethod = $this->shippingMethodService->add($this->getShippingServiceDetails($serviceId, $carrier));
        $shippingMethod->setActivated($active);
        $this->shippingMethodService->save($shippingMethod);

        return $shippingMethod;
    }

    protected function getMethodWithBothRanges(
        $fallbackToDefault = true,
        $fromPrice = 5,
        $toPrice = 15,
        $fromWeight = 0.5,
        $toWeight = 1.5,
        $fixedPrice = 5.55
    ) {
        /** @noinspection PhpUnhandledExceptionInspection */
        $policy = ShippingPricePolicy::fromArray(
            array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE_AND_WEIGHT,
                'from_price' => $fromPrice,
                'to_price' => $toPrice,
                'from_weight' => $fromWeight,
                'to_weight' => $toWeight,
                'pricing_policy' => ShippingPricePolicy::POLICY_FIXED_PRICE,
                'fixed_price' => $fixedPrice,
            )
        );
        $shippingMethod = $this->addShippingMethod(1);
        $shippingMethod->setUsePacklinkPriceIfNotInRange($fallbackToDefault);
        $shippingMethod->addPricingPolicy($policy);
        $this->shippingMethodService->save($shippingMethod);

        return $shippingMethod;
    }

    protected function getShippingCosts(array $packages = null, $id = '', $zip = '00127', $to = 'IT', $total = 9.9)
    {
        if (empty($packages)) {
            $packages = array(Package::defaultPackage());
        }

        if ($id) {
            return $this->shippingMethodService->getShippingCost($id, 'IT', $zip, $to, $zip, $packages, $total);
        }

        return $this->shippingMethodService->getShippingCosts('IT', $zip, $to, $zip, $packages, $total);
    }

    protected function getPercentPricePolicy($percent, $increase = false)
    {
        return $this->getPricingPolicy(
            ShippingPricePolicy::RANGE_PRICE,
            0,
            10,
            ShippingPricePolicy::POLICY_PACKLINK_ADJUST,
            $percent,
            $increase
        );
    }

    protected function getFixedPricePolicy($byWeight, $from, $to, $price)
    {
        return $this->getPricingPolicy(
            $byWeight ? ShippingPricePolicy::RANGE_WEIGHT : ShippingPricePolicy::RANGE_PRICE,
            $from,
            $to,
            ShippingPricePolicy::POLICY_FIXED_PRICE,
            0,
            false,
            $price
        );
    }

    protected function getPricingPolicy(
        $rangeType = ShippingPricePolicy::RANGE_PRICE,
        $from = 0,
        $to = 0,
        $policy = ShippingPricePolicy::POLICY_PACKLINK,
        $changePercent = 50,
        $increase = true,
        $fixedPrice = 20
    ) {
        /** @noinspection PhpUnhandledExceptionInspection */
        return ShippingPricePolicy::fromArray(
            array(
                'range_type' => $rangeType,
                'from_price' => $from,
                'to_price' => $to,
                'from_weight' => $from,
                'to_weight' => $to,
                'pricing_policy' => $policy,
                'increase' => $increase,
                'change_percent' => $changePercent,
                'fixed_price' => $fixedPrice,
            )
        );
    }

    /**
     * Retrieves shipping service details.
     *
     * @param int $id
     * @param string $carrierName
     * @param string $fromCountry
     * @param string $toCountry
     * @param bool $originDropOff
     * @param bool $destinationDropOff
     * @param float $basePrice
     *
     * @return \Logeecom\Infrastructure\Data\DataTransferObject |\Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails
     */
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

    /**
     * Retrieves invalid response.
     *
     * @param int $number
     *
     * @return array
     */
    private function getBadHttpResponses($number)
    {
        $responses = array();
        for ($counter = 0; $counter < $number; $counter++) {
            $responses[] = new HttpResponse(404, array(), '');
        }

        return $responses;
    }

    /**
     * Retrieves parcel with wrong parameters.
     *
     * @return array
     */
    public function wrongParametersProvider()
    {
        return array(
            array('IT', '', 'IT', '00127', array(new Package())),
            array('', '00127', 'IT', '00127', array(new Package())),
            array('IT', '00127', null, '00127', array(new Package())),
            array('IT', '00127', 'IT', '', array(new Package())),
        );
    }
}
