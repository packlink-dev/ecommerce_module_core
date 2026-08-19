<?php

namespace Logeecom\Tests\BusinessLogic\DDP;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Customs\CustomsService;
use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\DDP\DdpBehavior;
use Packlink\BusinessLogic\DDP\DdpCostService;
use Packlink\BusinessLogic\DDP\Models\DdpCostResponse;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;

/**
 * Class DdpCostServiceTest.
 *
 * @package Logeecom\Tests\BusinessLogic\DDP
 */
class DdpCostServiceTest extends BaseTestWithServices
{
    /**
     * @var DdpCostService
     */
    private $ddpCostService;

    /**
     * @before
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     */
    public function before()
    {
        parent::before();

        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        TestServiceRegister::registerService(
            CustomsService::CLASS_NAME,
            function () {
                return new CustomsService();
            }
        );

        TestServiceRegister::registerService(
            PackageTransformer::CLASS_NAME,
            function () {
                return PackageTransformer::getInstance();
            }
        );

        TestFrontDtoFactory::register(CustomsMapping::CLASS_KEY, CustomsMapping::CLASS_NAME);

        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
        $this->shopConfig->setDefaultParcel(
            ParcelInfo::fromArray(array('weight' => 1.0, 'width' => 10, 'height' => 10, 'length' => 10))
        );
        $user = new User();
        $user->customerType = 'BUSINESS';
        $this->shopConfig->setUserInfo($user);

        $this->ddpCostService = new DdpCostService();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testGetDdpCostsSendsInvoiceThenProductsAndParsesComponents()
    {
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());
        $this->saveShippingMethod('20154', DdpBehavior::LEVEL_SUPPORTED, DdpBehavior::OPTIONAL, 'percentage', -10.0);
        $this->httpClient->setMockResponses($this->getSuccessfulResponses());

        $cost = $this->ddpCostService->getDdpCosts($this->getOrder(), '20154');

        $history = $this->httpClient->getHistory();
        self::assertCount(2, $history);
        self::assertSame('POST', $history[0]['method']);
        self::assertSame('https://api.packlink.com/v2/customs-invoices', $history[0]['url']);
        self::assertSame('POST', $history[1]['method']);
        self::assertSame('https://api.packlink.com/pro/shipments/products', $history[1]['url']);

        $productsBody = json_decode($history[1]['body'], true);
        // Exactly one entry, always: batched responses mis-attribute results (see ShipmentProductsRequest).
        self::assertCount(1, $productsBody['shipments']);
        $shipment = $productsBody['shipments'][0];
        self::assertSame('20154', $shipment['service_id']);
        self::assertSame(120.45, $shipment['contentvalue']);
        self::assertSame(
            '70b7ac2a-7a71-11eb-9439-0242ac130002',
            $shipment['customs']['customs_invoice_id']
        );
        // Route and parcel data are required by the live endpoint and come from the order + warehouse.
        self::assertSame('PRO', $shipment['source']);
        self::assertNotEmpty($shipment['from']['country']);
        self::assertNotEmpty($shipment['to']['country']);
        self::assertCount(1, $shipment['packages']);
        self::assertTrue($shipment['selected_products']['ddp']['is_selected']);
        self::assertArrayNotHasKey('contentValue_currency', $shipment);

        self::assertInstanceOf(DdpCostResponse::class, $cost);
        self::assertSame('20154', $cost->serviceId);
        // Full component parsing is covered by ProxyTest::testGetShipmentProductsResponseParsing.
        self::assertSame(8.79, $cost->ddpFee->totalPrice);
        self::assertTrue($cost->ddpFee->isEnabled);
        self::assertTrue($cost->ddpFee->isSelected);
        self::assertSame(35.22, $cost->customsAndDuties->totalPrice);
        self::assertSame(DdpBehavior::OPTIONAL, $cost->effectiveBehavior);
        self::assertSame('percentage', $cost->ddpAdjustmentType);
        self::assertSame(-10.0, $cost->ddpAdjustmentAmount);
    }

    /**
     * When no shipping method owns the requested service id, the cost still returns
     * with effective behavior NONE and no adjustment.
     */
    public function testGetDdpCostsWithoutOwningMethodDefaultsBehaviorToNone()
    {
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());
        $this->httpClient->setMockResponses($this->getSuccessfulResponses());

        $cost = $this->ddpCostService->getDdpCosts($this->getOrder(), '20154');

