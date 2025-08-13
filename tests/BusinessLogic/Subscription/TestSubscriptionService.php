<?php

namespace Logeecom\Tests\BusinessLogic\Subscription;

use Packlink\BusinessLogic\Http\Subscription\Interfaces\SubscriptionServiceInterface;

class TestSubscriptionService implements SubscriptionServiceInterface
{
    /**
     * Return value
     *
     * @var bool
     */
    public $value = false;

    /**
     * Sets the return value for hasPlusSubscription.
     *
     * @param bool $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }


    public function hasPlusSubscription()
    {
        return $this->value;
    }
}