<?php

namespace Packlink\BusinessLogic\SimulatedPrices\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class SimulatedPricesSearch holds one simulated-prices request: the selected market chip, an optional
 * destination and, for a merchant without a Packlink account, an optional origin supplied by the module.
 *
 * @package Packlink\BusinessLogic\SimulatedPrices\DTO
 */
class SimulatedPricesSearch extends DataTransferObject
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Selected market chip, two-letter country code (ES, FR, DE, IT, GB).
     *
     * @var string
     */
    public $market = '';
    /**
     * Destination country, two-letter code. Empty means the market country.
     *
     * @var string
     */
    public $toCountry = '';
    /**
     * Destination postal code. Empty means the capital city of the market.
     *
     * @var string
     */
    public $toPostalCode = '';
    /**
     * Origin country of a merchant without a Packlink account. Empty means the market country.
     *
     * @var string
     */
    public $fromCountry = '';
    /**
     * Origin postal code of a merchant without a Packlink account. Empty means the capital city of the market.
     *
     * @var string
     */
    public $fromPostalCode = '';

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return array(
            'market' => $this->market,
            'toCountry' => $this->toCountry,
            'toPostalCode' => $this->toPostalCode,
            'fromCountry' => $this->fromCountry,
            'fromPostalCode' => $this->fromPostalCode,
        );
    }

    /**
     * Builds the search from raw input. Country codes are trimmed and upper-cased; postal codes are trimmed
     * and kept as strings, so leading zeroes survive.
     *
     * @param array $raw Raw input.
     *
     * @return static
     */
    public static function fromArray(array $raw)
    {
        $instance = new static();

        $instance->market = static::readCountry($raw, 'market');
        $instance->toCountry = static::readCountry($raw, 'toCountry');
        $instance->toPostalCode = static::readString($raw, 'toPostalCode');
        $instance->fromCountry = static::readCountry($raw, 'fromCountry');
        $instance->fromPostalCode = static::readString($raw, 'fromPostalCode');

        return $instance;
    }

    /**
     * @param array $raw
     * @param string $key
     *
     * @return string
     */
    private static function readCountry(array $raw, $key)
    {
        return strtoupper(static::readString($raw, $key));
    }

    /**
     * @param array $raw
     * @param string $key
     *
     * @return string
     */
    private static function readString(array $raw, $key)
    {
        $value = static::getDataValue($raw, $key, '');

        return is_scalar($value) ? trim((string)$value) : '';
    }
}
