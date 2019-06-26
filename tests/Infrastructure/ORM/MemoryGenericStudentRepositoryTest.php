<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;

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
        MemoryStorage::reset();
    }
}
