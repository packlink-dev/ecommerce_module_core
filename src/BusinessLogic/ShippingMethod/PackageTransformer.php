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
     * Missing package sides and weight are taken from the default parcel. The grouped package keeps the longest
     * side of each axis, and its shortest side is grown when needed so that it holds the summed package volume.
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

        $grouped = new Package();
        $volume = 0;
        foreach ($packages as $package) {
            $width = $package->width ?: $default->width;
            $height = $package->height ?: $default->height;
            $length = $package->length ?: $default->length;

            $grouped->weight += $package->weight ?: $default->weight;
            $grouped->width = max($grouped->width, $width);
            $grouped->height = max($grouped->height, $height);
            $grouped->length = max($grouped->length, $length);
            $volume += $width * $height * $length;
        }

        $this->growToVolume($grouped, $volume);

        return $grouped;
    }

    /**
     * Grows the shortest side of the package until the package holds the given volume.
     *
     * @param Package $package Package to grow.
     * @param float $volume Volume the package must hold.
     */
    private function growToVolume(Package $package, $volume)
    {
        if ($package->width * $package->height * $package->length >= $volume) {
            return;
        }

        $shortest = min($package->width, $package->height, $package->length);
        if ($package->width == $shortest) {
            $package->width = $volume / ($package->height * $package->length);
        } elseif ($package->height == $shortest) {
            $package->height = $volume / ($package->width * $package->length);
        } else {
            $package->length = $volume / ($package->width * $package->height);
        }
    }
}
