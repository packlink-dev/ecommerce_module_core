<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use Logeecom\Infrastructure\ORM\Entities\Process;
use Logeecom\Infrastructure\ORM\Entities\QueueItem;
use Logeecom\Infrastructure\ORM\IntermediateObject;
use Logeecom\Infrastructure\ORM\Utility\EntityTranslator;
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
        $entity->status = 'TestStatus';
        $entity->id = 'TestId';
        $entity->createTimestamp = new \DateTime();
        $entity->lastUpdateTimestamp = new \DateTime();
        $entity->failTimestamp = new \DateTime();
        $entity->finishTimestamp = new \DateTime();

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
        $entity = new Process();
        $entity->runner = 'TEST';

        $intermediate = new IntermediateObject();
        $intermediate->setData(serialize($entity));

        $translator = new EntityTranslator();
        $translator->init(QueueItem::getClassName());
        $translator->translate(array($intermediate));
    }
}
