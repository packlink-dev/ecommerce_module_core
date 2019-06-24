<?php

namespace Logeecom\Infrastructure\TaskExecution\TaskEvents;

use Logeecom\Infrastructure\Utility\Events\Event;

/**
 * Class TaskProgressEvent.
 *
 * @package Logeecom\Infrastructure\TaskExecution\TaskEvents
 */
class TaskProgressEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Progress in base points.
     *
     * @var int
     */
    private $progressPercentBasePoints;

    /**
     * TaskProgressEvent constructor.
     *
     * @param int $progressPercentBasePoints Integer representation of progress percentage in base points,
     *  as value between 0 and 10000. One base point is equal to 0.01%. For example 23.58% is equal to 2358 base points.
     *
     * @throws \InvalidArgumentException
     *  In case when progress percent is outside of 0 - 10000 boundaries or not an integer.
     */
    public function __construct($progressPercentBasePoints)
    {
        if (!is_int($progressPercentBasePoints)
            || $progressPercentBasePoints < 0
            || 10000 < $progressPercentBasePoints
        ) {
            throw new \InvalidArgumentException('Progress percentage must be value between 0 and 10000.');
        }

        $this->progressPercentBasePoints = $progressPercentBasePoints;
    }

    /**
     * Gets progress base points in form of integer value between 0 and 10000.
     *
     * @return int Progress base points.
     */
    public function getProgressBasePoints()
    {
        return $this->progressPercentBasePoints;
    }

    /**
     * Gets progress in percentage rounded to 2 decimals.
     *
     * @return float Progress in percentage rounded to 2 decimals.
     */
    public function getProgressFormatted()
    {
        return round($this->progressPercentBasePoints / 100, 2);
    }
}
