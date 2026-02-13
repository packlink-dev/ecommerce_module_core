<?php

namespace Packlink\BusinessLogic\UpdateShippingServices\Interfaces;

use Packlink\BusinessLogic\UpdateShippingServices\Models\UpdateShippingServiceTaskStatus;

interface UpdateShippingServiceTaskStatusServiceInterface
{
    /**
     * Saves new status.
     *
     * @param UpdateShippingServiceTaskStatus $status
     */
    public function save(UpdateShippingServiceTaskStatus $status);

    /**
     * Updates existing status.
     *
     * @param UpdateShippingServiceTaskStatus $status
     */
    public function update(UpdateShippingServiceTaskStatus $status);

    /**
     * Deletes status.
     *
     * @param UpdateShippingServiceTaskStatus $status
     */
    public function delete(UpdateShippingServiceTaskStatus $status);

    /**
     * Returns latest status for given context.
     *
     * @param string $context
     *
     * @return UpdateShippingServiceTaskStatus|null
     */
    public function getLatestByContext($context);

    /**
     * Returns latest status string for context.
     *
     * @param string $context
     *
     * @return string|null
     */
    public function getLatestStatus($context);

    /**
     * Creates or updates latest status entity for context.
     *
     * @param string $context
     * @param string $status
     * @param string|null $error
     * @param bool $finished
     *
     * @return UpdateShippingServiceTaskStatus
     */
    public function upsertStatus($context, $status, $error = null, $finished = false);
}