<?php

namespace Packlink\BusinessLogic\WebHook\Interfaces;

interface WebHookEventHandler
{
    /**
     * Validates input and handles Packlink webhook event.
     *
     * @param string $input Request input (raw body).
     *
     * @return bool Result.
     */
    public function handle($input);
}