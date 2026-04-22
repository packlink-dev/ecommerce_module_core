<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\IntegrationRegistration;

use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\ModuleResetServiceInterface;

class TestModuleResetService implements ModuleResetServiceInterface
{
    /**
     * @var bool
     */
    private $resetCalled = false;

    /**
     * @var bool
     */
    private $shouldFail = false;

    /**
     * @inheritdoc
     */
    public function resetModule()
    {
        $this->resetCalled = true;

        return !$this->shouldFail;
    }

    /**
     * Returns whether resetModule() was called during this test.
     *
     * @return bool
     */
    public function wasResetCalled()
    {
        return $this->resetCalled;
    }

    /**
     * Configure the mock to return false from resetModule().
     *
     * @param bool $shouldFail
     */
    public function setShouldFail($shouldFail)
    {
        $this->shouldFail = $shouldFail;
    }
}