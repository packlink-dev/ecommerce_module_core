<?php

namespace Packlink\BusinessLogic\WebHook\Exceptions;

use Logeecom\Infrastructure\Exceptions\BaseException;

/**
 * Class WebhookAuthorizationException.
 *
 * Thrown when a webhook request fails secret header validation.
 *
 * @package Packlink\BusinessLogic\WebHook\Exceptions
 */
class WebhookAuthorizationException extends BaseException
{
}
