<?php

namespace Packlink\BusinessLogic\WebHook\Exceptions;

use Logeecom\Infrastructure\Exceptions\BaseException;

/**
 * Class WebhookPayloadValidationException.
 *
 * Thrown when a webhook payload is missing required fields or is malformed.
 *
 * @package Packlink\BusinessLogic\WebHook\Exceptions
 */
class WebhookPayloadValidationException extends BaseException
{
}
