<?php

namespace Logeecom\Infrastructure\ORM\Entities;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\DateTimeIndex;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\IntegerIndex;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\StringIndex;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;

/**
 * Class QueueItem.
 *
 * @package Logeecom\Infrastructure\ORM\Entities
 */
class QueueItem extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * @var string
     */
    public $status;
    /**
     * @var string
     */
    public $taskType;
    /**
     * @var string
     */
    public $queueName;
    /**
     * @var int
     */
    public $progress;
    /**
     * @var int
     */
    public $lastExecutionProgress;
    /**
     * @var int
     */
    public $retries;
    /**
     * @var string
     */
    public $failureDescription;
    /**
     * @var string
     */
    public $serializedTask;
    /**
     * @var \DateTime
     */
    public $createTimestamp;
    /**
     * @var \DateTime
     */
    public $queueTimestamp;
    /**
     * @var \DateTime
     */
    public $lastUpdateTimestamp;
    /**
     * @var \DateTime
     */
    public $startTimestamp;
    /**
     * @var \DateTime
     */
    public $finishTimestamp;
    /**
     * @var \DateTime
     */
    public $failTimestamp;

    /**
     * Returns entity configuration object
     *
     * @return EntityConfiguration
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();
        $indexMap->addIndex(new StringIndex('status'))
            ->addIndex(new StringIndex('taskType'))
            ->addIndex(new StringIndex('queueName'))
            ->addIndex(new DateTimeIndex('queueTimestamp'))
            ->addIndex(new IntegerIndex('lastExecutionProgress'))
            ->addIndex(new DateTimeIndex('lastUpdateTimestamp'));

        return new EntityConfiguration($indexMap, 'QueueItem');
    }
}
