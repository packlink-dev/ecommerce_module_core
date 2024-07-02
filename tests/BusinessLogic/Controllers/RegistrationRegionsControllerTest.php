<?php

namespace BusinessLogic\Controllers;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Registration\MockCountryService;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\RegistrationRegionsController;
use Packlink\BusinessLogic\Country\CountryService;

class RegistrationRegionsControllerTest extends  BaseTestWithServices
{
    public $service;
    public $controller;

    /**
     * @before
     * @inheritDoc
     */
    protected function before()
    {
        parent::before();

        $this->service = MockCountryService::getInstance();

        $me = $this;

        TestServiceRegister::registerService(
            CountryService::CLASS_NAME,
            function () use ($me) {
                return $me->service;
            }
        );

        $this->controller = new RegistrationRegionsController();
    }

    public function testGetRegionsMethodCalls()
    {
        // arrange
        $expected = array(array('getSupportedCountries' => array(false)));

        // act
        $this->controller->getRegions();

        // assert
        $this->assertEquals($expected, $this->service->callHistory);
    }

    public function testGetRegionsResult()
    {
        // arrange
        MockCountryService::$supportedCountries = array('t1', 't2');

        // act
        $result = $this->controller->getRegions();

        // assert
        $this->assertEquals(MockCountryService::$supportedCountries, $result);
    }

    /**
     * @after
     * @inheritDoc
     */
    protected function after()
    {
        MockCountryService::resetInstance();
    }
}
