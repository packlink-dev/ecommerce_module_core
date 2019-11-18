<?php

namespace Logeecom\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use Logeecom\Infrastructure\TaskExecution\TaskEvents\AliveAnnouncedTaskEvent;
use Logeecom\Infrastructure\TaskExecution\TaskEvents\TaskProgressEvent;
use Logeecom\Infrastructure\Utility\TimeProvider;

/**
 * Class QueueItem
 *
 * @package Logeecom\Infrastructure\TaskExecution
 */
class QueueItem extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    const CREATED = 'created';
    const QUEUED = 'queued';
    const IN_PROGRESS = 'in_progress';
    const COMPLETED = 'completed';
    const FAILED = 'failed';
    /**
     * Array of simple field names.
     *
     * @var array
     */
    protected $fields = array(
        'id',
        'status',
        'context',
        'serializedTask',
        'queueName',
        'lastExecutionProgressBasePoints',
        'progressBasePoints',
        'retries',
        'failureDescription',
        'createTime',
        'startTime',
        'finishTime',
        'failTime',
        'earliestStartTime',
        'queueTime',
        'lastUpdateTime',
    );
    /**
     * Queue item status.
     *
     * @var string
     */
    protected $status;
    /**
     * Task associated to queue item.
     *
     * @var Task
     */
    protected $task;
    /**
     * Context in which task will be executed.
     *
     * @var string
     */
    protected $context;
    /**
     * String representation of task.
     *
     * @var string
     */
    protected $serializedTask;
    /**
     * Integration queue name.
     *
     * @var string
     */
    protected $queueName;
    /**
     * Last execution progress base points (integer value of 0.01%).
     *
     * @var int $lastExecutionProgressBasePoints
     */
    protected $lastExecutionProgressBasePoints;
    /**
     * Current execution progress in base points (integer value of 0.01%).
     *
     * @var int $progressBasePoints
     */
    protected $progressBasePoints;
    /**
     * Number of attempts to execute task.
     *
     * @var int
     */
    protected $retries;
    /**
     * Description of failure when task fails.
     *
     * @var string
     */
    protected $failureDescription;
    /**
     * Datetime when queue item is created.
     *
     * @var \DateTime
     */
    protected $createTime;
    /**
     * Datetime when queue item is started.
     *
     * @var \DateTime
     */
    protected $startTime;
    /**
     * Datetime when queue item is finished.
     *
     * @var \DateTime
     */
    protected $finishTime;
    /**
     * Datetime when queue item is failed.
     *
     * @var \DateTime
     */
    protected $failTime;
    /**
     * Min datetime when queue item can start.
     *
     * @var \DateTime
     */
    protected $earliestStartTime;
    /**
     * Datetime when queue item is enqueued.
     *
     * @var \DateTime
     */
    protected $queueTime;
    /**
     * Datetime when queue item is last updated.
     *
     * @var \DateTime
     */
    protected $lastUpdateTime;
    /**
     * Instance of time provider.
     *
     * @var TimeProvider
     */
    private $timeProvider;

    /**
     * QueueItem constructor.
     *
     * @param Task|null $task Associated task object.
     * @param string $context Context in which task will be executed.
     */
    public function __construct(Task $task = null, $context = '')
    {
        $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        $this->task = $task;
        $this->context = $context;
        $this->status = self::CREATED;
        $this->lastExecutionProgressBasePoints = 0;
        $this->progressBasePoints = 0;
        $this->retries = 0;
        $this->failureDescription = '';
        $this->createTime = $this->timeProvider->getCurrentLocalTime();

        $this->attachTaskEventHandlers();
    }

    /**
     * Sets queue item id.
     *
     * @param int $id Queue item id.
     */
    public function setId($id)
    {
        parent::setId($id);

        if ($this->task !== null) {
            $this->task->setExecutionId($id);
        }
    }

    /**
     * Returns queueTime.
     *
     * @return \DateTime queueTime Queue date and time.
     */
    public function getQueueTime()
    {
        return $this->queueTime;
    }

    /**
     * Returns lastUpdateTime.
     *
     * @return \DateTime lastUpdateTime Date and time of last update.
     */
    public function getLastUpdateTime()
    {
        return $this->lastUpdateTime;
    }

    /**
     * Gets queue item status.
     *
     * @return string Queue item status.
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets queue item status.
     *
     * @param string $status Queue item status.
     *  One of: QueueItem::CREATED, QueueItem::QUEUED, QueueItem::IN_PROGRESS, QueueItem::COMPLETED or QueueItem::FAILED
     */
    public function setStatus($status)
    {
        if (!in_array(
            $status,
            array(
                self::CREATED,
                self::QUEUED,
                self::IN_PROGRESS,
                self::COMPLETED,
                self::FAILED,
            ),
            false
        )) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid QueueItem status: "%s". Status must be one of "%s", "%s", "%s", "%s" or "%s" values.',
                    $status,
                    self::CREATED,
                    self::QUEUED,
                    self::IN_PROGRESS,
                    self::COMPLETED,
                    self::FAILED
                )
            );
        }

        $this->status = $status;
    }

    /**
     * Gets queue item queue name.
     *
     * @return string Queue item queue name.
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Sets queue item queue name.
     *
     * @param string $queueName Queue item queue name.
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;
    }

    /**
     * Gets queue item last execution progress in base points as value between 0 and 10000.
     *
     * One base point is equal to 0.01%.
     * For example 23.58% is equal to 2358 base points.
     *
     * @return int Last execution progress expressed in base points.
     */
    public function getLastExecutionProgressBasePoints()
    {
        return $this->lastExecutionProgressBasePoints;
    }

    /**
     * Sets queue item last execution progress in base points, as value between 0 and 10000.
     *
     * One base point is equal to 0.01%.
     * For example 23.58% is equal to 2358 base points.
     *
     * @param int $lastExecutionProgressBasePoints Queue item last execution progress in base points.
     */
    public function setLastExecutionProgressBasePoints($lastExecutionProgressBasePoints)
    {
        if (!is_int($lastExecutionProgressBasePoints) ||
            $lastExecutionProgressBasePoints < 0 ||
            10000 < $lastExecutionProgressBasePoints) {
            throw new \InvalidArgumentException('Last execution progress percentage must be value between 0 and 100.');
        }

        $this->lastExecutionProgressBasePoints = $lastExecutionProgressBasePoints;
    }

    /**
     * Gets progress in percentage rounded to 2 decimal value.
     *
     * @return float QueueItem progress in percentage rounded to 2 decimal value.
     */
    public function getProgressFormatted()
    {
        return round($this->progressBasePoints / 100, 2);
    }

    /**
     * Gets queue item progress in base points as value between 0 and 10000.
     *
     * One base point is equal to 0.01%.
     * For example 23.58% is equal to 2358 base points.
     *
     * @return int Queue item progress percentage in base points.
     */
    public function getProgressBasePoints()
    {
        return $this->progressBasePoints;
    }

    /**
     * Sets queue item progress in base points, as value between 0 and 10000.
     *
     * One base point is equal to 0.01%.
     * For example 23.58% is equal to 2358 base points.
     *
     * @param int $progressBasePoints Queue item progress in base points.
     */
    public function setProgressBasePoints($progressBasePoints)
    {
        if (!is_int($progressBasePoints) || $progressBasePoints < 0 || 10000 < $progressBasePoints) {
            throw new \InvalidArgumentException('Progress percentage must be value between 0 and 100.');
        }

        $this->progressBasePoints = $progressBasePoints;
    }

    /**
     * Gets queue item retries count.
     *
     * @return int Queue item retries count.
     */
    public function getRetries()
    {
        return $this->retries;
    }

    /**
     * Sets queue item retries count.
     *
     * @param int $retries Queue item retries count.
     */
    public function setRetries($retries)
    {
        $this->retries = $retries;
    }

    /**
     * Gets queue item task type.
     *
     * @return string Queue item task type.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function getTaskType()
    {
        return $this->getTask()->getType();
    }

    /**
     * Gets queue item associated task or null if not set.
     *
     * @return Task Task from queue item associated task.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function getTask()
    {
        if ($this->task === null) {
            $this->task = Serializer::unserialize($this->serializedTask);
            if (empty($this->task)) {
                throw new QueueItemDeserializationException(
                    json_encode(
                        array(
                            'Message' => 'Unable to deserialize queue item task',
                            'SerializedTask' => $this->serializedTask,
                            'QueueItemId' => $this->getId(),
                        )
                    )
                );
            }

            $this->attachTaskEventHandlers();
        }

        return $this->task;
    }

    /**
     * Gets serialized queue item task.
     *
     * @return string
     *   Serialized representation of queue item task.
     */
    public function getSerializedTask()
    {
        if ($this->task === null) {
            return $this->serializedTask;
        }

        return Serializer::serialize($this->task);
    }

    /**
     * Sets serialized task representation.
     *
     * @param string $serializedTask Serialized representation of task.
     */
    public function setSerializedTask($serializedTask)
    {
        $this->serializedTask = $serializedTask;
        $this->task = null;
    }

    /**
     * Gets task execution context.
     *
     * @return string
     *   Context in which task will be executed.
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets task execution context. Context in which task will be executed.
     *
     * @param string $context Execution context.
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Gets queue item failure description.
     *
     * @return string
     *   Queue item failure description.
     */
    public function getFailureDescription()
    {
        return $this->failureDescription;
    }

    /**
     * Sets queue item failure description.
     *
     * @param string $failureDescription
     *   Queue item failure description.
     */
    public function setFailureDescription($failureDescription)
    {
        $this->failureDescription = $failureDescription;
    }

    /**
     * Gets queue item created timestamp.
     *
     * @return int|null
     *   Queue item created timestamp.
     */
    public function getCreateTimestamp()
    {
        return $this->getTimestamp($this->createTime);
    }

    /**
     * Sets queue item created timestamp.
     *
     * @param int $timestamp
     *   Sets queue item created timestamp.
     */
    public function setCreateTimestamp($timestamp)
    {
        $this->createTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item start timestamp or null if task is not started.
     *
     * @return int|null
     *   Queue item start timestamp.
     */
    public function getStartTimestamp()
    {
        return $this->getTimestamp($this->startTime);
    }

    /**
     * Sets queue item start timestamp.
     *
     * @param int $timestamp
     *   Queue item start timestamp.
     */
    public function setStartTimestamp($timestamp)
    {
        $this->startTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item finish timestamp or null if task is not finished.
     *
     * @return int|null
     *   Queue item finish timestamp.
     */
    public function getFinishTimestamp()
    {
        return $this->getTimestamp($this->finishTime);
    }

    /**
     * Sets queue item finish timestamp.
     *
     * @param int $timestamp Queue item finish timestamp.
     */
    public function setFinishTimestamp($timestamp)
    {
        $this->finishTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item fail timestamp or null if task is not failed.
     *
     * @return int|null
     *   Queue item fail timestamp.
     */
    public function getFailTimestamp()
    {
        return $this->getTimestamp($this->failTime);
    }

    /**
     * Sets queue item fail timestamp.
     *
     * @param int $timestamp Queue item fail timestamp.
     */
    public function setFailTimestamp($timestamp)
    {
        $this->failTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item earliest start timestamp or null if not set.
     *
     * @return int|null
     *   Queue item earliest start timestamp.
     */
    public function getEarliestStartTimestamp()
    {
        return $this->getTimestamp($this->earliestStartTime);
    }

    /**
     * Sets queue item earliest start timestamp.
     *
     * @param int $timestamp Queue item earliest start timestamp.
     */
    public function setEarliestStartTimestamp($timestamp)
    {
        $this->earliestStartTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item queue timestamp or null if task is not queued.
     *
     * @return int|null
     *   Queue item queue timestamp.
     */
    public function getQueueTimestamp()
    {
        return $this->getTimestamp($this->queueTime);
    }

    /**
     * Gets queue item queue timestamp.
     *
     * @param int $timestamp Queue item queue timestamp.
     */
    public function setQueueTimestamp($timestamp)
    {
        $this->queueTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item last updated timestamp or null if task was never updated.
     *
     * @return int|null
     *   Queue item last updated timestamp.
     */
    public function getLastUpdateTimestamp()
    {
        return $this->getTimestamp($this->lastUpdateTime);
    }

    /**
     * Sets queue item last updated timestamp.
     *
     * @param int $timestamp
     *   Queue item last updated timestamp.
     */
    public function setLastUpdateTimestamp($timestamp)
    {
        $this->lastUpdateTime = $this->getDateTimeFromTimestamp($timestamp);
    }

    /**
     * Gets queue item last execution progress in base points as value between 0 and 10000.
     *
     * One base point is equal to 0.01%.
     * For example 23.58% is equal to 2358 base points.
     *
     * @return int Last execution progress expressed in base points.
     */
    public function getLastExecutionProgress()
    {
        return $this->lastExecutionProgressBasePoints;
    }

    /**
     * Reconfigures underlying task.
     *
     * @throws Exceptions\QueueItemDeserializationException
     */
    public function reconfigureTask()
    {
        $task = $this->getTask();

        if ($task->canBeReconfigured()) {
            $task->reconfigure();
            $this->setRetries(0);
            Logger::logDebug('Task ' . $this->getTaskType() . ' reconfigured.');
        }
    }

    /**
     * Returns entity configuration object.
     *
     * @return EntityConfiguration Configuration object.
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();
        $indexMap->addStringIndex('status')
            ->addStringIndex('taskType')
            ->addStringIndex('queueName')
            ->addStringIndex('context')
            ->addDateTimeIndex('queueTime')
            ->addIntegerIndex('lastExecutionProgress')
            ->addIntegerIndex('lastUpdateTimestamp');

        return new EntityConfiguration($indexMap, 'QueueItem');
    }

    /**
     * Transforms entity to its array format representation.
     *
     * @return array Entity in array format.
     */
    public function toArray()
    {
        $this->serializedTask = $this->getSerializedTask();
        $result = parent::toArray();

        $result['createTime'] = $this->timeProvider->serializeDate($this->createTime);
        $result['lastUpdateTime'] = $this->timeProvider->serializeDate($this->lastUpdateTime);
        $result['queueTime'] = $this->timeProvider->serializeDate($this->queueTime);
        $result['startTime'] = $this->timeProvider->serializeDate($this->startTime);
        $result['finishTime'] = $this->timeProvider->serializeDate($this->finishTime);
        $result['failTime'] = $this->timeProvider->serializeDate($this->failTime);
        $result['earliestStartTime'] = $this->timeProvider->serializeDate($this->earliestStartTime);

        return $result;
    }

    /**
     * Sets raw array data to this entity instance properties.
     *
     * @param array $data Raw array data with keys for class fields. @see self::$fields for field names.
     */
    public function inflate(array $data)
    {
        parent::inflate($data);

        $this->createTime = $this->timeProvider->deserializeDateString($data['createTime']);
        $this->lastUpdateTime = $this->timeProvider->deserializeDateString($data['lastUpdateTime']);
        $this->queueTime = $this->timeProvider->deserializeDateString($data['queueTime']);
        $this->startTime = $this->timeProvider->deserializeDateString($data['startTime']);
        $this->finishTime = $this->timeProvider->deserializeDateString($data['finishTime']);
        $this->failTime = $this->timeProvider->deserializeDateString($data['failTime']);
        $this->earliestStartTime = $this->timeProvider->deserializeDateString($data['earliestStartTime']);
    }

    /**
     * Gets timestamp of datetime.
     *
     * @param \DateTime|null $time Datetime object.
     *
     * @return int|null
     *   Timestamp of provided datetime or null if time is not defined.
     */
    protected function getTimestamp(\DateTime $time = null)
    {
        return $time !== null ? $time->getTimestamp() : null;
    }

    /**
     * Gets @see \DateTime object from timestamp.
     *
     * @param int $timestamp Timestamp in seconds.
     *
     * @return \DateTime|null
     *  Object if successful; otherwise, null;
     */
    protected function getDateTimeFromTimestamp($timestamp)
    {
        return !empty($timestamp) ? $this->timeProvider->getDateTime($timestamp) : null;
    }

    /**
     * Attach Task event handlers.
     */
    private function attachTaskEventHandlers()
    {
        if ($this->task === null) {
            return;
        }

        $this->task->setExecutionId($this->getId());
        $self = $this;
        $this->task->when(
            TaskProgressEvent::CLASS_NAME,
            function (TaskProgressEvent $event) use ($self) {
                $queue = new QueueService();
                $queue->updateProgress($self, $event->getProgressBasePoints());
            }
        );

        $this->task->when(
            AliveAnnouncedTaskEvent::CLASS_NAME,
            function () use ($self) {
                $queue = new QueueService();
                $queue->keepAlive($self);
            }
        );
    }
}