        self::assertInstanceOf(DdpCostResponse::class, $cost);
        self::assertSame(DdpBehavior::NONE, $cost->effectiveBehavior);
        self::assertNull($cost->ddpAdjustmentType);
        self::assertSame(0.0, $cost->ddpAdjustmentAmount);
    }

    public function testGetDdpCostsWithEmptyServiceIdMakesNoHttpCalls()
    {
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());

        self::assertNull($this->ddpCostService->getDdpCosts($this->getOrder(), ''));
        self::assertEmpty($this->httpClient->getHistory());
    }

    public function testGetDdpCostsWithoutCustomsConfigurationMakesNoHttpCalls()
    {
        self::assertNull($this->ddpCostService->getDdpCosts($this->getOrder(), '20154'));
        self::assertEmpty($this->httpClient->getHistory());
    }

    /**
     * A mapping that exists but cannot resolve an HS code produces no invoice, so the two calls it
     * would take to discover that are not worth making - and the log must name the configuration
     * rather than the generic assembly failure.
     */
    public function testGetDdpCostsWithIncompleteCustomsConfigurationMakesNoHttpCalls()
    {
        $mapping = $this->getCustomsMapping();
        $mapping->defaultTariffNumber = '';
        $mapping->mappingTariffNumber = '';
        $this->shopConfig->setCustomsMappings($mapping);

        self::assertNull($this->ddpCostService->getDdpCosts($this->getOrder(), '20154'));
        self::assertEmpty($this->httpClient->getHistory());
        self::assertNotEmpty($this->shopLogger->loggedMessages);
        self::assertContains(
            'customs configuration is incomplete',
            $this->shopLogger->loggedMessages[0]->getMessage()
        );
    }

    /**
     * Checkout must never break on DDP failures: an HTTP error on the invoice call is
     * logged and yields no cost.
     */
    public function testGetDdpCostsReturnsNullOnInvoiceCallFailure()
    {
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());
        $this->httpClient->setMockResponses(array());

        self::assertNull($this->ddpCostService->getDdpCosts($this->getOrder(), '20154'));
        self::assertNotEmpty($this->shopLogger->loggedMessages);
    }

    /**
     * An HTTP error on the products call is logged and yields no cost.
     */
    public function testGetDdpCostsReturnsNullOnProductsCallFailure()
    {
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());
        $invoiceBody = file_get_contents(__DIR__ . '/../Common/ApiResponses/Customs/createCustomsResult.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $invoiceBody)));

        self::assertNull($this->ddpCostService->getDdpCosts($this->getOrder(), '20154'));
        self::assertCount(2, $this->httpClient->getHistory());
        self::assertNotEmpty($this->shopLogger->loggedMessages);
    }

    public function testGetDdpCostsReturnsNullWhenInvoiceIdIsMissing()
    {
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '{}')));

        self::assertNull($this->ddpCostService->getDdpCosts($this->getOrder(), '20154'));
        self::assertCount(1, $this->httpClient->getHistory());
    }

    /**
     * A service with no DDP on the route omits ddp_fee entirely. That is an ordinary answer,
     * not a failure: no cost, and nothing alarming in the log.
     */
    public function testGetDdpCostsReturnsNullWhenResponseHasNoDdpProducts()
    {
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());
        $invoiceBody = file_get_contents(__DIR__ . '/../Common/ApiResponses/Customs/createCustomsResult.json');
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(200, array(), $invoiceBody),
                new HttpResponse(200, array(), '{"products_details":[{"products":{"porterage":{}}}],"summary":{}}'),
            )
        );

        self::assertNull($this->ddpCostService->getDdpCosts($this->getOrder(), '20154'));
        self::assertCount(2, $this->httpClient->getHistory());
    }

    /**
     * A rejected HS code is a merchant-fixable configuration fault, not a transient outage, and the
     * log must say so -- Packlink validates tariff numbers against a current HS revision, so a
     * well-formed but withdrawn code passes the 6-8 digit check and fails only here.
     */
    public function testGetDdpCostsLogsActionableHintForRejectedHsCode()
    {
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());
        $invoiceBody = file_get_contents(__DIR__ . '/../Common/ApiResponses/Customs/createCustomsResult.json');
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(200, array(), $invoiceBody),
                new HttpResponse(
                    400,
                    array(),
                    '{"messages":[{"message":"Invalid HS Code: \'851712\'","error_code":"INVALID_HS_CODE"}]}'
                ),
            )
        );

        self::assertNull($this->ddpCostService->getDdpCosts($this->getOrder(), '20154'));

        $logged = '';
        foreach ($this->shopLogger->loggedMessages as $message) {
            $logged .= $message->getMessage() . "\n";
        }

        // strpos rather than assertStringContainsString/assertContains: the former does not exist in
        // PHPUnit 4.8, the latter rejects string haystacks from PHPUnit 9 on, and the suite runs both.
        self::assertNotFalse(strpos($logged, 'Invalid HS Code'), 'Log must quote the API message: ' . $logged);
        self::assertNotFalse(strpos($logged, 'HS code'), 'Log must name the HS code as the fault: ' . $logged);
        self::assertNotFalse(strpos($logged, 'retrieving DDP products'), 'Log must name the step: ' . $logged);
        self::assertFalse(strpos($logged, 'transient'), 'A rejected HS code must not read as transient: ' . $logged);
    }

    /**
     * Malformed response shapes (scalar entries where objects are expected) must degrade to
     * "no DDP cost", never to an uncaught TypeError on the checkout path.
     */
    public function testGetDdpCostsReturnsNullOnMalformedProductsResponse()
    {
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());
        $invoiceBody = file_get_contents(__DIR__ . '/../Common/ApiResponses/Customs/createCustomsResult.json');
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(200, array(), $invoiceBody),
                new HttpResponse(200, array(), '{"products_details":["garbage"],"summary":{}}'),
            )
        );

        self::assertNull($this->ddpCostService->getDdpCosts($this->getOrder(), '20154'));

        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(200, array(), $invoiceBody),
                new HttpResponse(200, array(), '{"products_details":[{"products":{"ddp_fee":"12"}}],"summary":{}}'),
            )
        );

        self::assertNull($this->ddpCostService->getDdpCosts($this->getOrder(), '20154'));
    }

    /**
     * A configured customs mapping with no default warehouse cannot assemble an invoice; the
     * cost lookup must yield null without any HTTP call and without a fatal.
     */
    public function testGetDdpCostsReturnsNullWhenWarehouseIsMissing()
    {
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());
        $this->shopConfig->removeDefaultWarehouse();

        self::assertNull($this->ddpCostService->getDdpCosts($this->getOrder(), '20154'));
        self::assertEmpty($this->httpClient->getHistory());
    }

    /**
     * @return HttpResponse[]
     */
    private function getSuccessfulResponses()
    {
        return array(
            new HttpResponse(
                200,
                array(),
                file_get_contents(__DIR__ . '/../Common/ApiResponses/Customs/createCustomsResult.json')
            ),
            new HttpResponse(
                200,
                array(),
                file_get_contents(__DIR__ . '/../Common/ApiResponses/DDP/productsResponse.json')
            ),
        );
    }

    /**
     * @return \Packlink\BusinessLogic\Order\Objects\Order
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    private function getOrder()
    {
        $shopOrderService = new TestShopOrderService();
        $order = $shopOrderService->getOrder('ddp-cost-service-test', 1, 'US');
        $order->setTotalPrice(120.45);
        $order->setCurrency('EUR');

        return $order;
    }

    /**
     * @param string $serviceId
     * @param string|null $supportLevel
     * @param string $behavior
     * @param string|null $adjustmentType
     * @param float $adjustmentAmount
     *
     * @return ShippingMethod
     */
    private function createShippingMethod(
        $serviceId,
        $supportLevel,
        $behavior,
        $adjustmentType = null,
        $adjustmentAmount = 0.0
    ) {
        $method = new ShippingMethod();
        $method->setCarrierName('test carrier');
        $method->addShippingService(
            new ShippingService($serviceId, 'test service', 'IT', 'IT', 10.0, 8.0, 2.0, '', null, $supportLevel)
        );
        $method->setDdpBehavior($behavior);
        $method->setDdpAdjustmentType($adjustmentType);
        $method->setDdpAdjustmentAmount($adjustmentAmount);

        return $method;
    }

    /**
     * @param string $serviceId
     * @param string|null $supportLevel
     * @param string $behavior
     * @param string|null $adjustmentType
     * @param float $adjustmentAmount
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function saveShippingMethod($serviceId, $supportLevel, $behavior, $adjustmentType, $adjustmentAmount)
    {
        $method = $this->createShippingMethod($serviceId, $supportLevel, $behavior, $adjustmentType, $adjustmentAmount);
        RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME)->save($method);
    }

    /**
     * @return CustomsMapping
     */
    private function getCustomsMapping()
    {
        $mapping = new CustomsMapping();
        $mapping->defaultReason = 'PURCHASE_OR_SALE';
        $mapping->defaultSenderTaxId = '123';
        $mapping->defaultReceiverUserType = 'PRIVATE_PERSON';
        $mapping->defaultReceiverTaxId = '123';
        $mapping->defaultTariffNumber = '0123456';
        $mapping->defaultCountry = 'FR';
        $mapping->mappingReceiverTaxId = 'tax_1';

        return $mapping;
    }
}
