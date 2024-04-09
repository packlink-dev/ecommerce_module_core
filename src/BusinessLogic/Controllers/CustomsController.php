<?php

namespace Packlink\BusinessLogic\Controllers;

use Packlink\BusinessLogic\Country\CountryCodes;
use Packlink\BusinessLogic\Customs\CustomsMapping;
use Packlink\BusinessLogic\Customs\CustomsService;
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
     * @var CustomsService
     */
    private $customService;

    /**
     * @param CustomsService $customService
     */
    public function __construct(CustomsService $customService)
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
     * @return array
     */
    public function getReceiverTaxIdOptions()
    {
        return $this->customService->getReceiverTaxIdOptions();
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
