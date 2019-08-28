<?php

namespace Logeecom\Tests\BusinessLogic\ShippingMethod;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Models\FixedPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\PercentPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
use Packlink\BusinessLogic\ShippingMethod\ShippingCostCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Class ShippingMethodEntityTest.
 *
 * @package Logeecom\Tests\BusinessLogic\ShippingMethod
 */
class ShippingMethodEntityTest extends TestCase
{
    /**
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient
     */
    public $httpClient;

    protected function setUp()
    {
        parent::setUp();

        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());

        $this->httpClient = new TestHttpClient();
        $self = $this;

        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($self) {
                return $self->httpClient;
            }
        );

        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($self) {
                /** @var \Packlink\BusinessLogic\Configuration $config */
                $config = TestServiceRegister::getService(Configuration::CLASS_NAME);

                return new Proxy($config, $self->httpClient);
            }
        );

        TestServiceRegister::registerService(
            PackageTransformer::CLASS_NAME,
            function () {
                return PackageTransformer::getInstance();
            }
        );
    }

    public function testProperties()
    {
        $method = new ShippingMethod();
        $method->setCarrierName('DPD');
        self::assertEquals('DPD', $method->getCarrierName());
        $method->setEnabled(false);
        self::assertFalse($method->isEnabled());
        $method->setActivated(true);
        self::assertTrue($method->isActivated());
        $method->setLogoUrl('https://packlink.com');
        self::assertEquals('https://packlink.com', $method->getLogoUrl());
        $method->setDisplayLogo(false);
        self::assertFalse($method->isDisplayLogo());
        $method->setDepartureDropOff(true);
        self::assertTrue($method->isDepartureDropOff());
        $method->setDestinationDropOff(true);
        self::assertTrue($method->isDestinationDropOff());
        $method->setExpressDelivery(true);
        self::assertTrue($method->isExpressDelivery());
        $method->setDeliveryTime('2 DAYS');
        self::assertEquals('2 DAYS', $method->getDeliveryTime());
        $method->setNational(true);
        self::assertTrue($method->isNational());
        $method->setTaxClass(1);
        self::assertEquals(1, $method->getTaxClass());

        // default title
        self::assertEquals('DPD - 2 DAYS pick up', $method->getTitle());
        $method->setDestinationDropOff(false);
        self::assertEquals('DPD - 2 DAYS delivery', $method->getTitle());
        $method->setTitle('title');
        self::assertEquals('title', $method->getTitle());
    }

    public function testDefaultPricingPolicy()
    {
        $method = new ShippingMethod();

        self::assertEmpty($method->getFixedPriceByWeightPolicy());
        self::assertEmpty($method->getFixedPriceByValuePolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PACKLINK, $method->getPricingPolicy());
    }

    public function testFixedPricingByWeightPolicyOneValid()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);

        self::assertNotEmpty($method->getFixedPriceByWeightPolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEmpty($method->getFixedPriceByValuePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT, $method->getPricingPolicy());
    }

    public function testFixedPricingByValuePolicyOneValid()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $method->setFixedPriceByValuePolicy($fixedPricePolicies);

        self::assertNotEmpty($method->getFixedPriceByValuePolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEmpty($method->getFixedPriceByWeightPolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE, $method->getPricingPolicy());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFixedPricingPolicyValidationAmountNotSetOnFirst()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 0);

        // amount must be set
        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFixedPricingPolicyValidationNegativeAmountOnFirst()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, -10);

        // amount must be positive
        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFixedPricingPolicyValidationZeroAmountOnFirst()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 0);

        // amount must be positive
        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFixedPricingPolicyValidationFromNotZeroOnFirst()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(3, 10, 10);

        // from for first policy must be 0
        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFixedPricingPolicyValidationInvalidFromBetweenPolicies()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(11, 13, 10);

        // second policy must have "from" bigger from previous for exactly 0.001
        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFixedPricingPolicyValidationNegativeAmountOnSecondPolicy()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 13, -10);

        // second policy must have amount bigger than 0
        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);
    }

    public function testFixedPricingPolicyValidationValidZeroAmountOnFirst()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 0);

        $method->setFixedPriceByValuePolicy($fixedPricePolicies);
        self::assertNotEmpty($method->getFixedPriceByValuePolicy());
        self::assertCount(1, $method->getFixedPriceByValuePolicy());
    }

    public function testFixedPricingPolicyValidationValidZeroAmountOnLast()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 10);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 100, 0);

        $method->setFixedPriceByValuePolicy($fixedPricePolicies);
        self::assertNotEmpty($method->getFixedPriceByValuePolicy());
        self::assertCount(2, $method->getFixedPriceByValuePolicy());
    }

    public function testFixedPricingByWeightPolicyValidationValidTwoPolicies()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 13, 10);

        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);
        self::assertNotEmpty($method->getFixedPriceByWeightPolicy());
        self::assertCount(2, $method->getFixedPriceByWeightPolicy());
    }

    public function testFixedPricingByWeightPolicyValidationValidDifferentOrder()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(10, 13, 10);
        $fixedPricePolicies[] = new FixedPricePolicy(13, 50, 6);
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);
        $policies = $method->getFixedPriceByWeightPolicy();
        self::assertNotEmpty($policies);
        self::assertCount(3, $policies);
    }

    public function testFixedPricingByWeightPolicyValidationValidTwoPoliciesSecondWithoutUpperBound()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 20, 10);

        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);
        self::assertNotEmpty($method->getFixedPriceByWeightPolicy());
        self::assertCount(2, $method->getFixedPriceByWeightPolicy());
    }

    public function testFixedPricingPolicyValidationValidBulk()
    {
        $method = new ShippingMethod();

        /** @var FixedPricePolicy[] $fixedPricePolicies */
        $fixedPricePolicies[] = new FixedPricePolicy(0, 13, 1000);

        for ($i = 1; $i < 100; $i++) {
            $fixedPricePolicies[] = new FixedPricePolicy(
                $fixedPricePolicies[$i - 1]->to,
                $fixedPricePolicies[$i - 1]->to + 10,
                $fixedPricePolicies[$i - 1]->amount - 10
            );
        }

        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);
        self::assertCount(100, $method->getFixedPriceByWeightPolicy());
    }

    public function testPercentPricingPolicy()
    {
        $method = new ShippingMethod();

        $policy = new PercentPricePolicy(true, 10);
        $method->setPercentPricePolicy($policy);

        self::assertNotEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PERCENT, $method->getPricingPolicy());
        self::assertEmpty($method->getFixedPriceByWeightPolicy());

        $policy = new PercentPricePolicy(false, 10);
        $method->setPercentPricePolicy($policy);

        self::assertNotEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PERCENT, $method->getPricingPolicy());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPercentPricingPolicyZeroAmount()
    {
        $method = new ShippingMethod();

        $policy = new PercentPricePolicy(false, 0);

        $method->setPercentPricePolicy($policy);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPercentPricingPolicyNegativeAmount()
    {
        $method = new ShippingMethod();

        $policy = new PercentPricePolicy(true, -10);

        $method->setPercentPricePolicy($policy);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPercentPricingPolicyDecreaseFor100()
    {
        $method = new ShippingMethod();

        $policy = new PercentPricePolicy(false, 100);

        $method->setPercentPricePolicy($policy);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPercentPricingPolicyDecreaseForMoreThan100()
    {
        $method = new ShippingMethod();

        $policy = new PercentPricePolicy(false, 120);

        $method->setPercentPricePolicy($policy);
    }

    public function testPercentPricingPolicyAfterFixedPricePolicy()
    {
        $method = new ShippingMethod();

        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);

        self::assertNotEmpty($method->getFixedPriceByWeightPolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT, $method->getPricingPolicy());

        $method->setPercentPricePolicy(new PercentPricePolicy(true, 10));

        self::assertNotEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PERCENT, $method->getPricingPolicy());
        self::assertEmpty($method->getFixedPriceByWeightPolicy());
    }

    public function testFixedPricingPolicyAfterPercentPricePolicy()
    {
        $method = new ShippingMethod();

        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $method->setPercentPricePolicy(new PercentPricePolicy(true, 10));

        self::assertNotEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PERCENT, $method->getPricingPolicy());
        self::assertEmpty($method->getFixedPriceByWeightPolicy());

        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);

        self::assertNotEmpty($method->getFixedPriceByWeightPolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT, $method->getPricingPolicy());
    }

    public function testResetAfterFixedPricingPolicy()
    {
        $method = new ShippingMethod();

        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);

        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);

        self::assertNotEmpty($method->getFixedPriceByWeightPolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT, $method->getPricingPolicy());

        $method->setPacklinkPricePolicy();
        self::assertEmpty($method->getFixedPriceByWeightPolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PACKLINK, $method->getPricingPolicy());
    }

    public function testResetAfterPercentPricingPolicy()
    {
        $method = new ShippingMethod();
        $method->setPercentPricePolicy(new PercentPricePolicy(true, 10));

        self::assertNotEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PERCENT, $method->getPricingPolicy());
        self::assertEmpty($method->getFixedPriceByWeightPolicy());

        $method->setPacklinkPricePolicy();
        self::assertEmpty($method->getFixedPriceByWeightPolicy());
        self::assertEmpty($method->getPercentPricePolicy());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PACKLINK, $method->getPricingPolicy());
    }

    public function testToArrayPacklinkPricingPolicy()
    {
        $this->assertBasicDataToArray();
    }

    public function testToArrayPercentPricingPolicy()
    {
        $method = $this->assertBasicDataToArray();

        $policy = new PercentPricePolicy(true, 10);
        $method->setPercentPricePolicy($policy);

        $result = $method->toArray();
        self::assertEquals(ShippingMethod::PRICING_POLICY_PERCENT, $result['pricingPolicy']);
        self::assertEquals($policy->increase, $result['percentPricePolicy']['increase']);
        self::assertEquals($policy->amount, $result['percentPricePolicy']['amount']);
    }

    public function testToArrayFixedPricingByWeightPolicy()
    {
        $method = $this->assertBasicDataToArray();

        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 13, 10);
        $method->setFixedPriceByWeightPolicy($fixedPricePolicies);

        $result = $method->toArray();
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT, $result['pricingPolicy']);
        self::assertCount(2, $result['fixedPriceByWeightPolicy']);
        self::assertEquals(0, $result['fixedPriceByWeightPolicy'][0]['from']);
        self::assertEquals(10, $result['fixedPriceByWeightPolicy'][1]['from']);
    }

    public function testToArrayFixedPricingByValuePolicy()
    {
        $method = $this->assertBasicDataToArray();

        $fixedPricePolicies[] = new FixedPricePolicy(0, 10, 12);
        $fixedPricePolicies[] = new FixedPricePolicy(10, 13, 10);
        $method->setFixedPriceByValuePolicy($fixedPricePolicies);

        $result = $method->toArray();
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE, $result['pricingPolicy']);
        self::assertCount(2, $result['fixedPriceByValuePolicy']);
        self::assertEquals(0, $result['fixedPriceByValuePolicy'][0]['from']);
        self::assertEquals(10, $result['fixedPriceByValuePolicy'][1]['from']);
    }

    public function testFromArrayShippingCosts()
    {
        $data = array(
            'serviceId' => '20339',
            'serviceName' => 'test',
            'departure' => 'IT',
            'destination' => 'DE',
            'totalPrice' => 3,
            'basePrice' => 2,
            'taxPrice' => 1,
        );

        $method = ShippingService::fromArray($data);
        self::assertEquals('20339', $method->serviceId);
        self::assertEquals('test', $method->serviceName);
        self::assertEquals(3, $method->totalPrice);
        self::assertEquals(2, $method->basePrice);
        self::assertEquals(1, $method->taxPrice);
        self::assertEquals('IT', $method->departureCountry);
        self::assertEquals('DE', $method->destinationCountry);
    }

    public function testFromArrayShippingMethodShippingCosts()
    {
        $data = $this->getShippingMethodData();

        $method = ShippingMethod::fromArray($data);
        $costs = $method->getShippingServices();
        self::assertCount(1, $costs);
        self::assertEquals(3, $costs[0]->totalPrice);
        self::assertEquals(2, $costs[0]->basePrice);
        self::assertEquals(1, $costs[0]->taxPrice);

        $method->setShippingServices($costs);
        $costs = $method->getShippingServices();
        self::assertCount(1, $costs);
    }

    public function testFromArrayPacklinkPricingPolicy()
    {
        $data = $this->getShippingMethodData();

        $method = ShippingMethod::fromArray($data);
        self::assertEquals($data['carrierName'], $method->getCarrierName());
        self::assertEquals($data['title'], $method->getTitle());
        self::assertEquals($data['enabled'], $method->isEnabled());
        self::assertEquals($data['activated'], $method->isActivated());
        self::assertEquals($data['logoUrl'], $method->getLogoUrl());
        self::assertEquals($data['displayLogo'], $method->isDisplayLogo());
        self::assertEquals($data['departureDropOff'], $method->isDepartureDropOff());
        self::assertEquals($data['destinationDropOff'], $method->isDestinationDropOff());
        self::assertEquals($data['expressDelivery'], $method->isExpressDelivery());
        self::assertEquals($data['deliveryTime'], $method->getDeliveryTime());
        self::assertEquals($data['national'], $method->isNational());
        self::assertEquals(ShippingMethod::PRICING_POLICY_PACKLINK, $method->getPricingPolicy());
    }

    public function testFromArrayPercentPricingPolicy()
    {
        $data = $this->getShippingMethodData();
        $data['percentPricePolicy']['increase'] = false;
        $data['percentPricePolicy']['amount'] = 20;

        $method = ShippingMethod::fromArray($data);
        $policy = $method->getPercentPricePolicy();
        self::assertEquals(ShippingMethod::PRICING_POLICY_PERCENT, $method->getPricingPolicy());
        self::assertEquals(false, $policy->increase);
        self::assertEquals(20, $policy->amount);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFromArrayInvalidPercentPricingPolicy()
    {
        $data = $this->getShippingMethodData();
        $data['percentPricePolicy']['increase'] = false;
        $data['percentPricePolicy']['amount'] = 200;

        ShippingMethod::fromArray($data);
    }

    public function testFromArrayFixedPricingByWeightPolicy()
    {
        $data = $this->getShippingMethodData();
        $data['fixedPriceByWeightPolicy'][0] = array(
            'from' => 10,
            'to' => 20,
            'amount' => 100,
        );
        $data['fixedPriceByWeightPolicy'][1] = array(
            'from' => 0,
            'to' => 10,
            'amount' => 120,
        );

        $method = ShippingMethod::fromArray($data);
        $policy = $method->getFixedPriceByWeightPolicy();
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT, $method->getPricingPolicy());
        self::assertCount(2, $policy);
        self::assertEquals(0, $policy[0]->from);
        self::assertEquals(10, $policy[1]->from);
    }

    public function testFromArrayFixedPricingByValuePolicy()
    {
        $data = $this->getShippingMethodData();
        $data['fixedPriceByValuePolicy'][0] = array(
            'from' => 10,
            'to' => 20,
            'amount' => 100,
        );
        $data['fixedPriceByValuePolicy'][1] = array(
            'from' => 0,
            'to' => 10,
            'amount' => 120,
        );

        $method = ShippingMethod::fromArray($data);
        $policy = $method->getFixedPriceByValuePolicy();
        self::assertEquals(ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE, $method->getPricingPolicy());
        self::assertCount(2, $policy);
        self::assertEquals(0, $policy[0]->from);
        self::assertEquals(10, $policy[1]->from);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFromArrayInvalidFixedPricingPolicy()
    {
        $data = $this->getShippingMethodData();
        $data['fixedPriceByWeightPolicy'][0] = array(
            'from' => 10,
            'to' => 20,
            'amount' => 100,
        );
        $data['fixedPriceByWeightPolicy'][1] = array(
            'from' => 0,
            'to' => 5,
            'amount' => 120,
        );

        ShippingMethod::fromArray($data);
    }

    public function testCheapestService()
    {
        $method = $this->assertBasicDataToArray();
        $method->addShippingService(new ShippingService(213, '', 'IT', 'DE', 5, 4, 1));
        $packages = array(Package::defaultPackage());

        self::assertEquals(
            4,
            ShippingCostCalculator::getCheapestShippingService($method, 'IT', '123', 'DE', '234', $packages)->basePrice
        );
        $method->addShippingService(new ShippingService(213, '', 'IT', 'DE', 5, 3, 1));
        self::assertEquals(
            3,
            ShippingCostCalculator::getCheapestShippingService($method, 'IT', '123', 'DE', '234', $packages)->basePrice
        );
        $method->addShippingService(new ShippingService(213, '', 'IT', 'DE', 5, 5, 0));
        self::assertEquals(
            3,
            ShippingCostCalculator::getCheapestShippingService($method, 'IT', '123', 'DE', '234', $packages)->basePrice
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCheapestServiceWrongDestination()
    {
        $method = $this->assertBasicDataToArray();
        $packages = array(Package::defaultPackage());

        self::assertEquals(
            4,
            ShippingCostCalculator::getCheapestShippingService($method, 'IT', '123', 'DE', '234', $packages)->basePrice
        );
    }

    public function testCheapestServiceProxyResponse()
    {
        $method = $this->assertBasicDataToArray();
        $packages = array(Package::defaultPackage());

        $method->addShippingService(new ShippingService(213, '', 'IT', 'DE', 5, 1, 1));
        $method->addShippingService(new ShippingService(214, '', 'IT', 'DE', 5, 1.5, 1));
        $method->addShippingService(new ShippingService(20615, '', 'IT', 'DE', 5, 8.37, 1));
        $method->addShippingService(new ShippingService(20616, '', 'IT', 'DE', 5, 2, 1));

        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/ShippingServices/costTest.json');

        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $this->assertEquals(
            20616,
            ShippingCostCalculator::getCheapestShippingService($method, 'IT', '123', 'DE', '234', $packages)->serviceId
        );
    }

    private function assertBasicDataToArray()
    {
        $data = $this->getShippingMethodData();

        $method = new ShippingMethod();
        $method->setCarrierName($data['carrierName']);
        $method->setTitle($data['title']);
        $method->setEnabled($data['enabled']);
        $method->setActivated($data['activated']);
        $method->setLogoUrl($data['logoUrl']);
        $method->setDisplayLogo($data['displayLogo']);
        $method->setDepartureDropOff($data['departureDropOff']);
        $method->setDestinationDropOff($data['destinationDropOff']);
        $method->setExpressDelivery($data['expressDelivery']);
        $method->setDeliveryTime($data['deliveryTime']);
        $method->setNational($data['national']);
        $method->addShippingService(ShippingService::fromArray($data['shippingServices'][0]));

        $result = $method->toArray();
        self::assertEquals($data['carrierName'], $result['carrierName']);
        self::assertEquals($data['title'], $result['title']);
        self::assertEquals($data['enabled'], $result['enabled']);
        self::assertEquals($data['activated'], $result['activated']);
        self::assertEquals($data['logoUrl'], $result['logoUrl']);
        self::assertEquals($data['displayLogo'], $result['displayLogo']);
        self::assertEquals($data['departureDropOff'], $result['departureDropOff']);
        self::assertEquals($data['destinationDropOff'], $result['destinationDropOff']);
        self::assertEquals($data['expressDelivery'], $result['expressDelivery']);
        self::assertEquals($data['deliveryTime'], $result['deliveryTime']);
        self::assertEquals($data['national'], $result['national']);
        self::assertEquals(ShippingMethod::PRICING_POLICY_PACKLINK, $result['pricingPolicy']);
        self::assertEquals($data['shippingServices'], $result['shippingServices']);

        return $method;
    }

    /**
     * @return array
     */
    private function getShippingMethodData()
    {
        return array(
            'carrierName' => 'carrier name',
            'title' => 'title',
            'enabled' => false,
            'activated' => true,
            'logoUrl' => 'https://packlink.com',
            'displayLogo' => false,
            'departureDropOff' => true,
            'destinationDropOff' => true,
            'expressDelivery' => true,
            'deliveryTime' => '2 DAYS',
            'national' => true,
            'shippingServices' => array(
                array(
                    'serviceId' => 1234,
                    'serviceName' => 'service name',
                    'departure' => 'IT',
                    'destination' => 'IT',
                    'totalPrice' => 3,
                    'basePrice' => 2,
                    'taxPrice' => 1,
                ),
            ),
        );
    }
}
