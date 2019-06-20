<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\Utility;

use Logeecom\Infrastructure\Utility\TimeProvider;

class TestTimeProvider extends TimeProvider
{
    /** @var \DateTime */
    private $time;
    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     *
     * TestTimeProvider constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setCurrentLocalTime(new \DateTime());
    }

    /**
     * Setup time that will be returned with get method
     *
     * @param \DateTime $time
     */
    public function setCurrentLocalTime(\DateTime $time)
    {
        $this->time = $time;
    }

    /**
     * Returns time given as parameter for set method
     *
     * @return \DateTime
     * @throws \Exception
     */
    public function getCurrentLocalTime()
    {
        return new \DateTime('@' . $this->time->getTimestamp());
    }

    /**
     * @param int $sleepTime
     *
     * @throws \Exception
     */
    public function sleep($sleepTime)
    {
        $currentTime = $this->getCurrentLocalTime();
        $this->setCurrentLocalTime($currentTime->add(new \DateInterval("PT{$sleepTime}S")));
    }
}
