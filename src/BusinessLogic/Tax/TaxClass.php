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
     * Unique class key.
     */
    const CLASS_KEY = 'tax_class';
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
    /**
     * Required fields for DTO to be valid.
     *
     * @var array
     */
    protected static $requiredFields = array('label', 'value');
}
