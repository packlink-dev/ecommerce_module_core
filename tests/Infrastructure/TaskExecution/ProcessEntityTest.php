<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\Process;
use Logeecom\Infrastructure\TaskExecution\QueueItemStarter;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;

/**
 * Class ProcessEntityTest.
 *
 * @package Logeecom\Tests\Infrastructure\TaskExecution
 */
class ProcessEntityTest extends BaseInfrastructureTestWithServices
{
    public function testToArray()
    {
        $runner = new QueueItemStarter(1234);
        $entity = new Process();
        $entity->setId(1234);
        $entity->setGuid('test');
        $entity->setRunner($runner);

        $data = $entity->toArray();

        self::assertEquals($data['id'], $entity->getId());
        self::assertEquals($data['guid'], $entity->getGuid());
        self::assertEquals($data['runner'], Serializer::serialize($entity->getRunner()));
    }

    public function testFromArrayAndToJSON()
    {
        $runner = new QueueItemStarter(1234);
        $data = array(
            'class_name' => Process::CLASS_NAME,
            'id' => 123,
            'guid' => 'guid',
            'runner' => serialize($runner),
        );

        $entity = Process::fromArray($data);

        self::assertEquals($entity->getId(), $data['id']);
        self::assertEquals($entity->getGuid(), $data['guid']);
        self::assertEquals($entity->getRunner(), $runner);

        self::assertEquals(json_encode($data), json_encode($entity->toArray()));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFromArrayInvalidGuid()
    {
        $runner = new QueueItemStarter(1234);
        $data = array(
            'id' => 123,
            'runner' => serialize($runner),
        );

        Process::fromArray($data);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFromArrayInvalidRunner()
    {
        $data = array(
            'id' => 123,
            'guid' => 'test',
        );

        Process::fromArray($data);
    }
}
