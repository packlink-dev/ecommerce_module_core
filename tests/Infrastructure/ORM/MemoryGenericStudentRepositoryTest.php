<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use Logeecom\Tests\Common\TestComponents\ORM\MemoryRepository;

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
        return MemoryRepository::getClassName();
    }

    /**
     * Cleans up all storage services used by repositories
     */
    public function cleanUpStorage()
    {
    }
}
