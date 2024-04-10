<?php

namespace Packlink\BusinessLogic\Http\DTO\Customs;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class Signature
 *
 * @package Packlink\BusinessLogic\Http\DTO\Customs
 */
class Signature extends DataTransferObject
{
    /**
     * @var string
     */
    public $fullName;
    /**
     * @var string
     */
    public $city;

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $result = new static();

        $result->fullName = static::getDataValue($data, 'full_name');
        $result->city = static::getDataValue($data, 'city');

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'full_name' => $this->fullName,
            'city' => $this->city,
        );
    }
}
