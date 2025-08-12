<?php

namespace Packlink\BusinessLogic\Http\Subscription\Services;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Http\Subscription\Interfaces\SubscriptionServiceInterface;
use Packlink\BusinessLogic\Http\Subscription\SubscriptionProxy;

class SubscriptionService extends BaseService implements SubscriptionServiceInterface
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;


    /**
     * @return bool
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function hasPlusSubscription()
    {
        $response = $this->getSubscriptionProxy()->getSubscriptionFeatureBehaviours();

        if ($response->subscription === null ||
            !isset($response->subscription['plan_name'], $response->subscription['state'])) {

            return false;
        }

        return strtolower($response->subscription['plan_name']) === 'plus' &&
            strtolower($response->subscription['state']) === 'active';
    }

    /**
     * Returns proxy instance.
     *
     * @return object instance.
     */
    protected function getSubscriptionProxy()
    {
        return ServiceRegister::getService(SubscriptionProxy::CLASS_NAME);
    }

}