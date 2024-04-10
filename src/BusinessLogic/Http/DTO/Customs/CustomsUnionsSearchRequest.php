<?php

namespace Packlink\BusinessLogic\Http\DTO\Customs;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class CustomsUnionsSearchRequest
 *
 * @package Packlink\BusinessLogic\Http\DTO\Customs
 */
class CustomsUnionsSearchRequest extends DataTransferObject
{
    /**
     * @var string
     */
    public $fromPostalCode;
    /**
     * @var string
     */
    public $fromCountryCode;
    /**
     * @var string
     */
    public $toPostalCode;
    /**
     * @var string
     */
    public $toCountryCode;

    /**
     * @param array $data
     *
     * @return DataTransferObject|static
     */
    public static function fromArray(array $data)
    {
        $result = new static();

        $from = static::getDataValue($data, 'from', array());
        $result->fromPostalCode = static::getDataValue($from, 'postal_code');
        $result->fromCountryCode = static::getDataValue($from, 'country_code');
        $to = static::getDataValue($data, 'to', array());
        $result->toPostalCode = static::getDataValue($to, 'postal_code');
        $result->toCountryCode = static::getDataValue($to, 'country_code');

        return $result;
    }

    /**
     * @return array[]
     */
    public function toArray()
    {
        return array(
            'from' => array(
                'postal_code' => $this->fromPostalCode,
                'country_code' => $this->fromCountryCode,
            ),
            'to' => array(
                'postal_code' => $this->toPostalCode,
                'country_code' => $this->toCountryCode,
            )
        );
    }
}
