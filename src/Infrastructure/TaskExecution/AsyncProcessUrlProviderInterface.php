<?php

namespace Logeecom\Infrastructure\TaskExecution;

interface AsyncProcessUrlProviderInterface
{
    const CLASS_NAME = __CLASS__;

    /**
     * @param $guid
     *
     * @return mixed
     */
    public function getAsyncProcessUrl($guid);
}