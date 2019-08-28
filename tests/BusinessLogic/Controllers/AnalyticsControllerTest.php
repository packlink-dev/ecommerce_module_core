<?php
/** @noinspection PhpMissingDocCommentInspection */

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\AnalyticsController;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\Warehouse;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class AnalyticsControllerTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Controllers
 */
class AnalyticsControllerTest extends BaseTestWithServices
{
    /**
     * @var ShippingMethodService
     */
    public $shippingMethodService;
    /**
     * @var TestShopShippingMethodService
     */
    public $testShopShippingMethodService;
    /**
     * @var TestHttpClient
     */
    public $httpClient;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        /** @noinspection PhpUnhandledExceptionInspection */
        TestRepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        $me = $this;
        $me->shopConfig->setAuthorizationToken('test_token');

        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

        $me->testShopShippingMethodService = new TestShopShippingMethodService();
        TestServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () use ($me) {
                return $me->testShopShippingMethodService;
            }
        );

        $me->shippingMethodService = ShippingMethodService::getInstance();
        TestServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () use ($me) {
                return $me->shippingMethodService;
            }
        );

        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($me) {
                return new Proxy($me->shopConfig, $me->httpClient);
            }
        );
    }

    public function tearDown()
    {
        ShippingMethodService::resetInstance();
        TestRepositoryRegistry::cleanUp();

        parent::tearDown();
    }

    public function testSendSetupEvent()
    {
        AnalyticsController::sendSetupEvent();
        $this->assertNull($this->httpClient->getHistory());

        $shippingMethod = $this->shippingMethodService->add($this->getShippingServiceDetails(1));
        AnalyticsController::sendSetupEvent();
        $this->assertNull($this->httpClient->getHistory());

        $this->shippingMethodService->activate($shippingMethod->getId());
        AnalyticsController::sendSetupEvent();
        $this->assertNull($this->httpClient->getHistory());

        $this->shopConfig->setDefaultParcel(ParcelInfo::defaultParcel());
        AnalyticsController::sendSetupEvent();
        $this->assertNull($this->httpClient->getHistory());

        $this->shopConfig->setDefaultWarehouse(new Warehouse());

        AnalyticsController::sendSetupEvent();
        $this->assertCount(1, $this->httpClient->getHistory());

        AnalyticsController::sendSetupEvent();
        // once send, event should not be repeated
        $this->assertCount(1, $this->httpClient->getHistory());
    }

    public function testSendOtherServicesDisabledEvent()
    {
        AnalyticsController::sendOtherServicesDisabledEvent();
        $this->assertCount(1, $this->httpClient->getHistory());

        AnalyticsController::sendOtherServicesDisabledEvent();
        $this->assertCount(2, $this->httpClient->getHistory());
    }

    private function getShippingServiceDetails($id)
    {
        $details = ShippingServiceDetails::fromArray(
            array(
                'id' => $id,
                'carrier_name' => 'test carrier',
                'service_name' => 'test service',
                'currency' => 'EUR',
                'country' => 'IT',
                'dropoff' => false,
                'delivery_to_parcelshop' => false,
                'category' => 'express',
                'transit_time' => '3 DAYS',
                'transit_hours' => 72,
                'first_estimated_delivery_date' => '2019-01-05',
                'national' => true,
                'price' => array(
                    'total_price' => 13.76,
                    'base_price' => 10.76,
                    'tax_price' => 3,
                ),
            )
        );

        $details->departureCountry = 'IT';
        $details->destinationCountry = 'IT';
        $details->national = true;

        return $details;
    }
}
