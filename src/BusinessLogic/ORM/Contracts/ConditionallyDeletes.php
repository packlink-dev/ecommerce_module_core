<?php

namespace Packlink\BusinessLogic\ORM\Contracts;

use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;

/**
 * Interface ConditionallyDeletes
 *
 * @package Packlink\BusinessLogic\ORM\Contracts
 */
interface ConditionallyDeletes
{
    /**
     * Conditionally deletes records in a database table based on the provided query filter.
     *
     * If the filter is null all records will be deleted from the database table.
     *
     * @param \Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter|null $filter Filter that identifies records to be
     *     deleted.
     *
     * @return void
     */
    public function deleteWhere(QueryFilter $filter = null);
}