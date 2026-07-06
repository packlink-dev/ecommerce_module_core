<?php

namespace Logeecom\Tests\BusinessLogic\Subscription;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\Subscription\SubscriptionService;

/**
 * Class SubscriptionServiceTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Subscription
 */
class SubscriptionServiceTest extends BaseTestWithServices
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

        TestServiceRegister::registerService(
            SubscriptionService::CLASS_NAME,
            function () {
                return SubscriptionService::getInstance();
            }
        );
    }

    /**
     * @after
     */
    public function after()
    {
        SubscriptionService::resetInstance();
        parent::after();
    }

    public function testGetPlanTierSuccess()
    {
        $this->mockSubscription('Free');

        self::assertSame('FREE', $this->getService()->getPlanTier());
    }

    public function testGetPlanTierPlus()
    {
        $this->mockSubscription('Plus');

        self::assertSame('PLUS', $this->getService()->getPlanTier());
    }

    public function testGetPlanTierPremium()
    {
        $this->mockSubscription('Premium');

        self::assertSame('PREMIUM', $this->getService()->getPlanTier());
    }

    public function testGetPlanTierApiFailureReturnsNull()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(500, array(), '{"message":"Server error"}')));

        self::assertNull($this->getService()->getPlanTier());
    }

    public function testGetPlanTierAuth401ReturnsNull()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(401, array(), '{"message":"Unauthorized"}')));

        self::assertNull($this->getService()->getPlanTier());
    }

    public function testGetActiveSubscriptionSuccess()
    {
        $this->mockSubscription('Plus');

        $subscription = $this->getService()->getActiveSubscription();

        self::assertInstanceOf('Packlink\BusinessLogic\Http\DTO\Subscription\Subscription', $subscription);
        self::assertSame('Plus', $subscription->plan->name);
    }

    public function testGetActiveSubscriptionFailureReturnsNull()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(500, array(), '{"message":"err"}')));

        self::assertNull($this->getService()->getActiveSubscription());
    }

    /**
     * @param string $planName
     */
    private function mockSubscription($planName)
    {
        $payload = json_encode(array(
            'id' => 'sub_1',
            'client_id' => 'client_1',
            'activated_at' => '2025-01-01T00:00:00Z',
            'current_billing_currency' => 'EUR',
            'current_billing_amount' => 0,
            'plan' => array(
                'id' => 'plan_1',
                'code' => 'code_1',
                'name' => $planName,
            ),
        ));

        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $payload)));
    }

    /**
     * @return SubscriptionService
     */
    private function getService()
    {
        /** @var SubscriptionService $service */
        $service = TestServiceRegister::getService(SubscriptionService::CLASS_NAME);

        return $service;
    }
}
