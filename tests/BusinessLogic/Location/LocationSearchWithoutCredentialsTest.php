<?php

namespace Logeecom\Tests\BusinessLogic\Location;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Location\LocationService;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Class LocationSearchWithoutCredentialsTest. A merchant without a Packlink account searches postal codes
 * without credentials; a connected merchant keeps the authenticated search.
 *
 * @package Logeecom\Tests\BusinessLogic\Location
 */
class LocationSearchWithoutCredentialsTest extends BaseTestWithServices
{
    /**
     * @before
     * @inheritDoc
     */
    public function before()
    {
        parent::before();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());
        $shopShippingMethodService = new TestShopShippingMethodService();
        TestServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () use ($shopShippingMethodService) {
                return $shopShippingMethodService;
            }
        );
        TestServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () {
                return ShippingMethodService::getInstance();
            }
        );
        TestServiceRegister::registerService(
            PackageTransformer::CLASS_NAME,
            function () {
                return PackageTransformer::getInstance();
            }
        );
        TestServiceRegister::registerService(
            LocationService::CLASS_NAME,
            function () {
                return LocationService::getInstance();
            }
        );
    }

    /**
     * @after
     * @inheritDoc
     */
    public function after()
    {
        LocationService::resetInstance();
        ShippingMethodService::resetInstance();
        parent::after();
    }

    public function testSearchWithoutTokenSendsNoCredentials()
    {
        $this->shopConfig->setAuthorizationToken('');
        $this->httpClient->setMockResponses($this->getResponses());

        $result = LocationService::getInstance()->searchLocations('ES', '28001');

        $this->assertCount(1, $result);
        $this->assertEquals('28001', $result[0]->zipcode);
        foreach ($this->httpClient->getHistory() as $request) {
            foreach ($request['headers'] as $header) {
                $this->assertStringStartsNotWith('Authorization', $header);
            }
        }
    }

    public function testSearchWithTokenKeepsCredentials()
    {
        $this->httpClient->setMockResponses($this->getResponses());

        LocationService::getInstance()->searchLocations('ES', '28001');

        $headers = $this->httpClient->getLastRequestHeaders();
        $this->assertContains('Authorization: mock-auth-token', $headers);
    }

    /**
     * @return HttpResponse[]
     */
    private function getResponses()
    {
        return array(
            new HttpResponse(
                200,
                array(),
                '[{"id":"65","name":"Spain - Mainland","isoCode":"ES","phonePrefix":"+34","hasPostalCodes":true}]'
            ),
            new HttpResponse(
                200,
                array(),
                '[{"city":"Madrid","postal_zone_id":65,"text":"28001 - Madrid","zipcode":"28001","state":"ES","id":"a"}]'
            ),
        );
    }
}
