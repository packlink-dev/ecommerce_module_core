<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\ORM;

use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Packlink\BusinessLogic\ORM\Contracts\ConditionallyDeletes;

/**
 * Class MemoryRepositoryWithConditionalDelete
 *
 * @package Logeecom\Tests\BusinessLogic\Common\TestComponents\ORM
 */
class MemoryQueueItemReposiotoryWithConditionalDelete extends MemoryQueueItemRepository implements ConditionallyDeletes
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;

    /**
     * Conditionally deletes records in a database table based on the provided query filter.
     *
     * If the filter is null all records will be deleted from the database table.
     *
     * @param \Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter|null $filter Filter that identifies records to be
     *     deleted.
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function deleteWhere(QueryFilter $filter = null)
    {
        // IMPORTANT NOTICE!!!
        // DO NOT USE THIS AS A REFERENCE IMPLEMENTATION OF THE DELETE WHERE METHOD
        // SINCE THE WHOLE PURPOSE OF THIS METHOD IS TO AVOID RETRIEVING ENTITIES BEFORE DELETE
        $toBeDeleted = $this->select($filter);
        foreach ($toBeDeleted as $entity) {
            $this->delete($entity);
        }
    }
}