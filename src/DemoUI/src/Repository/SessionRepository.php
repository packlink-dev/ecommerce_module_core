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
     * @var int
     */
    private $lastId;
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;

    /**
     * SessionRepository constructor.
     */
    public function __construct()
    {
        $this->ensureStorage();
        $this->lastId = count($_SESSION['storage']);
    }

    /**
     * @inheritDoc
     */
    protected function getStorage()
    {
        return $_SESSION['storage'];
    }

    /**
     * @inheritDoc
     */
    protected function saveToStorage($key, $item)
    {
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
     * Generates a new ID.
     *
     * @return int
     */
    protected function generateId()
    {
        return ++$this->lastId;
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