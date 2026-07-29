<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Customs;

use Packlink\BusinessLogic\Customs\Models\MappingFieldOptions;
use Packlink\BusinessLogic\Customs\Models\TaxIdOption;

class MockCustomsMappingService extends \Packlink\BusinessLogic\Customs\CustomsMappingService
{

    /**
     * @inheritDoc
     */
    public function getMappingFieldsOptions()
    {
        $receiverTaxId = new MappingFieldOptions();
        $receiverTaxId->field = 'mapping_receiver_tax_id';
        $receiverTaxId->label = 'Customer tax ID field';
        $receiverTaxId->options = array(
            $this->option('tax_1', 'Tax 1'),
            $this->option('tax_2', 'Tax 2'),
            $this->option('tax_3', 'Tax 3'),
        );

        return array($receiverTaxId);
    }

    /**
     * @param string $value
     * @param string $name
     *
     * @return TaxIdOption
     */
    private function option($value, $name)
    {
        $option = new TaxIdOption();
        $option->value = $value;
        $option->name = $name;

        return $option;
    }
}