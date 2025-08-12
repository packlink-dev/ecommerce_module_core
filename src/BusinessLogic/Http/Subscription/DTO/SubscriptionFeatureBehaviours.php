<?php

namespace Packlink\BusinessLogic\Http\Subscription\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

class SubscriptionFeatureBehaviours extends DataTransferObject
{
    /**
     * @var array|null
     */
    public $subscription;

    /**
     * @var array
     */
    public $clientFeatureBehaviours;

    public function __construct()
    {
        $this->subscription = null;
        $this->clientFeatureBehaviours = array();
    }

    /**
     * @param array $data
     * @return SubscriptionFeatureBehaviours
     */
    public static function fromArray(array $data)
    {
        $instance = new self();

        $instance->subscription = !empty($data['subscription']) && is_array($data['subscription'])
            ? $data['subscription']
            : null;

        $instance->clientFeatureBehaviours = !empty($data['client_feature_behaviours']) && is_array($data['client_feature_behaviours'])
            ? $data['client_feature_behaviours']
            : array();

        return $instance;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'subscription' => $this->subscription,
            'client_feature_behaviours' => $this->clientFeatureBehaviours,
        );
    }
}