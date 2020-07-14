<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestRegistrationInfoService;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\RegistrationController;
use Packlink\BusinessLogic\Registration\RegistrationInfoService;

/**
 * Class RegistrationControllerTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Controllers
 */
class RegistrationControllerTest extends BaseTestWithServices
{
    /**
     * @var RegistrationController
     */
    public $registrationController;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());

        TestServiceRegister::registerService(
            RegistrationInfoService::CLASS_NAME,
            function () {
                return new TestRegistrationInfoService();
            }
        );

        $this->registrationController = new RegistrationController();
    }

    public function testGetRegisterData()
    {
        $data = $this->registrationController->getRegisterData();

        $this->assertEquals('test@test.com', $data['email']);
        $this->assertEquals('1111111111111', $data['phone']);
        $this->assertEquals('localhost:7000', $data['source']);
    }
}
