<?php

namespace Logeecom\Tests\BusinessLogic\WebHook;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\CashOnDelivery\TestCashOnDeliveryService;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\BootstrapComponent;
use Packlink\BusinessLogic\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\BusinessLogic\WebHook\WebHookEventHandler;

/**
 * Class WebHookHandlerTest
 * @package Logeecom\Tests\BusinessLogic\WebHook
 */
class WebHookHandlerTest extends BaseTestWithServices
{
    /**
     * @var \Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService
     */
    public $orderShipmentDetailsService;

    /** @var TestCashOnDeliveryService $cashOnDeliveryService*/

    private $cashOnDeliveryService;

    /**
     * @before
     * @inheritdoc
     */
    protected function before()
    {
        parent::before();

        BootstrapComponent::init();
        $me = $this;

        TestRepositoryRegistry::registerRepository(
            OrderShipmentDetails::getClassName(),
            MemoryRepository::getClassName()
        );

        $this->orderShipmentDetailsService = OrderShipmentDetailsService::getInstance();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(CashOnDelivery::CLASS_NAME, MemoryRepository::getClassName());

        $this->cashOnDeliveryService = new TestCashOnDeliveryService();
        ServiceRegister::registerService(
            CashOnDeliveryServiceInterface::CLASS_NAME,
            function () use ($me) {
                return $me->cashOnDeliveryService;
            }
        );

        TestServiceRegister::registerService(
            OrderShipmentDetailsService::CLASS_NAME,
            function () use ($me) {
                return $me->orderShipmentDetailsService;
            }
        );

        $orderRepository = new TestShopOrderService();
        $configService = new TestShopConfiguration();
        $configService->setAuthorizationToken('test');

        TestServiceRegister::registerService(
            ShopOrderService::CLASS_NAME,
            function () use ($orderRepository) {
                return $orderRepository;
            }
        );

        TestServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () use ($configService) {
                return $configService;
            }
        );
    }

    /**
     * @after
     * @inheritDoc
     */
    protected function after()
    {
        WebHookEventHandler::resetInstance();
        OrderService::resetInstance();

        parent::after();
    }

    /**
     * Tests setting of shipping status
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testHandleShippingDeliveredEvent()
    {
        $this->orderShipmentDetailsService->setReference('test_order_id', 'test');
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(
                    200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/shipmentDelivered.json')
                ),
            )
        );
        $webhookHandler = WebHookEventHandler::getInstance();
        $input = $this->getShippingStatusEventBody();
        $webhookHandler->handle($input);

        /** @var TestShopOrderService $shopOrderService */
        $shopOrderService = ServiceRegister::getService(ShopOrderService::CLASS_NAME);
        $order = $shopOrderService->getOrder('test_order_id');

        $this->assertNotNull($order);
        $this->assertEquals(ShipmentStatus::STATUS_DELIVERED, $order->getStatus());
    }

    /**
     * Tests when API fails
     */
    public function testHandleShippingStatusEventHttpError()
    {
        $this->httpClient->setMockResponses($this->getErrorMockResponse());
        $webhookHandler = WebHookEventHandler::getInstance();
        $input = $this->getShippingStatusEventBody();
        $webhookHandler->handle($input);

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
        $this->orderShipmentDetailsService->setReference('test_order_id', 'test');
        /** @var \Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService $orderRepository */
        $orderRepository = ServiceRegister::getService(ShopOrderService::CLASS_NAME);
        $orderRepository->shouldThrowOrderNotFoundException(true);

        $this->httpClient->setMockResponses($this->getMockStatusResponse());
        $webhookHandler = WebHookEventHandler::getInstance();
        $input = $this->getShippingStatusSuccessEventBody();
        $webhookHandler->handle($input);

        $this->assertNotEmpty($this->shopLogger->loggedMessages);
        $this->assertEquals('Order not found.', $this->shopLogger->loggedMessages[1]->getMessage());
    }

    /**
     * Tests setting of shipping tracking
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testHandleShippingTrackingEvent()
    {
        $this->orderShipmentDetailsService->setReference('test_order_id', 'test');
        $this->httpClient->setMockResponses(
            array_merge($this->getMockTrackingResponse(), $this->getMockStatusResponse())
        );
        $webhookHandler = WebHookEventHandler::getInstance();
        $input = $this->getTrackingEventBody();
        $webhookHandler->handle($input);

        /** @var \Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService $orderRepository */
        $orderRepository = ServiceRegister::getService(ShopOrderService::CLASS_NAME);
        $order = $orderRepository->getOrder('test_order_id');

        $this->assertNotNull($order);
        $trackingHistories = $order->getShipment()->getTrackingHistory();
        $this->assertCount(3, $trackingHistories);
        $this->assertEquals(14242322, $trackingHistories[0]->getTimestamp());
        $this->assertEquals('DELIVERED', $trackingHistories[0]->getDescription());
        $this->assertEquals('MIAMI', $trackingHistories[0]->getCity());
        $this->assertEquals('31016279', $order->getShipment()->getTrackingNumber());
    }

    /**
     * Tests when API fails
     */
    public function testHandleShippingTrackingEventHttpError()
    {
        $this->httpClient->setMockResponses($this->getErrorMockResponse());
        $webhookHandler = WebHookEventHandler::getInstance();
        $input = $this->getTrackingEventBody();
        $webhookHandler->handle($input);

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
        $this->httpClient->setMockResponses(
            array_merge($this->getMockTrackingResponse(), $this->getMockStatusResponse())
        );
        $webhookHandler = WebHookEventHandler::getInstance();
        $input = $this->getTrackingEventBody();
        $webhookHandler->handle($input);

        $this->assertNotEmpty($this->shopLogger->loggedMessages);
        $this->assertEquals(
            'Order details not found for reference "test".',
            $this->shopLogger->loggedMessages[1]->getMessage()
        );
    }

    /**
     * Tests when reference has no tracking info associated with it.
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testHandleShippingTrackingEventWhen404IsThrown()
    {
        /** @var TestShopOrderService $orderRepository */
        $orderRepository = ServiceRegister::getService(ShopOrderService::CLASS_NAME);

        $this->httpClient->setMockResponses(
            array_merge($this->get404ErrorResponse(), $this->get404ErrorResponse())
        );
        $webhookHandler = WebHookEventHandler::getInstance();
        $input = $this->getTrackingEventBody();
        $webhookHandler->handle($input);

        $this->assertEmpty($orderRepository->getOrder('test')->getShipment()->getTrackingHistory());
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
            ),
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
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/shipment.json')
            ),
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/tracking.json')
            ),
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
            new HttpResponse(400, array(), null),
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
            new HttpResponse(404, array(), null),
        );
    }

    /**
     * Returns body of the shipping status event.
     *
     * @return string
     */
    private function getShippingStatusEventBody()
    {
        return file_get_contents(__DIR__ . '/../Common/WebhookEvents/shippingStatusEventBody.json');
    }

    /**
     * Returns body of the accepted shipping status event.
     *
     * @return string
     */
    private function getShippingStatusSuccessEventBody()
    {
        return file_get_contents(__DIR__ . '/../Common/WebhookEvents/shippingStatusSuccessEventBody.json');
    }

    /**
     * Returns body of the tracking update event.
     *
     * @return string
     */
    private function getTrackingEventBody()
    {
        return file_get_contents(__DIR__ . '/../Common/WebhookEvents/trackingEventBody.json');
    }
}
