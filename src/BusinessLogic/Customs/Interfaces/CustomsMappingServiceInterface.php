<?php

namespace Packlink\BusinessLogic\Customs\Interfaces;

use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\Customs\Models\TaxIdOption;
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
     * @return TaxIdOption[]
     */
    public function getReceiverTaxIdOptions();

}