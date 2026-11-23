<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\SimulatedPricesController as BaseSimulatedPricesController;
use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class SimulatedPricesController. Wires the "Discover our shipping rates" panel (CR-SET-72-02).
 *
 * @package Packlink\DemoUI\Controllers
 */
class SimulatedPricesController extends BaseHttpController
{
    /**
     * Returns the panel data.
     */
    public function getData()
    {
        $controller = new BaseSimulatedPricesController();

        $this->output($controller->getData());
    }

    /**
     * Quotes the sample parcel. Core reads only the chip and the destination from the payload.
     *
     * @param Request $request
     */
    public function search(Request $request)
    {
        $payload = $request->getPayload();
        $controller = new BaseSimulatedPricesController();

        $this->output($controller->search(is_array($payload) ? $payload : array()));
    }
}
