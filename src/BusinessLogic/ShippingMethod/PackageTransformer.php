<?php

namespace Packlink\BusinessLogic\ShippingMethod;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\Singleton;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;

/**
 * Class PackageTransformer
 *
 * @package Packlink\BusinessLogic\ShippingMethod
 */
class PackageTransformer extends Singleton
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * Configuration service.
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * OrderService constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Transforms array of packages into one grouped package.
     *
     * @param Package[] $packages Array of packages.
     *
     * @return Package Grouped packages.
     */
    public function transform(array $packages = array())
    {
        $default = $this->configuration->getDefaultParcel() ?: ParcelInfo::defaultParcel();
        if (empty($packages)) {
            return new Package($default->weight, $default->width, $default->height, $default->length);
        }

        $count = count($packages);
        $width = $count === 1 && $packages[0]->width ? $packages[0]->width : $default->width;
        $length = $count === 1 && $packages[0]->length ? $packages[0]->length : $default->length;
        $height = $count === 1 && $packages[0]->height ? $packages[0]->height : $default->height;

        $weight = 0;
        foreach ($packages as $package) {
            $weight += $package->weight ?: $default->weight;
        }

        return new Package($weight, $width, $height, $length);
    }
}
