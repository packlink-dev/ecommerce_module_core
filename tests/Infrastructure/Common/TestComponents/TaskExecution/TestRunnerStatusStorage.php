<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerStatus;

class TestRunnerStatusStorage implements TaskRunnerStatusStorage
{

    /** @var TaskRunnerStatus|null */
    private $status;

    private $callHistory = array();
    private $exceptionResponses = array();

    public function getMethodCallHistory($methodName)
    {
        return !empty($this->callHistory[$methodName]) ? $this->callHistory[$methodName] : array();
    }

    public function initializeStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        if (!empty($this->exceptionResponses['getStatus'])) {
            throw $this->exceptionResponses['getStatus'];
        }

        $this->callHistory['getStatus'][] = array();

        return $this->status !== null ? $this->status : TaskRunnerStatus::createNullStatus();
    }

    public function setStatus(TaskRunnerStatus $status)
    {
        if (!empty($this->exceptionResponses['setStatus'])) {
            throw $this->exceptionResponses['setStatus'];
        }

        $this->callHistory['setStatus'][] = array('status' => $status);
        $this->status = $status;
    }

    public function setExceptionResponse($methodName, $exceptionToThrow)
    {
        $this->exceptionResponses[$methodName] = $exceptionToThrow;
    }
}