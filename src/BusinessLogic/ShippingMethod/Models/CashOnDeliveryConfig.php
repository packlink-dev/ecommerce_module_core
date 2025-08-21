<?php

namespace Packlink\BusinessLogic\ShippingMethod\Models;

class CashOnDeliveryConfig
{
    /**@var float $applyPercentageCashOnDelivery */
    public $applyPercentageCashOnDelivery = 0.0;

    /**@var bool $offered */

    public $offered = false;

    /**@var float $maxCashOnDelivery */
    public $maxCashOnDelivery = 0.0;

    /**@var float $minCashOnDelivery */
    public $minCashOnDelivery = 0.0;

    public function __construct(
        $applyPercentageCashOnDelivery = 0.0,
        $offered = false,
        $maxCashOnDelivery = 0.0,
        $minCashOnDelivery = 0.0
    ) {
        $this->applyPercentageCashOnDelivery = $applyPercentageCashOnDelivery;
        $this->offered = $offered;
        $this->maxCashOnDelivery = $maxCashOnDelivery;
        $this->minCashOnDelivery = $minCashOnDelivery;
    }

    public static function fromArray($data)
    {
        return new self(
            isset($data['apply_percentage_cash_on_delivery']) ? (float)$data['apply_percentage_cash_on_delivery'] : 0,
            isset($data['offered']) ? (bool)$data['offered'] : false,
            isset($data['max_cash_on_delivery']) ? (float)$data['max_cash_on_delivery'] : 0,
            isset($data['min_cash_on_delivery']) ? (float)$data['min_cash_on_delivery'] : 0
        );
    }

    public function toArray()
    {
        return array(
            'apply_percentage_cash_on_delivery' => $this->applyPercentageCashOnDelivery,
            'offered' => $this->offered,
            'max_cash_on_delivery' => $this->maxCashOnDelivery,
            'min_cash_on_delivery' => $this->minCashOnDelivery,
        );
    }
}