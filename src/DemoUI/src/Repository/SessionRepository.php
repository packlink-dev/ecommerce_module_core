<?php

namespace Packlink\DemoUI\Repository;

use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;

/**
 * Class SessionRepository.
 *
 * @package Packlink\DemoUI\Repository
 */
class SessionRepository extends MemoryRepository
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;

    /**
     * @inheritDoc
     */
    protected function getStorage()
    {
        $this->ensureStorage();

        return $_SESSION['storage'];
    }

    /**
     * @inheritDoc
     */
    protected function saveToStorage($key, $item)
    {
        $this->ensureStorage();

        $_SESSION['storage'][$key] = $item;
    }

    /**
     * @inheritDoc
     */
    protected function deleteFromStorage($key)
    {
        unset($_SESSION['storage'][$key]);
    }

    /**
     * Creates an array for storage in the session.
     */
    private function ensureStorage()
    {
        if (!array_key_exists('storage', $_SESSION)) {
            $_SESSION['storage'] = array();
        }
    }
}