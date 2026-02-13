<?php

namespace Packlink\BusinessLogic\UpdateShippingServices\Interfaces;

interface UpdateShippingServicesOrchestratorInterface
{
    /**
     * Adds an item to the queue for processing or handling.
     *
     * @param string|null $context The data or context to be added to the queue.
     */
    public function enqueue($context);
}