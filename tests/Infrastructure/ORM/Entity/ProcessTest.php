<?php

namespace Logeecom\Tests\Infrastructure\ORM\Entity;

use Logeecom\Infrastructure\TaskExecution\Process;

/**
 * Class ProcessTest.
 *
 * @package Logeecom\Tests\Infrastructure\ORM\Entity
 */
class ProcessTest extends GenericEntityTest
{
    /**
     * Returns entity full class name
     *
     * @return string
     */
    public function getEntityClass()
    {
        return Process::getClassName();
    }
}
