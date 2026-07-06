<?php

namespace Logeecom\Tests\BusinessLogic\Proxy;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\Subscription\Subscription;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class SubscriptionProxyTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Proxy
 */
class SubscriptionProxyTest extends BaseTestWithServices
{
    /**
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
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetActiveSubscriptionSuccess()
    {
        $body = file_get_contents(__DIR__ . '/../Common/ApiResponses/subscription.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $body)));

        $subscription = $this->getProxy()->getActiveSubscription();

        self::assertInstanceOf(Subscription::class, $subscription);
        self::assertSame('sub_abc123', $subscription->id);
        self::assertSame('client_xyz789', $subscription->clientId);
        self::assertNotNull($subscription->plan);
        self::assertSame('Plus', $subscription->plan->name);
    }

    /**
     * Asserts the request URL is the un-versioned subscription endpoint
     * (https://api.packlink.com/pro/... not https://api.packlink.com/v1/pro/...).
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetActiveSubscriptionApiUrl()
    {
        $body = file_get_contents(__DIR__ . '/../Common/ApiResponses/subscription.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $body)));

        $this->getProxy()->getActiveSubscription();

        $lastRequest = $this->httpClient->getLastRequest();
        self::assertSame(
            'https://api.packlink.com/pro/subscriptions/client/active',
            $lastRequest['url']
        );
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetActiveSubscription401()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(401, array(), '{"message":"Unauthorized"}')));

        $thrown = null;
        try {
            $this->getProxy()->getActiveSubscription();
        } catch (HttpAuthenticationException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown);
        self::assertSame(401, $thrown->getCode());
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     */
    public function testGetActiveSubscription500()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(500, array(), '{"message":"Server error"}')));

        $thrown = null;
        try {
            $this->getProxy()->getActiveSubscription();
        } catch (HttpRequestException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown);
        self::assertSame(500, $thrown->getCode());
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
