<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\Http\DTO\BaseDto;

/**
 * Class DashboardStatus
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class DashboardStatus extends BaseDto
{
    /**
     * Shipping method set flag.
     *
     * @var bool
     */
    public $isShippingMethodSet;
    /**
     * Parcel set flag.
     *
     * @var bool
     */
    public $isParcelSet;
    /**
     * Order status mappings set flag.
     *
     * @var bool
     */
    public $isOrderStatusMappingsSet;
    /**
     * Warehouse set flag.
     *
     * @var bool
     */
    public $isWarehouseSet;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'parcelSet' => $this->isParcelSet,
            'orderStatusMappingsSet' => $this->isOrderStatusMappingsSet,
            'warehouseSet' => $this->isWarehouseSet,
            'shippingMethodSet' => $this->isShippingMethodSet,
        );
    }
}
