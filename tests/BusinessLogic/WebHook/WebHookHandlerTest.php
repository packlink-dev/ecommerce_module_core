<?php

namespace Logeecom\Tests\BusinessLogic\WebHook;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\BootstrapComponent;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\WebHook\WebHookEventHandler;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;

/**
 * Class WebHookHandlerTest
 * @package Logeecom\Tests\BusinessLogic\WebHook
 */
class WebHookHandlerTest extends BaseTestWithServices
{
    /**
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient
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
    }

    protected function tearDown()
    {
        WebHookEventHandler::resetInstance();
        parent::tearDown();
    }

    /**
     * Tests setting of shipment labels
     */
    public function testHandleShipmentLabelEvent()
    {
        $this->httpClient->setMockResponses($this->getMockLabelResponse());
        WebHookEventHandler::getInstance()->handleShipmentLabelEvent('test');

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
        WebHookEventHandler::getInstance()->handleShipmentLabelEvent('test');

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
        WebHookEventHandler::getInstance()->handleShipmentLabelEvent('test');

        $this->assertNotEmpty($this->shopLogger->loggedMessages);
        $this->assertEquals('Order not found.', $this->shopLogger->loggedMessages[0]->getMessage());
    }

    /**
     * Tests setting of shipping status
     */
    public function testHandleShippingStatusEvent()
    {
        $this->httpClient->setMockResponses($this->getMockStatusResponse());
        WebHookEventHandler::getInstance()->handleShippingStatusEvent(
            'test',
            ShipmentStatus::STATUS_DELIVERED
        );

        /** @var TestOrderRepository $orderRepository */
        $orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
        $order = $orderRepository->getOrder('test');

        $this->assertNotNull($order);
        $this->assertEquals(ShipmentStatus::STATUS_DELIVERED, $order->getShipment()->getStatus());
    }

    /**
     * Tests when API fails
     */
    public function testHandleShippingStatusEventHttpError()
    {
        $this->httpClient->setMockResponses($this->getErrorMockResponse());
        WebHookEventHandler::getInstance()->handleShippingStatusEvent(
            'test',
            ShipmentStatus::STATUS_DELIVERED
        );

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
        /** @var \Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository $orderRepository */
        $orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
        $orderRepository->shouldThrowException(true);

        $this->httpClient->setMockResponses($this->getMockStatusResponse());
        WebHookEventHandler::getInstance()->handleShippingStatusEvent(
            'test',
            ShipmentStatus::STATUS_ACCEPTED
        );

        $this->assertNotEmpty($this->shopLogger->loggedMessages);
        $this->assertEquals('Order not found.', $this->shopLogger->loggedMessages[0]->getMessage());
    }

    /**
     * Tests setting of shipping tracking
     */
    public function testHandleShippingTrackingEvent()
    {
        $this->httpClient->setMockResponses(
            array_merge($this->getMockTrackingResponse(), $this->getMockStatusResponse())
        );
        WebHookEventHandler::getInstance()->handleTrackingInfoEvent('test');

        /** @var \Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestOrderRepository $orderRepository */
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
        WebHookEventHandler::getInstance()->handleTrackingInfoEvent('test');

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

        $this->httpClient->setMockResponses(
            array_merge($this->getMockTrackingResponse(), $this->getMockStatusResponse())
        );
        WebHookEventHandler::getInstance()->handleTrackingInfoEvent('test');

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

        $this->httpClient->setMockResponses(
            array_merge($this->get404ErrorResponse(), $this->get404ErrorResponse())
        );
        WebHookEventHandler::getInstance()->handleTrackingInfoEvent('test');

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
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/shipmentLabels.json')
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
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/shipment.json')
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
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/tracking.json')
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
