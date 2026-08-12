<?php

namespace Logeecom\Tests\BusinessLogic\Customs;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService;
use Packlink\BusinessLogic\Customs\CustomsService;
use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Order\Objects\Address;
use Packlink\BusinessLogic\Order\Objects\Item;
use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\BusinessLogic\Warehouse\Warehouse;

/**
 * Class CustomsServiceTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Customs
 */
class CustomsServiceTest extends BaseTestWithServices
{
    /**
     * @var CustomsService
     */
    private $customsService;

    /**
     * @inheritdoc
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     */
    protected function before()
    {
        parent::before();

        TestFrontDtoFactory::register(CustomsMapping::CLASS_KEY, CustomsMapping::CLASS_NAME);

        $this->customsService = new CustomsService();
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testIsShipmentInternationalTrueWhenNoCommonCustomsUnion()
    {
        // arrange
        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(
                    200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/Customs/emptySearchResult.json')
                ),
            )
        );

        // act
        $result = $this->customsService->isShipmentInternational('US', '10001');

        // assert
        self::assertTrue($result);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testIsShipmentInternationalFalseWhenCommonCustomsUnionExists()
    {
        // arrange
        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(
                    200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/Customs/searchResult.json')
                ),
            )
        );

        // act
        $result = $this->customsService->isShipmentInternational('FR', '75000');

        // assert
        self::assertFalse($result);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testShouldCreateCustomsFalseWhenWarehouseIsIncomplete()
    {
        // arrange: a warehouse missing the city, which CustomsService requires.
        $warehouse = new Warehouse();
        $warehouse->alias = 'default';
        $warehouse->name = 'John';
        $warehouse->surname = 'Doe';
        $warehouse->country = 'ES';
        $warehouse->postalCode = '28001';
        $warehouse->address = 'Test address';
        $warehouse->phone = '123456789';
        $warehouse->email = 'default@default.com';
        $this->shopConfig->setDefaultWarehouse($warehouse);

        // act
        $result = $this->customsService->shouldCreateCustoms('US', '10001');

        // assert
        self::assertFalse($result);
        self::assertEmpty($this->httpClient->getHistory(), 'An incomplete warehouse must short-circuit before any API call.');
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testShouldCreateCustomsTrueWhenWarehouseCompleteAndInternational()
    {
        // arrange
        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(
                    200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/Customs/emptySearchResult.json')
                ),
            )
        );

        // act
        $result = $this->customsService->shouldCreateCustoms('US', '10001');

        // assert
        self::assertTrue($result);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testShouldCreateCustomsFalseWhenWarehouseCompleteButNotInternational()
    {
        // arrange
        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(
                    200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/Customs/searchResult.json')
                ),
            )
        );

        // act
        $result = $this->customsService->shouldCreateCustoms('FR', '75000');

        // assert
        self::assertFalse($result);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testSendCustomsInvoiceBuildsPayloadAndReturnsId()
    {
        // arrange
        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());

        $mapping = new CustomsMapping();
        $mapping->defaultReason = 'PURCHASE_OR_SALE';
        $mapping->defaultSenderTaxId = '123';
        $mapping->defaultReceiverUserType = 'PRIVATE_PERSON';
        $mapping->defaultReceiverTaxId = '456';
        $mapping->defaultTariffNumber = '0123456';
        $mapping->defaultCountry = 'FR';
        $mapping->mappingReceiverTaxId = 'tax_1';
        $this->shopConfig->setCustomsMappings($mapping);

        $user = new User();
        $user->customerType = 'OTHERS';
        $this->shopConfig->setUserInfo($user);

        $order = new Order();
        $order->setOrderNumber('ORDER-1');
        $order->setCurrency('EUR');
        $order->setTotalWeight(2.5);
        $order->setTotalPrice(99.99);
        $order->setTaxId('');
        $order->setVatNumber('');

        $address = new Address();
        $address->setName('Jane');
        $address->setSurname('Roe');
        $address->setCompany('');
        $address->setStreet1('Main St');
        $address->setStreet2('Apt 2');
        $address->setZipCode('75000');
        $address->setCity('Paris');
        $address->setCountry('FR');
        $address->setPhone('+33600000000');
        $order->setShippingAddress($address);

        $itemWithFallback = new Item();
        $itemWithFallback->setTitle('Widget');
        $itemWithFallback->setPrice(49.99);
        $itemWithFallback->setWeight(1.25);
        $itemWithFallback->setQuantity(2);

        $itemWithOwnValues = new Item();
        $itemWithOwnValues->setTitle('Gadget');
        $itemWithOwnValues->setPrice(19.99);
        $itemWithOwnValues->setWeight(0.5);
        $itemWithOwnValues->setQuantity(1);
        $itemWithOwnValues->setTariffNumber('87654321');
        $itemWithOwnValues->setCountryOfOrigin('DE');

        $order->setItems(array($itemWithFallback, $itemWithOwnValues));

        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(
                    200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/Customs/createCustomsResult.json')
                ),
            )
        );

        // act
        $result = $this->customsService->sendCustomsInvoice($order);

        // assert
        self::assertEquals('70b7ac2a-7a71-11eb-9439-0242ac130002', $result);

        $history = $this->httpClient->getHistory();
        self::assertCount(1, $history);
        $payload = json_decode($history[0]['body'], true);

        self::assertEquals('ORDER-1', $payload['invoice_number']);
        self::assertEquals('PURCHASE_OR_SALE', $payload['reason_for_export']);

        self::assertEquals('private_person', $payload['sender']['user_type']);
        self::assertEquals('default test', $payload['sender']['full_name']);
        self::assertEquals('123', $payload['sender']['tax_id']);
        self::assertEquals('', $payload['sender']['company_name']);
        self::assertEquals('test', $payload['sender']['address']);
        self::assertEquals('ES', $payload['sender']['country']);

        self::assertEquals('PRIVATE_PERSON', $payload['receiver']['user_type']);
        self::assertEquals('Jane Roe', $payload['receiver']['full_name']);
        // user_type is matched case-insensitively, so a schema-cased "PRIVATE_PERSON" resolves the
        // tax id: the order has none, so it falls back to the mapping's default_receiver_tax_id ("456").
        self::assertEquals('456', $payload['receiver']['tax_id']);
        self::assertEquals('FR', $payload['receiver']['country']);
        self::assertEquals('+33600000000', $payload['receiver']['phone_number']);

        self::assertCount(2, $payload['inventory_of_contents']);
        self::assertEquals('0123456', $payload['inventory_of_contents'][0]['tariff_number']);
        self::assertEquals('FR', $payload['inventory_of_contents'][0]['country_of_origin']);
        self::assertEquals('87654321', $payload['inventory_of_contents'][1]['tariff_number']);
        self::assertEquals('DE', $payload['inventory_of_contents'][1]['country_of_origin']);

        self::assertEquals(1, $payload['shipment_details']['parcels_size']);
        self::assertEquals(2.5, $payload['shipment_details']['parcels_weight']);
        self::assertEquals('EUR', $payload['shipment_details']['cost']['currency']);
        self::assertEquals(99.99, $payload['shipment_details']['cost']['value']);

        self::assertEquals('default test', $payload['signature']['full_name']);
        self::assertEquals('Madrid', $payload['signature']['city']);
    }

    /**
     * An item whose tariff number resolves to nothing (no item value and no
     * configured default) must not produce a customs invoice with an empty
     * required field: the invoice is skipped with a warning and the draft
     * proceeds without customs.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testSendCustomsInvoiceSkippedWhenTariffNumberUnresolvable()
    {
        // arrange: no default tariff number, item carries none either.
        $order = $this->getOrderForSkipScenario('', 'FR');

        // act
        $result = $this->customsService->sendCustomsInvoice($order);

        // assert
        self::assertNull($result);
        self::assertEmpty($this->httpClient->getHistory(), 'No invoice request may be sent when the tariff number is unresolvable.');
        self::assertTrue(
            $this->shopLogger->isMessageContainedInLog('tariff number'),
            'Skipping the customs invoice must log a warning naming the missing tariff number.'
        );
    }

    /**
     * Same guard for the country of origin: empty resolved value means the
     * invoice is skipped, not sent invalid.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testSendCustomsInvoiceSkippedWhenCountryOfOriginUnresolvable()
    {
        // arrange: no default country of origin, item carries none either.
        $order = $this->getOrderForSkipScenario('123456', '');

        // act
        $result = $this->customsService->sendCustomsInvoice($order);

        // assert
        self::assertNull($result);
        self::assertEmpty($this->httpClient->getHistory(), 'No invoice request may be sent when the country of origin is unresolvable.');
        self::assertTrue(
            $this->shopLogger->isMessageContainedInLog('country of origin'),
            'Skipping the customs invoice must log a warning naming the missing country of origin.'
        );
    }

    /**
     * Builds an order + mapping where an item resolves neither its own customs
     * values nor a configured default for the given fields.
     *
     * @param string $defaultTariffNumber
     * @param string $defaultCountry
     *
     * @return Order
     */
    private function getOrderForSkipScenario($defaultTariffNumber, $defaultCountry)
    {
        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());

        $mapping = new CustomsMapping();
        $mapping->defaultReason = 'PURCHASE_OR_SALE';
        $mapping->defaultSenderTaxId = '123';
        $mapping->defaultReceiverUserType = 'PRIVATE_PERSON';
        $mapping->defaultReceiverTaxId = '456';
        $mapping->defaultTariffNumber = $defaultTariffNumber;
        $mapping->defaultCountry = $defaultCountry;
        $this->shopConfig->setCustomsMappings($mapping);

        $user = new User();
        $user->customerType = 'OTHERS';
        $this->shopConfig->setUserInfo($user);

        $order = new Order();
        $order->setOrderNumber('ORDER-2');
        $order->setCurrency('EUR');
        $order->setTotalWeight(1.0);
        $order->setTotalPrice(10.0);
        $order->setTaxId('');
        $order->setVatNumber('');

        $address = new Address();
        $address->setName('Jane');
        $address->setSurname('Roe');
        $address->setCompany('');
        $address->setStreet1('Main St');
        $address->setStreet2('');
        $address->setZipCode('75000');
        $address->setCity('Paris');
        $address->setCountry('FR');
        $address->setPhone('+33600000000');
        $order->setShippingAddress($address);

        $item = new Item();
        $item->setTitle('Widget');
        $item->setPrice(10.0);
        $item->setWeight(1.0);
        $item->setQuantity(1);

        $order->setItems(array($item));

        return $order;
    }

    /**
     * The direct call exercises the public service surface the checkout DDP flow relies on
     * (building an invoice without sending it).
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testCreateCustomsInvoiceReturnsPopulatedInvoice()
    {
        $this->arrangeInvoiceBuildContext();
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());
        $shopOrderService = new TestShopOrderService();
        $order = $shopOrderService->getOrder('customs-service-test', 1, 'DE');

        $invoice = $this->customsService->createCustomsInvoice($order);

        self::assertInstanceOf('Packlink\\BusinessLogic\\Http\\DTO\\Customs\\CustomsInvoice', $invoice);
        self::assertSame('testOrderNumber', $invoice->invoiceNumber);
        self::assertSame('PURCHASE_OR_SALE', $invoice->reasonForExport);
        self::assertNotNull($invoice->sender);
        self::assertNotNull($invoice->receiver);
        self::assertCount(1, $invoice->inventoriesOfContents);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testCreateCustomsInvoiceWithoutMapping()
    {
        $this->arrangeInvoiceBuildContext();
        $shopOrderService = new TestShopOrderService();
        $order = $shopOrderService->getOrder('customs-service-test-no-mapping', 1, 'DE');

        self::assertNull($this->customsService->createCustomsInvoice($order));
    }

    /**
     * Warehouse and user info the sender block is built from. before() leaves these unset so the
     * internationality tests can drive them per case.
     *
     * @return void
     */
    private function arrangeInvoiceBuildContext()
    {
        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
        $user = new User();
        $user->customerType = 'BUSINESS';
        $this->shopConfig->setUserInfo($user);
    }

    /**
     * A missing default warehouse must yield null instead of a fatal error
     * (getSender()/getSignature() type-hint Warehouse).
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testCreateCustomsInvoiceWithoutWarehouse()
    {
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());
        $this->shopConfig->removeDefaultWarehouse();
        $shopOrderService = new TestShopOrderService();
        $order = $shopOrderService->getOrder('customs-service-test-no-warehouse', 1, 'DE');

        self::assertNull($this->customsService->createCustomsInvoice($order));
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
