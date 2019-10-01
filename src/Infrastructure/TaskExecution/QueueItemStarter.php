<?php

namespace Logeecom\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Runnable;

/**
 * Class QueueItemStarter
 * @package Logeecom\Infrastructure\TaskExecution
 */
class QueueItemStarter implements Runnable
{
    /**
     * Id of queue item to start.
     *
     * @var int
     */
    private $queueItemId;
    /**
     * Service instance.
     *
     * @var QueueService
     */
    private $queueService;
    /**
     * Service instance.
     *
     * @var Configuration
     */
    private $configService;

    /**
     * QueueItemStarter constructor.
     *
     * @param int $queueItemId Id of queue item to start.
     */
    public function __construct($queueItemId)
    {
        $this->queueItemId = $queueItemId;
    }

    /**
     * Transforms array into an serializable object,
     *
     * @param array $array Data that is used to instantiate serializable object.
     *
     * @return \Logeecom\Infrastructure\Serializer\Interfaces\Serializable
     *      Instance of serialized object.
     */
    public static function fromArray(array $array)
    {
        return new static($array['queue_item_id']);
    }

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return array('queue_item_id' => $this->queueItemId);
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize(array($this->queueItemId));
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        list($this->queueItemId) = Serializer::unserialize($serialized);
    }

    /**
     * Starts runnable run logic.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function run()
    {
        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem $queueItem */
        $queueItem = $this->fetchItem();

        if ($queueItem === null || ($queueItem->getStatus() !== QueueItem::QUEUED)) {
            Logger::logDebug(
                'Fail to start task execution because task no longer exists or it is not in queued state anymore.',
                'Core',
                array(
                    'TaskId' => $this->getQueueItemId(),
                    'Status' => $queueItem !== null ? $queueItem->getStatus() : 'unknown',
                )
            );

            return;
        }

        try {
            $this->getConfigService()->setContext($queueItem->getContext());
            $this->getQueueService()->start($queueItem);
            $this->getQueueService()->finish($queueItem);
        } catch (\Exception $ex) {
            if (QueueItem::IN_PROGRESS === $queueItem->getStatus()) {
                $this->getQueueService()->fail($queueItem, $ex->getMessage());
            }

            $context = array(
                'TaskId' => $this->getQueueItemId(),
                'ExceptionMessage' => $ex->getMessage(),
            );
            Logger::logError('Fail to start task execution.', 'Core', $context);

            $context['ExceptionTrace'] = $ex->getTraceAsString();
            Logger::logDebug('Fail to start task execution.', 'Core', $context);
        }
    }

    /**
     * Gets id of a queue item that will be run.
     *
     * @return int Id of queue item to run.
     */
    public function getQueueItemId()
    {
        return $this->queueItemId;
    }

    /**
     * Gets Queue item.
     *
     * @return QueueItem|null Queue item if found; otherwise, null.
     */
    private function fetchItem()
    {
        $queueItem = null;

        try {
            $queueItem = $this->getQueueService()->find($this->queueItemId);
        } catch (\Exception $ex) {
            $context = array(
                'TaskId' => $this->getQueueItemId(),
                'ExceptionMessage' => $ex->getMessage(),
            );
            Logger::logError('Fail to start task execution.', 'Core', $context);

            $context['ExceptionTrace'] = $ex->getTraceAsString();
            Logger::logDebug('Fail to start task execution.', 'Core', $context);
        }

        return $queueItem;
    }

    /**
     * Gets Queue service instance.
     *
     * @return QueueService Service instance.
     */
    private function getQueueService()
    {
        if ($this->queueService === null) {
            $this->queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
        }

        return $this->queueService;
    }

    /**
     * Gets configuration service instance.
     *
     * @return Configuration Service instance.
     */
    private function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
