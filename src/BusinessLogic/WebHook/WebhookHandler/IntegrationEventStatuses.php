<?php

namespace Packlink\BusinessLogic\WebHook\WebhookHandler;

/**
 * Interface IntegrationEventStatuses.
 *
 * Contains status constants for integration registration webhook events.
 *
 * @package Packlink\BusinessLogic\WebHook\WebhookHandler
 */
interface IntegrationEventStatuses
{
    const STATUS_ENABLED = 'ENABLED';
    const STATUS_DISABLED = 'DISABLED';
    const STATUS_DELETED = 'DELETED';
}
