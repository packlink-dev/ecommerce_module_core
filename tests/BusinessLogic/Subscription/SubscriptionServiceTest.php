<?php

namespace Logeecom\Tests\BusinessLogic\Subscription;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Http\Subscription\Services\SubscriptionService;

/**
 * Class SubscriptionServiceTest
 *
 * @package Logeecom\Tests\BusinessLogic\Subscription
 */
class SubscriptionServiceTest extends BaseTestWithServices
{
    /**
     * @return void
     */
    public function testHasPlusSubscriptionReturnsTrue()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/subscriptionFeatureBehaviorsPlus.json');

        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $service = ServiceRegister::getService(SubscriptionService::CLASS_NAME);

        $result = $service->hasPlusSubscription();

        $this->assertTrue($result);
    }

    /**
     * @return void
     */
    public function testHasPlusSubscriptionReturnsFalse()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/subscriptionFeatureBehaviorsBasic.json');

        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $service = ServiceRegister::getService(SubscriptionService::CLASS_NAME);

        $result = $service->hasPlusSubscription();

        $this->assertFalse($result);
    }
}