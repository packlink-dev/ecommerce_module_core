<?php

namespace Packlink\BusinessLogic\Http\DTO\Subscription;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class SubscriptionPlan. Represents the nested plan object in the
 * GET /pro/subscriptions/client/active response.
 *
 * @package Packlink\BusinessLogic\Http\DTO\Subscription
 */
class SubscriptionPlan extends DataTransferObject
{
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $code;
    /**
     * Plan display name (e.g. "Free", "Plus", "Premium").
     *
     * @var string
     */
    public $name;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
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
        $plan = new static();

        $plan->id = static::getDataValue($raw, 'id');
        $plan->code = static::getDataValue($raw, 'code');
        $plan->name = static::getDataValue($raw, 'name');

        return $plan;
    }
}
