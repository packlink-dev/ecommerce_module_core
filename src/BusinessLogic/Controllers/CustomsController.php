<?php

namespace Packlink\BusinessLogic\Controllers;

use Packlink\BusinessLogic\Country\CountryCodes;
use Packlink\BusinessLogic\Customs\CustomsMappingService;
use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;

/**
 * Class CustomsController
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class CustomsController
{
    /**
     * @var CustomsMappingService
     */
    private $customService;

    /**
     * @param CustomsMappingService $customService
     */
    public function __construct(CustomsMappingService $customService)
    {
        $this->customService = $customService;
    }

    /**
     * @return CustomsMapping|null
     */
    public function getData()
    {
        return $this->customService->getCustomsMappings();
    }

    /**
     * @return array
     */
    public function getAllCountries()
    {
        return CountryCodes::$countryCodes;
    }

    /**
     * Returns the data-mapping field definitions (label + selectable options)
     * for the customs settings page. The set of fields is platform-driven.
     *
     * @return \Packlink\BusinessLogic\Customs\Models\MappingFieldOptions[]
     */
    public function getMappingFieldsOptions()
    {
        return $this->customService->getMappingFieldsOptions();
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws FrontDtoNotRegisteredException
     * @throws FrontDtoValidationException
     */
    public function save($data)
    {
        $this->customService->updateCustomsMapping($data);
    }
}
