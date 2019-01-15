<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use Logeecom\Infrastructure\ORM\IntermediateObject;
use Logeecom\Infrastructure\ORM\Utility\EntityTranslator;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerStatus;
use PHPUnit\Framework\TestCase;

/**
 * Class EntityTranslatorTest
 * @package Logeecom\Tests\Infrastructure\ORM
 */
class EntityTranslatorTest extends TestCase
{
    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Exception
     */
    public function testTranslate()
    {
        $entity = new QueueItem();
        $entity->setStatus('created');
        $entity->id = 'TestId';
        $entity->setCreateTimestamp(time());
        $entity->setLastUpdateTimestamp(time());
        $entity->setFailTimestamp(time());
        $entity->setFinishTimestamp(time());

        $intermediate = new IntermediateObject();
        $intermediate->setData(serialize($entity));

        $translator = new EntityTranslator();
        $translator->init(QueueItem::getClassName());
        $entities = $translator->translate(array($intermediate));

        $this->assertEquals($entity, $entities[0]);
    }

    /**
     * @expectedException \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     */
    public function testTranslateWithoutInit()
    {
        $intermediate = new IntermediateObject();
        $translator = new EntityTranslator();
        $translator->translate(array($intermediate));
    }

    /**
     * @expectedException \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     */
    public function testInitOnNonEntity()
    {
        $translator = new EntityTranslator();
        $translator->init('\Logeecom\Infrastructure\ORM\IntermediateObject');
    }

    /**
     * @expectedException \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     */
    public function testTranslateWrongEntity()
    {
        $entity = new TaskRunnerStatus('Test', 123);

        $intermediate = new IntermediateObject();
        $intermediate->setData(serialize($entity));

        $translator = new EntityTranslator();
        $translator->init(QueueItem::getClassName());
        $translator->translate(array($intermediate));
    }
}
