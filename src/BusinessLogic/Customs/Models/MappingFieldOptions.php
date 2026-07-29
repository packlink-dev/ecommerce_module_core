<?php

namespace Packlink\BusinessLogic\Customs\Models;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class MappingFieldOptions
 *
 * Describes a single platform-supplied data-mapping field for the customs
 * settings page: which CustomsMapping field it targets, its display label,
 * and the list of selectable options (e.g. product or customer attributes).
 * The set of fields, their labels and their options are entirely
 * platform-driven; core neither hardcodes nor assumes any of them.
 *
 * @package Packlink\BusinessLogic\Customs
 */
class MappingFieldOptions extends DataTransferObject
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * The CustomsMapping field this option list targets (e.g. "mapping_tariff_number").
     *
     * @var string
     */
    public $field;
    /**
     * Display label for the mapping select.
     *
     * @var string
     */
    public $label;
    /**
     * @var TaxIdOption[]
     */
    public $options = array();

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $fieldOptions = new self();
        $fieldOptions->field = static::getDataValue($data, 'field');
        $fieldOptions->label = static::getDataValue($data, 'label');

        foreach (static::getDataValue($data, 'options', array()) as $option) {
            $fieldOptions->options[] = TaxIdOption::fromArray($option);
        }

        return $fieldOptions;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $options = array();
        foreach ($this->options as $option) {
            $options[] = $option->toArray();
        }

        return array(
            'field' => $this->field,
            'label' => $this->label,
            'options' => $options,
        );
    }
}
