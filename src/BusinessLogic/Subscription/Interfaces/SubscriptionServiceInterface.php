<?php

namespace Packlink\BusinessLogic\Subscription\Interfaces;

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