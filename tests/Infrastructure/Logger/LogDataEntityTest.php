<?php

namespace Logeecom\Tests\Infrastructure\Logger;

use Logeecom\Infrastructure\Logger\LogData;
use PHPUnit\Framework\TestCase;

/**
 * Class LogDataEntityTest.
 *
 * @package Logeecom\Tests\Infrastructure\Logger
 */
class LogDataEntityTest extends TestCase
{
    public function testToArray()
    {
        $entity = new LogData(
            'test integration',
            2,
            time(),
            'Test',
            'test message',
            array('first key' => 'first value', 'second key' => 'second value')
        );
        $entity->setId(1234);

        $this->assertProperties($entity->toArray(), $entity);
    }

    public function testFromArray()
    {
        $data = array(
            'id' => 1234,
            'integration' => 'test integration',
            'logLevel' => 2,
            'timestamp' => time(),
            'component' => 'Test',
            'message' => 'Test message',
            'context' => array('first key' => 'first value', 'second key' => 'second value'),
        );

        $this->assertProperties($data, LogData::fromArray($data));
    }

    private function assertProperties($expected, LogData $entity)
    {
        self::assertEquals($expected['id'], $entity->getId());
        self::assertEquals($expected['integration'], $entity->getIntegration());
        self::assertEquals($expected['logLevel'], $entity->getLogLevel());
        self::assertEquals($expected['timestamp'], $entity->getTimestamp());
        self::assertEquals($expected['component'], $entity->getComponent());
        self::assertEquals($expected['message'], $entity->getMessage());

        if (isset($expected['context'])) {
            self::assertCount(count($expected['context']), $entity->getContext());
            $context = $entity->getContext();
            foreach ($context as $item) {
                self::assertTrue(array_key_exists($item->getName(), $expected['context']));
                self::assertEquals($item->getValue(), $expected['context'][$item->getName()]);
            }
        }
    }
}
