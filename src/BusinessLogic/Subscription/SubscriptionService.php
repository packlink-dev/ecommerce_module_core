<?php

namespace Packlink\BusinessLogic\Subscription;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Subscription\Subscription;
use Packlink\BusinessLogic\Http\Proxy;

/**
 * Class SubscriptionService. Wraps Proxy calls to the Packlink subscription
 * API and returns null on failure so callers can hide promotional UI rather
 * than degrading the experience (spec AC-2.1.5).
 *
 * @package Packlink\BusinessLogic\Subscription
 */
class SubscriptionService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this service.
     *
     * @var static
     */
    protected static $instance;
    /**
     * @var Proxy
     */
    private $proxy;
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Returns the merchant's active subscription from the Packlink API,
     * or null if the API call fails (auth or network error).
     *
     * @return Subscription|null
     */
    public function getActiveSubscription()
    {
        try {
            return $this->getProxy()->getActiveSubscription();
        } catch (HttpBaseException $e) {
            Logger::logError(
                'Failed to fetch active subscription: ' . $e->getMessage(),
                'Core'
            );

            return null;
        }
    }

    /**
     * Returns the normalized plan tier ('FREE', 'PLUS', 'PREMIUM') or null on API failure.
     *
     * @return string|null
     */
    public function getPlanTier()
    {
        $subscription = $this->getActiveSubscription();

        if ($subscription === null) {
            return null;
        }

        return $subscription->getPlanTier();
    }

    /**
     * @return Proxy
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }

    /**
     * @return Configuration
     */
    protected function getConfigService()
    {
        if ($this->configuration === null) {
            $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configuration;
    }
}
