<?php

namespace Packlink\DemoUI\Services\BusinessLogic;

use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\DemoUI\Services\Integration\UrlService;

/**
 * Class CarrierService
 *
 * @package Packlink\DemoUI\Services\BusinessLogic
 */
class CarrierService implements ShopShippingMethodService
{
    /**
     * Returns carrier logo file path of shipping method with provided ID.
     * If logo doesn't exist returns default carrier logo.
     *
     * @param string $carrierName Name of the carrier.
     *
     * @return string Logo file path.
     */
    public function getCarrierLogoFilePath($carrierName)
    {
        $image = str_replace(' ', '-', mb_strtolower($carrierName));

        return UrlService::getResourceUrl("images/carriers/{$image}.png");
    }

    /**
     * Adds / Activates shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     *
     * @return bool TRUE if activation succeeded; otherwise, FALSE.
     */
    public function add(ShippingMethod $shippingMethod)
    {
        return true;
    }

    /**
     * Updates shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     */
    public function update(ShippingMethod $shippingMethod)
    {
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
        return true;
    }

    /**
     * Deletes backup shipping method.
     *
     * @return bool TRUE if backup shipping method is deleted; otherwise, FALSE.
     */
    public function deleteBackupShippingMethod()
    {
        return true;
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
