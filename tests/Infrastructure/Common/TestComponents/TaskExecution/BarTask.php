<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution;

class BarTask extends FooTask
{
    public function execute()
    {
        parent::execute();

        $this->reportProgress(100);
    }
}