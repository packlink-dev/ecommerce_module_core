<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use DateTime;
use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Customs\MockCustomsMappingService;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\CustomsController;
use Packlink\BusinessLogic\Country\CountryCodes;
use Packlink\BusinessLogic\Customs\CustomsMappingService;
use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\DTO\ValidationError;

class CustomsControllerTest extends BaseTestWithServices
{
    /**
     * @var CustomsController
     */
    private $customsController;

    /**
     * @inheritDoc
     */
    public function before()
    {
        parent::before();

        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());
        TestFrontDtoFactory::register(ValidationError::CLASS_KEY, ValidationError::CLASS_NAME);
        TestFrontDtoFactory::register(CustomsMapping::CLASS_KEY, CustomsMapping::CLASS_NAME);

        $queue = new TestQueueService();
        $taskRunnerStarter = new TestTaskRunnerWakeupService();
        $configuration = new TestShopConfiguration();
        $nowDateTime = new DateTime('2018-03-21T13:42:05');

        $timeProvider = new TestTimeProvider();
        $timeProvider->setCurrentLocalTime($nowDateTime);

        TestServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () use ($configuration) {
                return $configuration;
            }
        );

        TestServiceRegister::registerService(
            TimeProvider::CLASS_NAME,
            function () use ($timeProvider) {
                return $timeProvider;
            }
        );

        TestServiceRegister::registerService(
            EventBus::CLASS_NAME,
            function () {
                return EventBus::getInstance();
            }
        );

        TestServiceRegister::registerService(
            Serializer::CLASS_NAME,
            function () {
                return new NativeSerializer();
            }
        );

        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () use ($queue) {
                return $queue;
            }
        );

        TestServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () use ($taskRunnerStarter) {
                return $taskRunnerStarter;
            }
        );

        TestServiceRegister::registerService(
            CustomsMappingService::CLASS_NAME,
            function () {
                return new MockCustomsMappingService();
            }
        );

        $this->customsController = new CustomsController(
            TestServiceRegister::getService(CustomsMappingService::CLASS_NAME)
        );
    }

    public function testGetData()
    {
        // arrange
        $mapping = array (
            'default_reason' => 'PURCHASE_OR_SALE',
            'default_sender_tax_id' => '123',
            'default_receiver_tax_id' => '123',
            'default_receiver_user_type' => 'PRIVATE_PERSON',
            'default_tariff_number' => '123456',
            'default_country' => 'FR',
            'mapping_receiver_tax_id' => 'tax_1',
            'mapping_tariff_number' => 'hs_code_1',
            'mapping_company_vat' => 'vat_1',
        );
        /** @var CustomsMappingService $service */
        $service = TestServiceRegister::getService(CustomsMappingService::CLASS_NAME);
        $service->updateCustomsMapping($mapping);

        // act
        $result = $this->customsController->getData();

        // assert
        self::assertEquals($mapping, $result->toArray());
    }

    public function testSave()
    {
        // arrange
        $mapping = array (
            'default_reason' => 'PURCHASE_OR_SALE',
            'default_sender_tax_id' => '123',
            'default_receiver_tax_id' => '123',
            'default_receiver_user_type' => 'PRIVATE_PERSON',
            'default_tariff_number' => '12345678',
            'default_country' => 'FR',
            'mapping_receiver_tax_id' => 'tax_1',
            'mapping_tariff_number' => 'hs_code_1',
            'mapping_company_vat' => 'vat_1',
        );

        // act
        $this->customsController->save($mapping);

        // assert
        /** @var CustomsMappingService $service */
        $service = TestServiceRegister::getService(CustomsMappingService::CLASS_NAME);
        $result = $service->getCustomsMappings();
        self::assertEquals($mapping, $result->toArray());
    }

    public function testGetAllCountries()
    {
        // act
        $result = $this->customsController->getAllCountries();

        // assert
        self::assertEquals(CountryCodes::$countryCodes, $result);
    }

    public function testGetMappingFieldsOptions()
    {
        // act
        $result = $this->customsController->getMappingFieldsOptions();

        // assert
        self::assertCount(1, $result);
        self::assertEquals('mapping_receiver_tax_id', $result[0]->field);
        self::assertEquals(
            array(
                array('value' => 'tax_1', 'name' => 'Tax 1'),
                array('value' => 'tax_2', 'name' => 'Tax 2'),
                array('value' => 'tax_3', 'name' => 'Tax 3'),
            ),
            array_map(
                function ($option) {
                    return $option->toArray();
                },
                $result[0]->options
            )
        );
    }

    public function testSaveInvalidMappingThrows()
    {
        // arrange
        $mapping = array(
            'default_reason' => 'PURCHASE_OR_SALE',
            'default_sender_tax_id' => '123',
            'default_receiver_tax_id' => '123',
            'default_receiver_user_type' => 'PRIVATE_PERSON',
            'default_tariff_number' => '123', // invalid: fewer than 6 digits
            'default_country' => 'FR',
            'mapping_receiver_tax_id' => 'tax_1',
        );

        // act
        $exceptionThrown = null;
        try {
            $this->customsController->save($mapping);
        } catch (FrontDtoValidationException $e) {
            $exceptionThrown = $e;
        }

        // assert
        self::assertNotNull($exceptionThrown, 'Invalid mapping must raise a validation exception.');

        /** @var Configuration $configuration */
        $configuration = TestServiceRegister::getService(Configuration::CLASS_NAME);
        self::assertNull($configuration->getCustomsMappings(), 'Invalid mapping must not be persisted.');
    }
}
