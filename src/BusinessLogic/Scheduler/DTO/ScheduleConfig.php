<?php

namespace Packlink\BusinessLogic\Scheduler\DTO;

class ScheduleConfig
{
    /**
     * @var string
     */
    /**
     * @var int|null
     */
    private $dayOfWeek;
    /**
     * @var int[]
     */
    private $daysOfWeek = array();
    /**
     * @var int|null
     */
    private $hour;
    /**
     * @var int|null
     */
    private $minute;
    /**
     * @var int|null
     */
    private $startHour;
    /**
     * @var int|null
     */
    private $startMinute;
    /**
     * @var int|null
     */
    private $endHour;
    /**
     * @var int|null
     */
    private $endMinute;
    /**
     * @var int|null
     */
    private $interval;
    /**
     * @var bool
     */
    private $recurring;

    public function __construct(int $dayOfWeek, int $hour, int $minute, bool $recurring = true)
    {
        $this->dayOfWeek = $dayOfWeek;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->recurring = $recurring;
    }

    public function getDayOfWeek()
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek($dayOfWeek)
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function getDaysOfWeek(): array
    {
        return $this->daysOfWeek;
    }

    public function setDaysOfWeek(array $daysOfWeek)
    {
        $this->daysOfWeek = $daysOfWeek;

        return $this;
    }

    public function getHour()
    {
        return $this->hour;
    }

    public function setHour($hour)
    {
        $this->hour = $hour;

        return $this;
    }

    public function getMinute()
    {
        return $this->minute;
    }

    public function setMinute($minute)
    {
        $this->minute = $minute;

        return $this;
    }

    public function getStartHour()
    {
        return $this->startHour;
    }

    public function setStartHour($startHour)
    {
        $this->startHour = $startHour;

        return $this;
    }

    public function getStartMinute()
    {
        return $this->startMinute;
    }

    public function setStartMinute($startMinute)
    {
        $this->startMinute = $startMinute;

        return $this;
    }

    public function getEndHour()
    {
        return $this->endHour;
    }

    public function setEndHour($endHour)
    {
        $this->endHour = $endHour;

        return $this;
    }

    public function getEndMinute()
    {
        return $this->endMinute;
    }

    public function setEndMinute($endMinute)
    {
        $this->endMinute = $endMinute;

        return $this;
    }

    public function getInterval()
    {
        return $this->interval;
    }

    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

    public function isRecurring(): bool
    {
        return $this->recurring;
    }

    public function setRecurring($recurring)
    {
        $this->recurring = (bool)$recurring;

        return $this;
    }
}
