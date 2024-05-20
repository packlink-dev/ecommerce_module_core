<?php

namespace Packlink\BusinessLogic\Customs;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class TaxIdOption
 *
 * @package Packlink\BusinessLogic\Customs
 */
class TaxIdOption extends DataTransferObject
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * @var string
     */
    public $value;
    /**
     * @var string
     */
    public $name;

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $option = new self();
        $option->value = static::getDataValue($data, 'value');
        $option->name = static::getDataValue($data, 'name');
        
        return $option;
    }
    
    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'value' => $this->value,
            'name'  => $this->name,
        );
    }
}