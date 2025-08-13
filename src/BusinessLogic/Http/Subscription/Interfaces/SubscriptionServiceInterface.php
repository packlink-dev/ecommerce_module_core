<?php

namespace Packlink\BusinessLogic\Http\Subscription\Interfaces;

interface SubscriptionServiceInterface
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * @return bool
     */
    public function hasPlusSubscription();
}