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

    /**
     * Adds backup shipping method based on provided shipping method.
     *
     * @param ShippingMethod $shippingMethod
     *
     * @return bool TRUE if backup shipping method is added; otherwise, FALSE.
     */
    public function addBackupShippingMethod(ShippingMethod $shippingMethod)
    {
        if ($this->returnFalse) {
            return false;
        }

        $this->callHistory['addBackup'][] = $shippingMethod;

        return true;
    }

    /**
     * Deletes backup shipping method.
     *
     * @return bool TRUE if backup shipping method is deleted; otherwise, FALSE.
     */
    public function deleteBackupShippingMethod()
    {
        if ($this->returnFalse) {
            return false;
        }

        $this->callHistory['deleteBackup'][] = 'called';

        return true;
    }

    /**
     * Gets the carrier logo path based on carrier name.
     *
     * @param string $carrierName
     *
     * @return string
     */
    public function getCarrierLogoFilePath($carrierName)
    {
        return 'tmp://' . $carrierName;
    }

    /**
     * Disables shop shipping services/carriers.
     *
     * @return boolean TRUE if operation succeeded; otherwise, false.
     */
    public function disableShopServices()
    {
        return true;
    }
}
