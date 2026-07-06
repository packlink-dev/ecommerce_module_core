<?php

namespace Logeecom\Tests\BusinessLogic\Proxy;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class BffProxyTest.
 *
 * Covers the shared tracking page proxy methods: getOrderReference, getPublicTrackingUrl
 * and getEstimatedDeliveryDate, including the BFF session bootstrap.
 *
 * @package Logeecom\Tests\BusinessLogic\Proxy
 */
class BffProxyTest extends BaseTestWithServices
{
    /**
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    public function before()
    {
        parent::before();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());
    }

    /**
     * Tests that getOrderReference returns the order_reference from the v1 shipments response.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetOrderReferenceSuccess()
    {
        $body = file_get_contents(__DIR__ . '/../Common/ApiResponses/shipmentOrderReference.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $body)));

        $orderReference = $this->getProxy()->getOrderReference('DE0123456789');

        self::assertSame('ORD-987654', $orderReference);

        $lastRequest = $this->httpClient->getLastRequest();
        self::assertSame(
            'https://api.packlink.com/v1/shipments/DE0123456789',
            $lastRequest['url']
        );
    }

    /**
     * Tests that getOrderReference returns null when the order_reference key is absent.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetOrderReferenceMissing()
    {
        $this->httpClient->setMockResponses(
            array(new HttpResponse(200, array(), '{"reference":"DE0123456789","state":"READY_TO_PURCHASE"}'))
        );

        $orderReference = $this->getProxy()->getOrderReference('DE0123456789');

        self::assertNull($orderReference);
    }

    /**
     * Tests the happy path for getPublicTrackingUrl: v1 shipments -> BFF init -> BFF postsale.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetPublicTrackingUrlSuccess()
    {
        $shipmentBody = file_get_contents(__DIR__ . '/../Common/ApiResponses/shipmentOrderReference.json');
        $initBody = file_get_contents(__DIR__ . '/../Common/ApiResponses/bffInit.json');
        $postsaleBody = file_get_contents(__DIR__ . '/../Common/ApiResponses/bffPostsale.json');

        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(200, array(), $shipmentBody),
                new HttpResponse(200, array(), $initBody),
                new HttpResponse(200, array(), $postsaleBody),
            )
        );

        $url = $this->getProxy()->getPublicTrackingUrl('DE0123456789', 'es-ES');

        self::assertSame('https://pro.packlink.com/tracking/ABC123XYZ', $url);

        $history = $this->httpClient->getHistory();
        self::assertCount(3, $history);

        // Second call is the BFF init.
        self::assertSame('https://api.packlink.com/bff/init', $history[1]['url']);

        // Third call is the postsale endpoint with order reference, shipment reference and locale.
        $postsaleRequest = $history[2];
        self::assertNotFalse(
            strpos($postsaleRequest['url'], 'bff/postsale/ORD-987654/DE0123456789'),
            'Postsale URL "' . $postsaleRequest['url'] . '" must contain the order and shipment references.'
        );
        self::assertNotFalse(
            strpos($postsaleRequest['url'], 'locale=es-ES'),
            'Postsale URL "' . $postsaleRequest['url'] . '" must contain the locale parameter.'
        );

        // The BFF call must carry the session header obtained from bff/init.
        self::assertArrayHasKey('session', $postsaleRequest['headers']);
        self::assertSame(
            'X-Packlink-Session-Id: sess-abc-123',
            $postsaleRequest['headers']['session']
        );
    }

    /**
     * Tests that getPublicTrackingUrl returns null and does not call the BFF when the
     * order reference is missing.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetPublicTrackingUrlNoOrderReference()
    {
        $this->httpClient->setMockResponses(
            array(new HttpResponse(200, array(), '{"reference":"DE0123456789"}'))
        );

        $url = $this->getProxy()->getPublicTrackingUrl('DE0123456789');

        self::assertNull($url);

        // Only the v1 shipments call should have been made; no BFF init/postsale calls.
        $history = $this->httpClient->getHistory();
        self::assertCount(1, $history);
        self::assertSame('https://api.packlink.com/v1/shipments/DE0123456789', $history[0]['url']);
    }

    /**
     * Tests the happy path for getEstimatedDeliveryDate: BFF init -> BFF tracking.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetEstimatedDeliveryDateSuccess()
    {
        $initBody = file_get_contents(__DIR__ . '/../Common/ApiResponses/bffInit.json');
        $trackingBody = file_get_contents(__DIR__ . '/../Common/ApiResponses/bffTracking.json');

        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(200, array(), $initBody),
                new HttpResponse(200, array(), $trackingBody),
            )
        );

        $date = $this->getProxy()->getEstimatedDeliveryDate('https://pro.packlink.com/tracking/ABC123XYZ');

        self::assertSame('2026-06-15', $date);

        $history = $this->httpClient->getHistory();
        self::assertCount(2, $history);

        // First call is the BFF init.
        self::assertSame('https://api.packlink.com/bff/init', $history[0]['url']);

        // Second call uses the basename of the provided public tracking URL.
        self::assertSame(
            'https://api.packlink.com/bff/tracking/public/ABC123XYZ',
            $history[1]['url']
        );
        self::assertArrayHasKey('session', $history[1]['headers']);
        self::assertSame(
            'X-Packlink-Session-Id: sess-abc-123',
            $history[1]['headers']['session']
        );
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
