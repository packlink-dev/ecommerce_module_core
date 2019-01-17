<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\Http\DTO\BaseDto;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class ShippingMethodResponse
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class ShippingMethodResponse extends BaseDto
{
    /**
     * Shipping method identifier.
     *
     * @var int
     */
    public $id;
    /**
     * Shipping method name.
     *
     * @var string
     */
    public $name;
    /**
     * Shipping method title.
     *
     * @var string
     */
    public $title;
    /**
     * Description of delivery.
     *
     * @var string
     */
    public $deliveryDescription;
    /**
     * Shipping delivery type.
     *
     * @var string
     */
    public $deliveryType;
    /**
     * Parcel origin type.
     *
     * @var string
     */
    public $parcelOrigin;
    /**
     * Carrier logo URL.
     *
     * @var string
     */
    public $logoUrl;
    /**
     * Parcel destination type.
     *
     * @var string
     */
    public $parcelDestination;
    /**
     * Price policy code.
     *
     * @var int
     */
    public $pricePolicy;
    /**
     * Show logo.
     *
     * @var bool
     */
    public $showLogo = true;
    /**
     * Selected flag.
     *
     * @var bool
     */
    public $selected = false;
    /**
     * Percent price policy.
     *
     * @var PercentPricePolicy
     */
    public $percentPricePolicy;
    /**
     * Fixed price policy.
     *
     * @var FixedPricePolicy[]
     */
    public $fixedPricePolicy;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        $result = array(
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'deliveryDescription' => $this->deliveryDescription,
            'deliveryType' => $this->deliveryType,
            'parcelOrigin' => $this->parcelOrigin,
            'parcelDestination' => $this->parcelDestination,
            'logoUrl' => $this->logoUrl,
            'showLogo' => $this->showLogo,
            'selected' => $this->selected,
            'pricePolicy' => $this->pricePolicy,
        );

        if ($this->pricePolicy === ShippingMethod::PRICING_POLICY_PERCENT && $this->percentPricePolicy) {
            $result['percentPricePolicy'] = $this->percentPricePolicy->toArray();
        }

        if ($this->pricePolicy === ShippingMethod::PRICING_POLICY_FIXED && $this->fixedPricePolicy) {
            $result['fixedPricePolicy'] = array();
            foreach ($this->fixedPricePolicy as $item) {
                $result['fixedPricePolicy'][] = $item->toArray();
            }
        }

        return $result;
    }
}
