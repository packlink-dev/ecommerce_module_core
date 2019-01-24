<?php

namespace Logeecom\Tests\Infrastructure\ORM;

/**
 * Class MemoryGenericStudentRepositoryTest.
 *
 * @package Logeecom\Tests\Infrastructure\ORM
 */
class MemoryGenericStudentRepositoryTest extends AbstractGenericStudentRepositoryTest
{
    /**
     * @return string
     */
    public function getStudentEntityRepositoryClass()
    {
        return \Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository::getClassName();
    }

    /**
     * Cleans up all storage services used by repositories
     */
    public function cleanUpStorage()
    {
    }
}
