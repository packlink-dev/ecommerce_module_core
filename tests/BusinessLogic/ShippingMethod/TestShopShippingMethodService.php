<?php

namespace Logeecom\Tests\BusinessLogic\ShippingMethod;

use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class TestShopShippingMethodService.
 *
 * @package Logeecom\Tests\BusinessLogic\ShippingMethod
 */
class TestShopShippingMethodService implements ShopShippingMethodService
{
    /**
     * History of method calls for testing purposes.
     *
     * @var array
     */
    public $callHistory = array();
    /**
     * @var bool
     */
    public $returnFalse = false;

    /**
     * Adds / Activates shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     *
     * @return bool TRUE if activation succeeded; otherwise, FALSE.
     */
    public function add(ShippingMethod $shippingMethod)
    {
        if ($this->returnFalse) {
            return false;
        }

        $this->callHistory['add'][] = $shippingMethod;

        return true;
    }

    /**
     * Updates shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     */
    public function update(ShippingMethod $shippingMethod)
    {
        $this->callHistory['update'][] = $shippingMethod;
    }

    /**
     * Deletes shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     *
     * @return bool TRUE if deletion succeeded; otherwise, FALSE.
     */
    public function delete(ShippingMethod $shippingMethod)
    {
        if ($this->returnFalse) {
            return false;
        }

        $this->callHistory['delete'][] = $shippingMethod;

        return true;
    }

    public function addBackupShippingMethod(ShippingMethod $shippingMethod)
    {
        if ($this->returnFalse) {
            return false;
        }

        $this->callHistory['addBackup'][] = $shippingMethod;

        return true;
    }

    public function deleteBackupShippingMethod()
    {
        if ($this->returnFalse) {
            return false;
        }

        $this->callHistory['deleteBackup'][] = 'called';

        return true;
    }
}
