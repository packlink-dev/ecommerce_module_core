<?php

namespace Packlink\DemoUI\Services\BusinessLogic;

use Packlink\BusinessLogic\Customs\CustomsMappingService as BaseCustomsService;
use Packlink\BusinessLogic\Customs\Models\MappingFieldOptions;

class CustomsMappingService extends BaseCustomsService
{

    /**
     * @inheritDoc
     */
    public function getMappingFieldsOptions()
    {
        return array(
            MappingFieldOptions::fromArray(
                array(
                    'field' => 'mapping_receiver_tax_id',
                    'label' => 'Customer tax ID field',
                    'options' => array(
                        array('value' => 'tax_id_1', 'name' => 'Tax ID 1'),
                        array('value' => 'tax_id_2', 'name' => 'Tax ID 2'),
                        array('value' => 'tax_id_3', 'name' => 'Tax ID 3'),
                    ),
                )
            ),
            MappingFieldOptions::fromArray(
                array(
                    'field' => 'mapping_tariff_number',
                    'label' => 'Product HS code field',
                    'options' => array(
                        array('value' => 'hs_code_1', 'name' => 'HS Code 1'),
                        array('value' => 'hs_code_2', 'name' => 'HS Code 2'),
                    ),
                )
            ),
            MappingFieldOptions::fromArray(
                array(
                    'field' => 'mapping_country_of_origin',
                    'label' => 'Product country of origin field',
                    'options' => array(
                        array('value' => 'origin_1', 'name' => 'Country of origin 1'),
                        array('value' => 'origin_2', 'name' => 'Country of origin 2'),
                    ),
                )
            ),
            MappingFieldOptions::fromArray(
                array(
                    'field' => 'mapping_company_vat',
                    'label' => 'Company VAT field',
                    'options' => array(
                        array('value' => 'vat_1', 'name' => 'Company VAT 1'),
                        array('value' => 'vat_2', 'name' => 'Company VAT 2'),
                    ),
                )
            ),
        );
    }
}