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
     * the contract body shape: shipments[].{service_id,contentvalue,contentValue_currency,customs.customs_invoice_id}.
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
        self::assertSame('20154', $requestBody['shipments'][0]['service_id']);
        self::assertSame(120.46, $requestBody['shipments'][0]['contentvalue']);
        self::assertSame('EUR', $requestBody['shipments'][0]['contentValue_currency']);
        self::assertSame('invoice-id-1', $requestBody['shipments'][0]['customs']['customs_invoice_id']);
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

        $details = $this->getProxy()->getShipmentProducts($this->getShipmentProductsRequest());

        self::assertCount(1, $details);
        self::assertInstanceOf(DdpProductsDetail::class, $details[0]);
        self::assertNull($details[0]->serviceId);
        self::assertInstanceOf(DdpProductCost::class, $details[0]->ddpFee);
        self::assertSame(8.79, $details[0]->ddpFee->basePrice);
        self::assertSame(0.0, $details[0]->ddpFee->taxPrice);
        self::assertSame(8.79, $details[0]->ddpFee->totalPrice);
        self::assertSame('EUR', $details[0]->ddpFee->currency);
        self::assertTrue($details[0]->ddpFee->isEnabled);
        self::assertTrue($details[0]->ddpFee->isSelected);
        self::assertInstanceOf(DdpProductCost::class, $details[0]->customsAndDuties);
        self::assertSame(35.22, $details[0]->customsAndDuties->basePrice);
        self::assertSame(35.22, $details[0]->customsAndDuties->totalPrice);
        self::assertTrue($details[0]->customsAndDuties->isEnabled);
        self::assertTrue($details[0]->customsAndDuties->isSelected);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetShipmentProductsEmptyResponse()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '{"summary":{}}')));

        $details = $this->getProxy()->getShipmentProducts($this->getShipmentProductsRequest());

        self::assertSame(array(), $details);
    }

    /**
     * @return void
     */
    public function testDdpProductsDetailParsesServiceIdAndFlags()
    {
        $detail = DdpProductsDetail::fromArray(
            array(
                'service_id' => '20154',
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

        self::assertSame('20154', $detail->serviceId);
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
        $item->contentValueCurrency = 'EUR';
        $item->customsInvoiceId = 'invoice-id-1';

        $request = new ShipmentProductsRequest();
        $request->items = array($item);

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
