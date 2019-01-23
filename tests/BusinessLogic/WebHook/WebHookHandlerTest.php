<?php

namespace Logeecom\Tests\BusinessLogic\WebHook;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Tests\Common\BaseTestWithServices;
use Logeecom\Tests\Common\TestComponents\Order\TestOrderRepository;
use Logeecom\Tests\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Common\TestServiceRegister;
use Packlink\BusinessLogic\BootstrapComponent;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\WebHook\Events\ShipmentLabelEvent;
use Packlink\BusinessLogic\WebHook\Events\ShippingStatusEvent;
use Packlink\BusinessLogic\WebHook\Events\TrackingInfoEvent;
use Packlink\BusinessLogic\WebHook\WebHookEventHandler;

/**
 * Class WebHookHandlerTest
 * @package Logeecom\Tests\BusinessLogic\WebHook
 */
class WebHookHandlerTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        BootstrapComponent::init();
        $me = $this;

        $this->httpClient = new TestHttpClient();
        $orderRepository = new TestOrderRepository();

        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

        TestServiceRegister::registerService(
            OrderRepository::CLASS_NAME,
            function () use ($orderRepository) {
                return $orderRepository;
            }
        );

        TestServiceRegister::registerService(Proxy::CLASS_NAME, function () use ($me) {
            /** @var Configuration $config */
            $config = TestServiceRegister::getService(Configuration::CLASS_NAME);

            return new Proxy($config->getAuthorizationToken(), $me->httpClient);
        });
    }

    protected function tearDown()
    {
        EventBus::resetInstance();
        WebHookEventHandler::resetInstance();
        parent::tearDown();
    }

    /**
     * Tests setting of shipment labels
     */
    public function testHandleShipmentLabelEvent()
    {
        $this->httpClient->setMockResponses($this->getMockLabelResponse());
        /** @var EventBus $bus */
        $bus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $bus->fire(new ShipmentLabelEvent('test'));

        /** @var TestOrderRepository $orderRepository */
        $orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
        $order = $orderRepository->getOrder('test');

        $this->assertNotNull($order);
        $this->assertNotEmpty($order->getPacklinkShipmentLabels());
        $this->assertCount(1, $order->getPacklinkShipmentLabels());
    }

    /**
     * Tests when API fails
     */
    public function testHandleShipmentLabelEventHttpError()
    {
        $this->httpClient->setMockResponses($this->getErrorMockResponse());
        /** @var EventBus $bus */
        $bus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $bus->fire(new ShipmentLabelEvent('test'));

        $this->assertNotEmpty($this->shopLogger->loggedMessages);
        /** @var \Logeecom\Infrastructure\Logger\LogData $logData */
        $logData = end($this->shopLogger->loggedMessages);
        $this->assertNotEmpty($logData);
        $logContextData = $logData->getContext();
        $this->assertNotEmpty($logContextData);
        $this->assertEquals('referenceId', $logContextData[0]->getName());
        $this->assertEquals('test', $logContextData[0]->getValue());
    }

    /**
     * Tests when order fetch fails
     */
    public function testHandleShipmentLabelEventNoOrder()
    {
        /** @var TestOrderRepository $orderRepository */
        $orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
        $orderRepository->shouldThrowException(true);

        $this->httpClient->setMockResponses($this->getMockLabelResponse());
        /** @var EventBus $bus */
        $bus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $bus->fire(new ShipmentLabelEvent('test'));

        $this->assertNotEmpty($this->shopLogger->loggedMessages);
        $this->assertEquals('Order not found.', $this->shopLogger->loggedMessages[0]->getMessage());
    }

    /**
     * Tests setting of shipping status
     */
    public function testHandleShippingStatusEvent()
    {
        $this->httpClient->setMockResponses($this->getMockStatusResponse());
        /** @var EventBus $bus */
        $bus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $bus->fire(new ShippingStatusEvent('test'));

        /** @var TestOrderRepository $orderRepository */
        $orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
        $order = $orderRepository->getOrder('test');

        $this->assertNotNull($order);
        $this->assertEquals('CARRIER_PENDING', $order->getShipment()->getStatus());
    }

    /**
     * Tests when API fails
     */
    public function testHandleShippingStatusEventHttpError()
    {
        $this->httpClient->setMockResponses($this->getErrorMockResponse());
        /** @var EventBus $bus */
        $bus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $bus->fire(new ShippingStatusEvent('test'));

        $this->assertNotEmpty($this->shopLogger->loggedMessages);
        /** @var \Logeecom\Infrastructure\Logger\LogData $logData */
        $logData = end($this->shopLogger->loggedMessages);
        $this->assertNotEmpty($logData);
        $logContextData = $logData->getContext();
        $this->assertNotEmpty($logContextData);
        $this->assertEquals('referenceId', $logContextData[0]->getName());
        $this->assertEquals('test', $logContextData[0]->getValue());
    }

    /**
     * Tests when order fetch fails
     */
    public function testHandleShippingStatusEventNoOrder()
    {
        /** @var TestOrderRepository $orderRepository */
        $orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
        $orderRepository->shouldThrowException(true);

        $this->httpClient->setMockResponses($this->getMockStatusResponse());
        /** @var EventBus $bus */
        $bus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $bus->fire(new ShippingStatusEvent('test'));

        $this->assertNotEmpty($this->shopLogger->loggedMessages);
        $this->assertEquals('Order not found.', $this->shopLogger->loggedMessages[0]->getMessage());
    }

    /**
     * Tests setting of shipping tracking
     */
    public function testHandleShippingTrackingEvent()
    {
        $this->httpClient->setMockResponses($this->getMockTrackingResponse());
        /** @var EventBus $bus */
        $bus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $bus->fire(new TrackingInfoEvent('test'));

        /** @var TestOrderRepository $orderRepository */
        $orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
        $order = $orderRepository->getOrder('test');

        $this->assertNotNull($order);
        $trackingHistories = $order->getShipment()->getTrackingHistory();
        $this->assertCount(3, $trackingHistories);
        $this->assertEquals(14242322, $trackingHistories[0]->getTimestamp());
        $this->assertEquals('DELIVERED', $trackingHistories[0]->getDescription());
        $this->assertEquals('MIAMI', $trackingHistories[0]->getCity());
    }

    /**
     * Tests when API fails
     */
    public function testHandleShippingTrackingEventHttpError()
    {
        $this->httpClient->setMockResponses($this->getErrorMockResponse());
        /** @var EventBus $bus */
        $bus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $bus->fire(new TrackingInfoEvent('test'));

        $this->assertNotEmpty($this->shopLogger->loggedMessages);
        /** @var \Logeecom\Infrastructure\Logger\LogData $logData */
        $logData = end($this->shopLogger->loggedMessages);
        $this->assertNotEmpty($logData);
        $logContextData = $logData->getContext();
        $this->assertNotEmpty($logContextData);
        $this->assertEquals('referenceId', $logContextData[0]->getName());
        $this->assertEquals('test', $logContextData[0]->getValue());
    }

    /**
     * Tests when order fetch fails
     */
    public function testHandleShippingTrackingEventNoOrder()
    {
        /** @var TestOrderRepository $orderRepository */
        $orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
        $orderRepository->shouldThrowException(true);

        $this->httpClient->setMockResponses($this->getMockTrackingResponse());
        /** @var EventBus $bus */
        $bus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $bus->fire(new TrackingInfoEvent('test'));

        $this->assertNotEmpty($this->shopLogger->loggedMessages);
        $this->assertEquals('Order not found.', $this->shopLogger->loggedMessages[0]->getMessage());
    }

    /**
     * Tests when reference has no tracking info associated with it.
     */
    public function testHandleShippingTrackingEventWhen404IsThrown()
    {
        /** @var TestOrderRepository $orderRepository */
        $orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);

        $this->httpClient->setMockResponses($this->get404ErrorResponse());
        /** @var EventBus $bus */
        $bus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $bus->fire(new TrackingInfoEvent('test'));

        $this->assertEmpty($orderRepository->getOrder('test')->getShipment()->getTrackingHistory());
    }

    /**
     * Returns responses for testing parcel and warehouse initialization.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getMockLabelResponse()
    {
        return array(
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../../Common/ApiResponses/shipmentLabels.json')
            )
        );
    }

    /**
     * Returns responses for testing parcel and warehouse initialization.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getMockStatusResponse()
    {
        return array(
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../../Common/ApiResponses/shipment.json')
            )
        );
    }

    /**
     * Returns responses for testing parcel and warehouse initialization.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getMockTrackingResponse()
    {
        return array(
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../../Common/ApiResponses/tracking.json')
            )
        );
    }

    /**
     * Returns responses error response
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getErrorMockResponse()
    {
        return array(
            new HttpResponse(400, array(), null)
        );
    }

    /**
     * Returns response with 404 status code.
     *
     * @return HttpResponse[]
     */
    private function get404ErrorResponse()
    {
        return array(
            new HttpResponse(404, array(), null)
        );
    }
}
