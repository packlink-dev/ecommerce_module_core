<?php

namespace Packlink\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Tests\BusinessLogic\BaseSyncTest;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;

class UpdateShippingServicesTaskTest extends BaseSyncTest
{
    /**
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient
     */
    public $httpClient;
    /**
     * @var ShippingMethodService
     */
    public $shippingMethodService;
    /**
     * Tested task instance.
     *
     * @var UpdateShippingServicesTask
     */
    public $syncTask;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        MemoryStorage::reset();
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        $this->shopConfig->setAuthorizationToken('test_token');

        $me = $this;

        $testShopShippingMethodService = new TestShopShippingMethodService();
        TestServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () use ($testShopShippingMethodService) {
                return $testShopShippingMethodService;
            }
        );

        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($me) {
                return new Proxy($me->shopConfig, $me->httpClient);
            }
        );

        $this->shippingMethodService = ShippingMethodService::getInstance();
        TestServiceRegister::registerService(
            ShippingMethodService::CLASS_NAME,
            function () use ($me) {
                return $me->shippingMethodService;
            }
        );
    }

    protected function tearDown()
    {
        ShippingMethodService::resetInstance();
        MemoryStorage::reset();

        parent::tearDown();
    }

    /**
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testNoExecution()
    {
        // should not execute if user info or default parcel is not set.
        $this->syncTask->execute();

        self::assertCount(0, $this->shippingMethodService->getAllMethods());
        self::assertEmpty($this->httpClient->getHistory());
        $this->validate100Progress();
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testExecuteAllNew()
    {
        $this->prepareAndExecuteValidTask();

        self::assertCount(14, $this->shippingMethodService->getAllMethods());
        self::assertCount(0, $this->shippingMethodService->getActiveMethods());

        $this->validate100Progress();

        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, true);
        self::assertEquals(6, $repo->count($query));

        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, false);
        self::assertEquals(8, $repo->count($query));
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testExecuteNew()
    {
        $this->prepareAndExecuteValidTask();

        // in test repository in this test ids of services start from 4
        $this->shippingMethodService->activate(4);
        $activeMethods = $this->shippingMethodService->getActiveMethods();
        self::assertCount(1, $activeMethods);

        $method = $activeMethods[0];
        $services = $method->getShippingServices();
        self::assertCount(1, $services);
        self::assertEquals(20339, $services[0]->serviceId);
        self::assertEquals(5.98, $services[0]->totalPrice);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testExecuteLocalUpdate()
    {
        $this->prepareAndExecuteValidTask();

        $this->shippingMethodService->activate(4);
        $method = $this->shippingMethodService->getShippingMethod(4);

        $services = $method->getShippingServices();
        // previous price = 5.98
        $services[0]->totalPrice = 13;
        $method->setShippingServices($services);
        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $repo->update($method);

        $method = $this->shippingMethodService->getShippingMethod($method->getId());

        // check if price is updated.
        $services = $method->getShippingServices();
        self::assertCount(1, $services);
        self::assertEquals(13, $services[0]->totalPrice);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testExecuteAPIUpdate()
    {
        $this->prepareAndExecuteValidTask();

        $methodId = 4;
        // update locally first
        $this->shippingMethodService->activate($methodId);
        $method = $this->shippingMethodService->getShippingMethod($methodId);
        $services = $method->getShippingServices();
        // previous price 5.98
        $services[0]->totalPrice = 14.27;
        $method->setShippingServices($services);
        // add new service
        $method->addShippingService(new ShippingService(12345, 'new service', 'IT', 'IT', 12.43, 10.40, 2.03));
        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $repo->update($method);

        $method = $this->shippingMethodService->getShippingMethod($methodId);
        self::assertCount(2, $method->getShippingServices());

        // execute task once more. All services should be updated and invalid services should be deleted.
        $this->httpClient->setMockResponses($this->getValidMockResponses());
        $this->syncTask->execute();

        self::assertCount(14, $this->shippingMethodService->getAllMethods());
        self::assertCount(1, $this->shippingMethodService->getActiveMethods());

        $method = $this->shippingMethodService->getShippingMethod($methodId);
        $services = $method->getShippingServices();
        self::assertCount(1, $services);
        self::assertEquals(5.98, $services[0]->totalPrice);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testExecuteNewAndDelete()
    {
        $this->prepareAndExecuteValidTask();

        self::assertCount(14, $this->shippingMethodService->getAllMethods());
        self::assertCount(0, $this->shippingMethodService->getActiveMethods());

        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, true);
        self::assertEquals(6, $repo->count($query));

        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, false);
        self::assertEquals(8, $repo->count($query));

        // only international from IT do ES
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-ES')),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
                new HttpResponse(200, array(), '[]'),
            )
        );

        $this->syncTask->execute();

        self::assertCount(7, $this->shippingMethodService->getAllMethods());

        $repo = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, true);
        self::assertEquals(0, $repo->count($query));

        $query = new QueryFilter();
        $query->where('national', Operators::EQUALS, false);
        self::assertEquals(7, $repo->count($query));
    }

    /**
     * @expectedException \InvalidArgumentException
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testExecuteNonPlatformCountry()
    {
        $user = new User();
        $user->country = 'RS';
        $user->firstName = 'Test';
        $user->lastName = 'User';
        $user->email = 'test.user@example.com';

        $this->shopConfig->setUserInfo($user);

        $this->syncTask->execute();
    }

    /**
     * @return Task
     */
    protected function createSyncTaskInstance()
    {
        return new UpdateShippingServicesTask();
    }

    /**
     * @return \Packlink\BusinessLogic\Http\DTO\User
     */
    protected function getUser()
    {
        $user = new User();
        $user->country = 'IT';
        $user->firstName = 'Test';
        $user->lastName = 'User';
        $user->email = 'test.user@example.com';

        return $user;
    }

    protected function getValidMockResponses()
    {
        return array(
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-IT')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-ES')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-DE')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-FR')),
            new HttpResponse(200, array(), $this->getDemoServiceDeliveryDetails('IT-US')),
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function prepareAndExecuteValidTask()
    {
        $this->shopConfig->setUserInfo($this->getUser());
        $this->shopConfig->setDefaultParcel(ParcelInfo::defaultParcel());

        $this->httpClient->setMockResponses($this->getValidMockResponses());

        $this->syncTask->execute();
        // after this execution, resulting data is as follows:
        /*
		[
		  {
			"id": 4,
			"carrierName": "NEXIVE",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": false,
			"destinationDropOff": false,
			"expressDelivery": false,
			"deliveryTime": "3 DAYS",
			"national": true,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 20339,
				"serviceName": "",
				"departure": "IT",
				"destination": "IT",
				"totalPrice": 5.98,
				"basePrice": 4.94,
				"taxPrice": 1.04
			  }
			]
		  },
		  {
			"id": 5,
			"carrierName": "Poste Italiane",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": false,
			"destinationDropOff": false,
			"expressDelivery": false,
			"deliveryTime": "2 DAYS",
			"national": true,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 21317,
				"serviceName": "",
				"departure": "IT",
				"destination": "IT",
				"totalPrice": 7.08,
				"basePrice": 5.85,
				"taxPrice": 1.23
			  },
			  {
				"serviceId": 20203,
				"serviceName": "",
				"departure": "IT",
				"destination": "IT",
				"totalPrice": 7.42,
				"basePrice": 6.13,
				"taxPrice": 1.29
			  }
			]
		  },
		  {
			"id": 6,
			"carrierName": "TNT",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": false,
			"destinationDropOff": false,
			"expressDelivery": false,
			"deliveryTime": "2 DAYS",
			"national": true,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 20945,
				"serviceName": "",
				"departure": "IT",
				"destination": "IT",
				"totalPrice": 8.85,
				"basePrice": 7.31,
				"taxPrice": 1.54
			  },
			  {
				"serviceId": 20943,
				"serviceName": "",
				"departure": "IT",
				"destination": "IT",
				"totalPrice": 172.26,
				"basePrice": 142.36,
				"taxPrice": 29.9
			  }
			]
		  },
		  {
			"id": 7,
			"carrierName": "SDA",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": false,
			"destinationDropOff": false,
			"expressDelivery": true,
			"deliveryTime": "2 DAYS",
			"national": true,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 20189,
				"serviceName": "",
				"departure": "IT",
				"destination": "IT",
				"totalPrice": 10.66,
				"basePrice": 8.81,
				"taxPrice": 1.85
			  }
			]
		  },
		  {
			"id": 8,
			"carrierName": "Bartolini",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": false,
			"destinationDropOff": false,
			"expressDelivery": true,
			"deliveryTime": "1 DAYS",
			"national": true,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 20127,
				"serviceName": "",
				"departure": "IT",
				"destination": "IT",
				"totalPrice": 11.3,
				"basePrice": 9.34,
				"taxPrice": 1.96
			  }
			]
		  },
		  {
			"id": 9,
			"carrierName": "UPS",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": false,
			"destinationDropOff": false,
			"expressDelivery": false,
			"deliveryTime": "1 DAYS",
			"national": true,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 20131,
				"serviceName": "",
				"departure": "IT",
				"destination": "IT",
				"totalPrice": 17.98,
				"basePrice": 14.86,
				"taxPrice": 3.12
			  }
			]
		  },
		  {
			"id": 10,
			"carrierName": "UPS",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": true,
			"destinationDropOff": true,
			"expressDelivery": false,
			"deliveryTime": "2 DAYS",
			"national": false,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 20615,
				"serviceName": "",
				"departure": "IT",
				"destination": "ES",
				"totalPrice": 10.13,
				"basePrice": 8.37,
				"taxPrice": 1.76
			  }
			]
		  },
		  {
			"id": 11,
			"carrierName": "UPS",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": true,
			"destinationDropOff": true,
			"expressDelivery": true,
			"deliveryTime": "1 DAYS",
			"national": false,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 20611,
				"serviceName": "",
				"departure": "IT",
				"destination": "ES",
				"totalPrice": 11.88,
				"basePrice": 9.82,
				"taxPrice": 2.06
			  }
			]
		  },
		  {
			"id": 12,
			"carrierName": "STARPACK",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": false,
			"destinationDropOff": false,
			"expressDelivery": false,
			"deliveryTime": "3 DAYS",
			"national": false,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 21105,
				"serviceName": "",
				"departure": "IT",
				"destination": "ES",
				"totalPrice": 20.75,
				"basePrice": 17.15,
				"taxPrice": 3.6
			  },
			  {
				"serviceId": 21103,
				"serviceName": "",
				"departure": "IT",
				"destination": "ES",
				"totalPrice": 34.64,
				"basePrice": 34.64,
				"taxPrice": 0
			  },
			  {
				"serviceId": 21279,
				"serviceName": "",
				"departure": "IT",
				"destination": "US",
				"totalPrice": 24.65,
				"basePrice": 24.65,
				"taxPrice": 0
			  }
			]
		  },
		  {
			"id": 13,
			"carrierName": "Poste Italiane",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": false,
			"destinationDropOff": false,
			"expressDelivery": false,
			"deliveryTime": "5 DAYS",
			"national": false,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 20209,
				"serviceName": "",
				"departure": "IT",
				"destination": "ES",
				"totalPrice": 19.83,
				"basePrice": 16.39,
				"taxPrice": 3.44
			  },
			  {
				"serviceId": 20329,
				"serviceName": "",
				"departure": "IT",
				"destination": "US",
				"totalPrice": 38.54,
				"basePrice": 38.54,
				"taxPrice": 0
			  }
			]
		  },
		  {
			"id": 14,
			"carrierName": "Bartolini",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": false,
			"destinationDropOff": false,
			"expressDelivery": false,
			"deliveryTime": "4 DAYS",
			"national": false,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 20126,
				"serviceName": "",
				"departure": "IT",
				"destination": "ES",
				"totalPrice": 20.35,
				"basePrice": 16.82,
				"taxPrice": 3.53
			  }
			]
		  },
		  {
			"id": 15,
			"carrierName": "UPS",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": false,
			"destinationDropOff": false,
			"expressDelivery": false,
			"deliveryTime": "2 DAYS",
			"national": false,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 20030,
				"serviceName": "",
				"departure": "IT",
				"destination": "ES",
				"totalPrice": 24.63,
				"basePrice": 20.36,
				"taxPrice": 4.27
			  }
			]
		  },
		  {
			"id": 16,
			"carrierName": "TNT",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": false,
			"destinationDropOff": false,
			"expressDelivery": false,
			"deliveryTime": "5 DAYS",
			"national": false,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 20255,
				"serviceName": "",
				"departure": "IT",
				"destination": "ES",
				"totalPrice": 45.7,
				"basePrice": 45.7,
				"taxPrice": 0
			  },
			  {
				"serviceId": 20130,
				"serviceName": "",
				"departure": "IT",
				"destination": "ES",
				"totalPrice": 28.16,
				"basePrice": 23.27,
				"taxPrice": 4.89
			  },
			  {
				"serviceId": 21051,
				"serviceName": "",
				"departure": "IT",
				"destination": "US",
				"totalPrice": 58.09,
				"basePrice": 58.09,
				"taxPrice": 0
			  }
			]
		  },
		  {
			"id": 17,
			"carrierName": "UPS",
			"title": null,
			"enabled": true,
			"activated": false,
			"logoUrl": null,
			"displayLogo": true,
			"departureDropOff": false,
			"destinationDropOff": false,
			"expressDelivery": true,
			"deliveryTime": "1 DAYS",
			"national": false,
			"pricingPolicy": 1,
			"shippingServices": [
			  {
				"serviceId": 20937,
				"serviceName": "",
				"departure": "IT",
				"destination": "US",
				"totalPrice": 37.67,
				"basePrice": 37.67,
				"taxPrice": 0
			  }
			]
		  }
		]
         */
    }

    private function getDemoServiceDeliveryDetails($countries)
    {
        return file_get_contents(
            __DIR__ . "/../Common/ApiResponses/ShippingServices/ShippingServiceDetails-$countries.json"
        );
    }
}
