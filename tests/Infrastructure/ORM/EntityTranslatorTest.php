<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use Logeecom\Infrastructure\ORM\IntermediateObject;
use Logeecom\Infrastructure\ORM\Utility\EntityTranslator;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\AsyncProcessUrlProviderInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Priority;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerConfig;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerStatus;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestAsyncProcessUrlProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;

/**
 * Class EntityTranslatorTest.
 *
 * @package Logeecom\Tests\Infrastructure\ORM
 */
class EntityTranslatorTest extends BaseInfrastructureTestWithServices
{
    /**
     * @before
     *
     * @throws \Exception
     */
    public function before()
    {
        parent::before();


        TestServiceRegister::registerService(
            AsyncProcessUrlProviderInterface::CLASS_NAME,
            function () {
                return new TestAsyncProcessUrlProvider();
            }
        );

        TestServiceRegister::registerService(
            TaskRunnerConfigInterface::CLASS_NAME,
            function () {
                $config = ServiceRegister::getService(\Logeecom\Infrastructure\Configuration\Configuration::CLASS_NAME);
                $urlProvider = ServiceRegister::getService(AsyncProcessUrlProviderInterface::CLASS_NAME);

                return new TaskRunnerConfig($config, $urlProvider);
            }
        );

    }
    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Exception
     */
    public function testTranslate()
    {
        $entity = new QueueItem();
        $entity->setStatus('created');
        $entity->setId(123);
        $entity->setCreateTimestamp(time());
        $entity->setLastUpdateTimestamp(time());
        $entity->setFailTimestamp(time());
        $entity->setFinishTimestamp(time());
        $entity->setPriority(Priority::LOW);

        $intermediate = new IntermediateObject();
        $data = $entity->toArray();
        $data['class_name'] = $entity::getClassName();
        $data = json_encode($data);
        $intermediate->setData($data);

        $translator = new EntityTranslator();
        $translator->init(QueueItem::getClassName());
        $entities = $translator->translate(array($intermediate));

        $this->assertEquals($entity, $entities[0]);
    }

    /**
     * @return void
     */
    public function testTranslateWithoutInit()
    {
        $intermediate = new IntermediateObject();
        $translator = new EntityTranslator();
        $exThrown = null;
        try {
            $translator->translate(array($intermediate));
        } catch (\Logeecom\Infrastructure\ORM\Exceptions\EntityClassException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }

    /**
     * @return void
     */
    public function testInitOnNonEntity()
    {
        $translator = new EntityTranslator();
        $exThrown = null;
        try {
            $translator->init('\Logeecom\Infrastructure\ORM\IntermediateObject');
        } catch (\Logeecom\Infrastructure\ORM\Exceptions\EntityClassException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }

    /**
     * @return void
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     */
    public function testTranslateWrongEntity()
    {
        $entity = new TaskRunnerStatus('Test', 123);

        $intermediate = new IntermediateObject();
        $intermediate->setData(Serializer::serialize($entity));

        $translator = new EntityTranslator();
        $translator->init(QueueItem::getClassName());
        $exThrown = null;
        try {
            $translator->translate(array($intermediate));
        } catch (\Logeecom\Infrastructure\ORM\Exceptions\EntityClassException $ex) {
            $exThrown = $ex;
        }

        $this->assertNotNull($exThrown);
    }
}
