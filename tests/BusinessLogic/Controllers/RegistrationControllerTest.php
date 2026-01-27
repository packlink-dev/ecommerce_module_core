<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\HttpTaskExecutor;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestRegistrationInfoService;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\RegistrationController;
use Packlink\BusinessLogic\Registration\RegistrationInfoService;
use Packlink\BusinessLogic\Tasks\DefaultTaskMetadataProvider;

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
     * @before
     * @inheritdoc
     */
    public function before()
    {
        parent::before();

        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());

        TestServiceRegister::registerService(
            RegistrationInfoService::CLASS_NAME,
            function () {
                return new TestRegistrationInfoService();
            }
        );

        $metadataProvider = new DefaultTaskMetadataProvider($this->shopConfig);
        $taskExecutor = new HttpTaskExecutor(
            TestServiceRegister::getService(\Logeecom\Infrastructure\TaskExecution\QueueService::CLASS_NAME),
            $metadataProvider,
            $this->shopConfig,
            EventBus::getInstance(),
            ServiceRegister::getService(TimeProvider::CLASS_NAME)
        );
        $this->registrationController = new RegistrationController($taskExecutor);
    }

    public function testGetRegisterData()
    {
        $data = $this->registrationController->getRegisterData('FR');

        $this->assertEquals('test@test.com', $data['email']);
        $this->assertEquals('1111111111111', $data['phone']);
        $this->assertEquals('localhost:7000', $data['source']);
        $this->assertEquals('FR', $data['platform_country']);
    }
}
