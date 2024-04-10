<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\CustomsController as BaseCustomsController;
use Packlink\BusinessLogic\Country\CountryCodes;
use Packlink\DemoUI\Controllers\Models\Request;
use Packlink\DemoUI\Services\BusinessLogic\CustomsMappingService;

class CustomsController extends BaseHttpController
{
    /**
     * @var BaseCustomsController
     */
    private $baseController;

    public function __construct()
    {
        $this->baseController = new BaseCustomsController(new CustomsMappingService());
    }

    public function getData()
    {
        $result = $this->baseController->getData();

        return $this->output($result ? $result->toArray() : []);
    }

    public function getAllCountries()
    {
        return $this->output(CountryCodes::$countryCodes);
    }

    public function getCustomData()
    {
        return $this->output($this->baseController->getReceiverTaxIdOptions());
    }

    public function save(Request $request)
    {
        $this->baseController->save($request->getPayload());
    }
}
