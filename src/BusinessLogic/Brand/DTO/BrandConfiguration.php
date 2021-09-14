<?php


namespace Packlink\BusinessLogic\Brand\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class BrandConfiguration
 *
 * @package Packlink\BusinessLogic\Brand\DTO
 */
class BrandConfiguration extends DataTransferObject
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Platform code.
     *
     * @var string
     */
    public $platformCode;
    /**
     * Shipping service source.
     *
     * @var string
     */
    public $shippingServiceSource;
    /**
     * Platform countries.
     *
     * @var array
     */
    public $platformCountries;
    /**
     * Registration countries.
     *
     * @var array
     */
    public $registrationCountries;
    /**
     * List of available warehouse countries.
     *
     * @var array
     */
    public $warehouseCountries;

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'platform_code' => $this->platformCode,
            'shipping_service_source' => $this->shippingServiceSource,
            'platform_countries' => $this->platformCountries,
            'registration_countries' => $this->registrationCountries,
            'warehouse_countries' => $this->warehouseCountries,
        );
    }

    /**
     * Creates BrandConfiguration object from array.
     *
     * @param array $data
     *
     * @return BrandConfiguration
     */
    public static function fromArray(array $data)
    {
        $result = new static();

        $result->platformCode = $data['platform_code'];
        $result->shippingServiceSource = $data['shipping_service_source'];

        if (isset($data['platform_countries']) && is_array($data['platform_countries'])) {
            $result->platformCountries = $data['platform_countries'];
        } else {
            $result->platformCountries = array();
        }

        if (isset($data['registration_countries']) && is_array($data['registration_countries'])) {
            $result->registrationCountries = $data['registration_countries'];
        } else {
            $result->registrationCountries = array();
        }

        if (isset($data['warehouse_countries']) && is_array($data['warehouse_countries'])) {
            $result->warehouseCountries = $data['warehouse_countries'];
        } else {
            $result->warehouseCountries = array();
        }

        return $result;
    }
}
