<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use Logeecom\Tests\Common\TestComponents\ORM\MemoryQueueItemRepository;

/**
 * Class MemoryGenericQueueItemRepositoryTest.
 *
 * @package Logeecom\Tests\Infrastructure\ORM
 */
class MemoryGenericQueueItemRepositoryTest extends AbstractGenericQueueItemRepositoryTest
{
    /**
     * @return string
     */
    public function getQueueItemEntityRepositoryClass()
    {
        return MemoryQueueItemRepository::getClassName();
    }

    /**
     * Cleans up all storage services used by repositories
     */
    public function cleanUpStorage()
    {
    }
}
