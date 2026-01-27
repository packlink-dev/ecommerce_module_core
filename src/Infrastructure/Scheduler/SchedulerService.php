<?php

namespace Logeecom\Infrastructure\Scheduler;

use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\Task;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;
use Logeecom\Infrastructure\Scheduler\Models\DailySchedule;
use Logeecom\Infrastructure\Scheduler\Models\HourlySchedule;
use Logeecom\Infrastructure\Scheduler\Models\Schedule;
use Logeecom\Infrastructure\Scheduler\Models\WeeklySchedule;

class SchedulerService implements SchedulerInterface
{
    /**
     * @var Configuration
     */
    private $configService;

    public function __construct(Configuration $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @param callable $callback
     * @param int $dayOfWeek
     * @param int $hour
     * @param int $minute
     *
     * @return void
     *
     * @throws RepositoryNotRegisteredException
     */
    public function scheduleWeekly(callable $callback, int $dayOfWeek, int $hour, int $minute)
    {
        $task = $this->createTask($callback);
        $schedule = new WeeklySchedule(
            $task,
            $this->configService->getDefaultQueueName(),
            $this->configService->getContext()
        );

        $schedule->setDay($dayOfWeek);
        $schedule->setHour($hour);
        $schedule->setMinute($minute);
        $schedule->setNextSchedule();

        $this->saveSchedule($schedule);
    }

    /**
     * @param callable $callback
     * @param int $dayOfWeek
     * @param int $hour
     * @param int $minute
     *
     * @return void
     *
     * @throws RepositoryNotRegisteredException
     */
    public function scheduleDaily(callable $callback, int $dayOfWeek, int $hour, int $minute)
    {
        $task = $this->createTask($callback);
        $schedule = new DailySchedule(
            $task,
            $this->configService->getDefaultQueueName(),
            $this->configService->getContext()
        );

        $schedule->setDaysOfWeek(array($dayOfWeek));
        $schedule->setHour($hour);
        $schedule->setMinute($minute);
        $schedule->setNextSchedule();

        $this->saveSchedule($schedule);
    }

    /**
     * @param callable $callback
     * @param int $dayOfWeek
     * @param int $hour
     * @param int $minute
     *
     * @return void
     *
     * @throws RepositoryNotRegisteredException
     */
    public function scheduleHourly(callable $callback, int $dayOfWeek, int $hour, int $minute)
    {
        $task = $this->createTask($callback);
        $schedule = new HourlySchedule(
            $task,
            $this->configService->getDefaultQueueName(),
            $this->configService->getContext()
        );

        $schedule->setDay($dayOfWeek);
        $schedule->setMinute($minute);
        $schedule->setStartHour($hour);
        $schedule->setStartMinute($minute);
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
