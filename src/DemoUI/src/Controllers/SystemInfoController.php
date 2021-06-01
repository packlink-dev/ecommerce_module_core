<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\SystemInfoController as CoreSystemInfoController;

/**
 * Class SystemInfoController
 */
class SystemInfoController extends BaseHttpController
{
    /**
     * @var CoreSystemInfoController
     */
    private $controller;

    /**
     * DebugController constructor.
     */
    public function __construct()
    {
        $this->controller = new CoreSystemInfoController();
    }

    public function get()
    {
        $this->outputDtoEntities($this->controller->get());
    }
}