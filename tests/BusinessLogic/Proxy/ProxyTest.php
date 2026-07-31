<?php

namespace Logeecom\Tests\BusinessLogic\Proxy;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\Customs\CustomsInvoice;
use Packlink\BusinessLogic\Http\DTO\DDP\DdpProductCost;
use Packlink\BusinessLogic\Http\DTO\DDP\DdpProductsDetail;
use Packlink\BusinessLogic\Http\DTO\DDP\ShipmentProductsRequest;
use Packlink\BusinessLogic\Http\DTO\DDP\ShipmentProductsRequestItem;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class ProxyTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Proxy
 */
class ProxyTest extends BaseTestWithServices
{
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

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());
    }

    /**
     * Tests successful response.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testSuccessfulResponse()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/shipmentLabels.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $labels = $this->getProxy()->getLabels('asdf');

        self::assertCount(1, $labels);
    }

    /**
     * Tests the case when API returns a list of messages.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     */
    public function testBadResponseListOfMessages()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/badResponseMessages.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), $response)));

        $exThrown = null;
        try {
            $this->getProxy()->getLabels('asdf');
        } catch (\Logeecom\Infrastructure\Http\Exceptions\HttpRequestException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
        $this->assertEquals(400, $exThrown->getCode());
        $expected = "Error message 1\nError message 2";
        $actual = str_replace(["\r\n", "\r"], "\n", $exThrown->getMessage());

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests the case when API returns a list of messages.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     */
    public function testBadResponseMessage()
    {
        $response = '{"message": "Error message 1"}';
        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), $response)));

        $exThrown = null;
        try {
            $this->getProxy()->getLabels('asdf');
        } catch (\Logeecom\Infrastructure\Http\Exceptions\HttpRequestException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
        $this->assertEquals(400, $exThrown->getCode());
        $this->assertEquals('Error message 1',  $exThrown->getMessage());
    }

    /**
     * Tests the case when API returns an authentication error.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function test401()
    {
        $response = '{"message": "Auth error"}';
        $this->httpClient->setMockResponses(array(new HttpResponse(401, array(), $response)));

        $exThrown = null;
        try {
            $this->getProxy()->getLabels('asdf');
        } catch (\Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
        $this->assertEquals(401, $exThrown->getCode());
        $this->assertEquals('Auth error',  $exThrown->getMessage());    }

    /**
     * Tests the case when API returns a 404 error.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function test404()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(404, array(), '')));

        self::assertEmpty($this->getProxy()->getLabels('asdf'));
    }

    /**
     * Asserts the checkout customs invoice request hits the un-versioned /v2/ endpoint
     * (https://api.packlink.com/v2/... not https://api.packlink.com/v1/v2/...).
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testCreateCheckoutCustomsInvoice()
    {
        $body = file_get_contents(__DIR__ . '/../Common/ApiResponses/Customs/createCustomsResult.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $body)));
        $data = json_decode($body, true);
        $invoice = CustomsInvoice::fromArray($data['data']);

        $invoiceId = $this->getProxy()->createCheckoutCustomsInvoice($invoice);

        self::assertSame('70b7ac2a-7a71-11eb-9439-0242ac130002', $invoiceId);
        $lastRequest = $this->httpClient->getLastRequest();
        self::assertSame('POST', $lastRequest['method']);
        self::assertSame('https://api.packlink.com/v2/customs-invoices', $lastRequest['url']);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testCreateCheckoutCustomsInvoiceNoId()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '{}')));
        $body = file_get_contents(__DIR__ . '/../Common/ApiResponses/Customs/createCustomsResult.json');
        $data = json_decode($body, true);
        $invoice = CustomsInvoice::fromArray($data['data']);

        self::assertNull($this->getProxy()->createCheckoutCustomsInvoice($invoice));
    }

    /**
     * Asserts the DDP products request hits the un-versioned /pro/ endpoint and carries
     * the full body shape the live API requires. Verified against the real endpoint 2026-07-30.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetShipmentProductsRequestShape()
    {
        $body = file_get_contents(__DIR__ . '/../Common/ApiResponses/DDP/productsResponse.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $body)));

        $this->getProxy()->getShipmentProducts($this->getShipmentProductsRequest());

        $lastRequest = $this->httpClient->getLastRequest();
        self::assertSame('POST', $lastRequest['method']);
        self::assertSame('https://api.packlink.com/pro/shipments/products', $lastRequest['url']);
        $requestBody = json_decode($lastRequest['body'], true);
        self::assertCount(1, $requestBody['shipments']);
        $shipment = $requestBody['shipments'][0];
        self::assertSame('20154', $shipment['service_id']);
        self::assertSame(120.46, $shipment['contentvalue']);
        self::assertSame('invoice-id-1', $shipment['customs']['customs_invoice_id']);
        // The live endpoint rejects a partial body; every field below is required for HTTP 200.
        self::assertSame('PRO', $shipment['source']);
        self::assertSame(array('city' => 'Madrid', 'country' => 'ES', 'zip_code' => '28001'), $shipment['from']);
        self::assertSame(array('city' => 'London', 'country' => 'GB', 'zip_code' => 'W1S 2YS'), $shipment['to']);
        self::assertCount(1, $shipment['packages']);
        self::assertEquals(2.0, $shipment['packages'][0]['weight']);
        self::assertFalse($shipment['insurance']['insurance_selected']);
        self::assertFalse($shipment['content_second_hand']);
        self::assertFalse($shipment['proof_of_delivery']);
        self::assertFalse($shipment['adult_signature']);
        self::assertFalse($shipment['additional_handling']);
        self::assertTrue($shipment['selected_products']['ddp']['is_selected']);
        // Not a field of this endpoint - sending it was part of what made every request 500.
        self::assertArrayNotHasKey('contentValue_currency', $shipment);
        // No shipment exists at checkout; the endpoint prices fine without a reference.
        self::assertArrayNotHasKey('packlink_reference', $shipment);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetShipmentProductsResponseParsing()
    {
        $body = file_get_contents(__DIR__ . '/../Common/ApiResponses/DDP/productsResponse.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $body)));

        $detail = $this->getProxy()->getShipmentProducts($this->getShipmentProductsRequest());

        self::assertInstanceOf(DdpProductsDetail::class, $detail);
        self::assertInstanceOf(DdpProductCost::class, $detail->ddpFee);
        self::assertSame(8.79, $detail->ddpFee->basePrice);
        self::assertSame(0.0, $detail->ddpFee->taxPrice);
        self::assertSame(8.79, $detail->ddpFee->totalPrice);
        self::assertSame('EUR', $detail->ddpFee->currency);
        self::assertTrue($detail->ddpFee->isEnabled);
        self::assertTrue($detail->ddpFee->isSelected);
        self::assertInstanceOf(DdpProductCost::class, $detail->customsAndDuties);
        self::assertSame(35.22, $detail->customsAndDuties->basePrice);
        self::assertSame(35.22, $detail->customsAndDuties->totalPrice);
        self::assertTrue($detail->customsAndDuties->isEnabled);
        self::assertTrue($detail->customsAndDuties->isSelected);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetShipmentProductsEmptyResponse()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '{"summary":{}}')));

        self::assertNull($this->getProxy()->getShipmentProducts($this->getShipmentProductsRequest()));
    }

    /**
     * @return void
     */
    public function testDdpProductsDetailParsesFlagsAndIgnoresUnmatchableKeys()
    {
        // The live response carries packlink_reference and never service_id. Nothing is correlated
        // from it: the request is never batched, so the one entry belongs to the one request.
        $detail = DdpProductsDetail::fromArray(
            array(
                'packlink_reference' => 'FR2026PRO0002354009',
                'products' => array(
                    'ddp_fee' => array(
                        'base_price' => 8.79,
                        'tax_price' => 0.0,
                        'total_price' => 8.79,
                        'currency' => 'EUR',
                        'is_enabled' => false,
                        'is_selected' => false,
                    ),
                ),
            )
        );

        self::assertFalse($detail->ddpFee->isEnabled);
        self::assertFalse($detail->ddpFee->isSelected);
        self::assertNull($detail->customsAndDuties);
    }

    /**
     * @return ShipmentProductsRequest
     */
    private function getShipmentProductsRequest()
    {
        $item = new ShipmentProductsRequestItem();
        $item->serviceId = '20154';
        $item->contentValue = 120.456;
        $item->customsInvoiceId = 'invoice-id-1';
        $item->fromCountry = 'ES';
        $item->fromZip = '28001';
        $item->fromCity = 'Madrid';
        $item->toCountry = 'GB';
        $item->toZip = 'W1S 2YS';
        $item->toCity = 'London';
        $item->packages = array(new Package(2.0, 30, 20, 25));

        $request = new ShipmentProductsRequest();
        $request->item = $item;

        return $request;
    }

    /**
     * @return Proxy
     */
    private function getProxy()
    {
        /** @var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        return $proxy;
    }
}
