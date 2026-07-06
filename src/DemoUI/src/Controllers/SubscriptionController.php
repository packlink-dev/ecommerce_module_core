<?php

namespace Packlink\DemoUI\Controllers;

/**
 * Class SubscriptionController.
 *
 * @package Packlink\DemoUI\Controllers
 */
class SubscriptionController extends BaseHttpController
{
    /**
     * Returns the merchant's subscription plan tier and display name.
     */
    public function getPlan()
    {
        $ctrl = new \Packlink\BusinessLogic\Controllers\SubscriptionController();

        $this->output($ctrl->getPlan()->toArray());
    }

    /**
     * Returns the promotional banner data (plan tier, localized label, upgrade URL).
     */
    public function getPromotionalBanner()
    {
        $ctrl = new \Packlink\BusinessLogic\Controllers\SubscriptionController();

        $this->output($ctrl->getPromotionalBanner()->toArray());
    }
}
