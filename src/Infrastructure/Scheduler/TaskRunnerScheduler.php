<?php

namespace Logeecom\Infrastructure\Scheduler;

use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerConfig;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Scheduler\DTO\ScheduleConfig;
use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;
use Logeecom\Infrastructure\Scheduler\Models\DailySchedule;
use Logeecom\Infrastructure\Scheduler\Models\HourlySchedule;
use Logeecom\Infrastructure\Scheduler\Models\Schedule;
use Logeecom\Infrastructure\Scheduler\Models\WeeklySchedule;

class TaskRunnerScheduler implements SchedulerInterface
{
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * @var TaskRunnerConfigInterface $taskRunnerConfig
     */
    private $taskRunnerConfig;

    public function __construct(Configuration $configService, TaskRunnerConfigInterface $taskRunnerConfig)
    {
        $this->configService = $configService;
        $this->taskRunnerConfig = $taskRunnerConfig;
    }

    /**
     * @param callable $callback
     * @param ScheduleConfig $config
     *
     * @return void
     *
     * @throws RepositoryNotRegisteredException
     */
    public function scheduleWeekly(callable $callback, ScheduleConfig $config)
    {
        $task = $this->createTask($callback);
        $schedule = new WeeklySchedule(
            $task,
            $this->taskRunnerConfig->getDefaultQueueName(),
            $this->configService->getContext()
        );

        $schedule->setDay($config->getDayOfWeek());
        $schedule->setHour($config->getHour());
        $schedule->setMinute($config->getMinute());
        $schedule->setRecurring($config->isRecurring());
        $schedule->setNextSchedule();

        $this->saveSchedule($schedule);
    }

    /**
     * @param callable $callback
     * @param ScheduleConfig $config
     *
     * @return void
     *
     * @throws RepositoryNotRegisteredException
     */
    public function scheduleDaily(callable $callback, ScheduleConfig $config)
    {
        $task = $this->createTask($callback);
        $schedule = new DailySchedule(
            $task,
            $this->taskRunnerConfig->getDefaultQueueName(),
            $this->configService->getContext()
        );

        $daysOfWeek = $config->getDaysOfWeek();
        if (empty($daysOfWeek) && $config->getDayOfWeek() !== null) {
            $daysOfWeek = array($config->getDayOfWeek());
        }

        if (!empty($daysOfWeek)) {
            $schedule->setDaysOfWeek($daysOfWeek);
        }

        $schedule->setHour($config->getHour());
        $schedule->setMinute($config->getMinute());
        $schedule->setRecurring($config->isRecurring());
        $schedule->setNextSchedule();

        $this->saveSchedule($schedule);
    }

    /**
     * @param callable $callback
     * @param ScheduleConfig $config
     *
     * @return void
     *
     * @throws RepositoryNotRegisteredException
     */
    public function scheduleHourly(callable $callback, ScheduleConfig $config)
    {
        $task = $this->createTask($callback);
        $schedule = new HourlySchedule(
            $task,
            $this->taskRunnerConfig->getDefaultQueueName(),
            $this->configService->getContext()
        );

        $schedule->setDay($config->getDayOfWeek());
        if ($config->getMinute() !== null) {
            $schedule->setMinute($config->getMinute());
        }

        $startHour = $config->getStartHour() !== null ? $config->getStartHour() : $config->getHour();
        $startMinute = $config->getStartMinute() !== null ? $config->getStartMinute() : $config->getMinute();
        if ($startHour !== null) {
            $schedule->setStartHour($startHour);
        }
        if ($startMinute !== null) {
            $schedule->setStartMinute($startMinute);
        }
        if ($config->getEndHour() !== null) {
            $schedule->setEndHour($config->getEndHour());
        }
        if ($config->getEndMinute() !== null) {
            $schedule->setEndMinute($config->getEndMinute());
        }
        if ($config->getInterval() !== null) {
            $schedule->setInterval($config->getInterval());
        }
        $schedule->setRecurring($config->isRecurring());
        $schedule->setNextSchedule();

        $this->saveSchedule($schedule);
    }

    /**
     * @param callable $callback
     *
     * @return Task
     */
    private function createTask(callable $callback)
    {
        $task = call_user_func($callback);

        if (!$task instanceof Task) {
            throw new \InvalidArgumentException('Scheduler callback must return an instance of Task.');
        }

        return $task;
    }

    /**
     * @param Schedule $schedule
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function saveSchedule(Schedule $schedule)
    {
        RepositoryRegistry::getRepository(Schedule::CLASS_NAME)->save($schedule);
    }

}
