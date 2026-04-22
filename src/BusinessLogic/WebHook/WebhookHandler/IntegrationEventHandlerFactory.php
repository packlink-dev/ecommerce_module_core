<?php

namespace Packlink\BusinessLogic\WebHook\WebhookHandler;

/**
 * Class IntegrationEventHandlerFactory.
 *
 * Returns the concrete handler for a given integration event status.
 *
 * @package Packlink\BusinessLogic\WebHook\WebhookHandler
 */
class IntegrationEventHandlerFactory
{
    /**
     * Map of status constants to handler class names.
     *
     * @var array
     */
    private static $handlerMap = array(
        IntegrationEventStatuses::STATUS_ENABLED => 'Packlink\BusinessLogic\WebHook\WebhookHandler\EnabledStatusHandler',
        IntegrationEventStatuses::STATUS_DISABLED => 'Packlink\BusinessLogic\WebHook\WebhookHandler\DisabledStatusHandler',
        IntegrationEventStatuses::STATUS_DELETED => 'Packlink\BusinessLogic\WebHook\WebhookHandler\DeletedStatusHandler',
    );

    /**
     * Returns the concrete handler for the given status, or null if unsupported.
     *
     * @param string $status One of IntegrationEventStatuses constants.
     *
     * @return AbstractIntegrationEventHandler|null
     */
    public static function create($status)
    {
        if (!isset(self::$handlerMap[$status])) {
            return null;
        }

        $class = self::$handlerMap[$status];

        return new $class();
    }
}
