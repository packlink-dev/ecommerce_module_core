<?php

namespace Packlink\BusinessLogic\Tax;

use Packlink\BusinessLogic\DTO\FrontDto;

/**
 * Class TaxClass.
 *
 * @package Packlink\BusinessLogic\Tax
 */
class TaxClass extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Display label.
     *
     * @var string
     */
    public $label;
    /**
     * The value for tax class.
     *
     * @var mixed
     */
    public $value;
    /**
     * Fields for this DTO. Needed for validation and transformation from/to array.
     *
     * @var array
     */
    protected static $fields = array('label', 'value');
}
