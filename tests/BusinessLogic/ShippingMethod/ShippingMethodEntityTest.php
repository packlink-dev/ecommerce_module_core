<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace Logeecom\Tests\BusinessLogic\ShippingMethod;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
use Packlink\BusinessLogic\ShippingMethod\ShippingCostCalculator;
use Packlink\BusinessLogic\Warehouse\Warehouse;

/**
 * Class ShippingMethodEntityTest.
 *
 * @package Logeecom\Tests\BusinessLogic\ShippingMethod
 */
class ShippingMethodEntityTest extends BaseTestWithServices
{
    /**
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient
     */
    public $httpClient;

    /**
     * @before
     * @inheritDoc
     */
    protected function before()
    {
        parent::before();

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

        TestFrontDtoFactory::register(ValidationError::CLASS_KEY, ValidationError::CLASS_NAME);
        TestFrontDtoFactory::register(Warehouse::CLASS_KEY, Warehouse::CLASS_NAME);
        TestFrontDtoFactory::register(ParcelInfo::CLASS_KEY, ParcelInfo::CLASS_NAME);
        TestFrontDtoFactory::register(ShippingPricePolicy::CLASS_KEY, ShippingPricePolicy::CLASS_NAME);
    }

    /**
     * @after
     * @inheritDoc
     */
    protected function after()
    {
        parent::after();

        TestFrontDtoFactory::reset();
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

        $method->setCurrency('GBP');
        self::assertEquals('GBP', $method->getCurrency());
    }

    public function testFromArrayShippingService()
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
     * @return void
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function testCheapestServiceWrongDestination()
    {
        $method = $this->assertBasicDataToArray();
        $packages = array(Package::defaultPackage());

        try {
            $basePrice = ShippingCostCalculator::getCheapestShippingService($method, 'IT', '123', 'DE', '234', $packages)->basePrice;
        } catch (\InvalidArgumentException $ex) {
            $exThrown = $ex;
            $this->assertNotNull($exThrown);
            return;
        }

        self::assertEquals(4, $basePrice);
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

    public function testSystemSpecificPricingPolicy()
    {
        $method = $this->assertBasicDataToArray();

        $policies = $method->getPricingPolicies();
        $policy = $policies[0];
        $this->assertEquals('test', $policy->systemId);
    }

    /**
     * Asserts basic shipping method data.
     *
     * @return \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
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
        $method->addPricingPolicy(ShippingPricePolicy::fromArray($data['pricingPolicies'][0]));
        $method->setCurrency($data['currency']);

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
        self::assertEquals($data['shippingServices'], $result['shippingServices']);
        self::assertEquals($data['pricingPolicies'], $result['pricingPolicies']);
        self::assertEquals($data['currency'], $result['currency']);

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
            'currency' => 'USD',
            'shippingServices' => array(
                array(
                    'serviceId' => 1234,
                    'serviceName' => 'service name',
                    'departure' => 'IT',
                    'destination' => 'IT',
                    'totalPrice' => 3,
                    'basePrice' => 2,
                    'taxPrice' => 1,
                    'category' => 'standard',
                    'cash_on_delivery' => array(
                        "apply_percentage_cash_on_delivery" => "2.75",
                        "offered" => true,
                        "max_cash_on_delivery" => "2.35",
                        "min_cash_on_delivery" => "0.00",
                    ),
                ),
            ),
            'pricingPolicies' => array(
                array(
                    'range_type' => ShippingPricePolicy::RANGE_PRICE,
                    'from_price' => 0,
                    'to_price' => null,
                    'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
                    'from_weight' => null,
                    'to_weight' => null,
                    'increase' => false,
                    'change_percent' => null,
                    'fixed_price' => null,
                    'system_id' => 'test',
                ),
            ),
        );
    }
}
