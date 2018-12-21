<?php

namespace Logeecom\Tests\Infrastructure\ORM\Entity;

use Logeecom\Infrastructure\ORM\Entities\QueueItem;

/**
 * Class QueueItemTest.
 *
 * @package Logeecom\Tests\Infrastructure\ORM\Entity
 */
class QueueItemTest extends GenericEntityTest
{
    /**
     * Returns entity full class name
     *
     * @return string
     */
    public function getEntityClass()
    {
        return QueueItem::getClassName();
    }
}
