<?php

namespace Packlink\BusinessLogic\Customs\Interfaces;

use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\Customs\Models\MappingFieldOptions;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;

interface CustomsMappingServiceInterface
{

    /**
     * Updates customs mapping.
     *
     * @param array $data
     *
     * @return void
     *
     * @throws FrontDtoValidationException
     * @throws FrontDtoNotRegisteredException
     */
    public function updateCustomsMapping(array $data);

    /**
     * @return CustomsMapping|null
     */
    public function getCustomsMappings();

    /**
     * Returns the data-mapping field definitions (label + selectable options)
     * that the customs settings page should render. The set of fields is
     * entirely platform-driven (e.g. customer tax id, product HS code,
     * company VAT where the platform supports it).
     *
     * @return MappingFieldOptions[]
     */
    public function getMappingFieldsOptions();

}