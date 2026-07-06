<?php

namespace Packlink\BusinessLogic\Http\DTO\Subscription;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class Subscription. Represents the merchant's active subscription returned
 * from GET /pro/subscriptions/client/active.
 *
 * @package Packlink\BusinessLogic\Http\DTO\Subscription
 */
class Subscription extends DataTransferObject
{
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $clientId;
    /**
     * @var string|null
     */
    public $activatedAt;
    /**
     * @var string
     */
    public $currentBillingCurrency;
    /**
     * @var float
     */
    public $currentBillingAmount;
    /**
     * @var SubscriptionPlan|null
     */
    public $plan;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'client_id' => $this->clientId,
            'activated_at' => $this->activatedAt,
            'current_billing_currency' => $this->currentBillingCurrency,
            'current_billing_amount' => $this->currentBillingAmount,
            'plan' => $this->plan !== null ? $this->plan->toArray() : array(),
        );
    }

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     */
    public static function fromArray(array $raw)
    {
        $subscription = new static();

        $subscription->id = static::getDataValue($raw, 'id');
        $subscription->clientId = static::getDataValue($raw, 'client_id');
        $subscription->activatedAt = static::getDataValue($raw, 'activated_at', null);
        $subscription->currentBillingCurrency = static::getDataValue($raw, 'current_billing_currency');
        $subscription->currentBillingAmount = (float)static::getDataValue($raw, 'current_billing_amount', 0);

        $planData = static::getDataValue($raw, 'plan', array());
        $subscription->plan = !empty($planData) && is_array($planData) ? SubscriptionPlan::fromArray($planData) : null;

        return $subscription;
    }

    /**
     * Returns the normalized plan tier: FREE, PLUS, or PREMIUM.
     * Defaults to FREE if the plan is missing or unrecognized.
     *
     * @return string One of: 'FREE', 'PLUS', 'PREMIUM'.
     */
    public function getPlanTier()
    {
        if ($this->plan === null) {
            return 'FREE';
        }

        $normalized = strtoupper(trim((string)$this->plan->name));

        if (in_array($normalized, array('FREE', 'PLUS', 'PREMIUM'), true)) {
            return $normalized;
        }

        return 'FREE';
    }
}
