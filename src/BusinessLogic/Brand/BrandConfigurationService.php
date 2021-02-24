<?php


namespace Packlink\BusinessLogic\Brand;

use Packlink\BusinessLogic\Brand\DTO\BrandConfiguration;

/**
 * Interface BrandConfigurationService
 *
 * @package Packlink\BusinessLogic\Brand
 */
interface BrandConfigurationService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves BrandConfiguration.
     *
     * @return BrandConfiguration
     */
    public function get();
}
