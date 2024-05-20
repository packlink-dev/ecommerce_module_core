<?php

namespace Packlink\BusinessLogic\Http\DTO\Customs;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class Money
 *
 * @package Packlink\BusinessLogic\Http\DTO\Customs
 */
class Money extends DataTransferObject
{
    /**
     * @var string
     */
    public $currency;
    /**
     * @var float
     */
    public $value;

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $result = new static();

        $result->currency = static::getDataValue($data, 'currency');
        $result->value = static::getDataValue($data, 'value', 0);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'currency' => $this->currency,
            'value' => $this->value,
        );
    }
}