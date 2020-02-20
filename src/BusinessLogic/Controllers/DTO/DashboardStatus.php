<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\DTO\FrontDto;

/**
 * Class DashboardStatus.
 *
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class DashboardStatus extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'dashboard_status';
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
     * Fields for this DTO. Needed for validation and transformation from/to array.
     *
     * @var array
     */
    protected static $fields = array(
        'isShippingMethodSet',
        'isParcelSet',
        'isWarehouseSet',
        'isOrderStatusMappingsSet',
    );
}
