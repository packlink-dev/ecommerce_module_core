<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\OrderStatusMappingController as OrderStatusMappingControllerBase;
use Packlink\BusinessLogic\Language\Translator;
use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class OrderStateMappingController
 *
 * @package Packlink\DemoUI\Controllers
 */
class OrderStatusMappingController extends BaseHttpController
{
    /**
     * @var OrderStatusMappingControllerBase
     */
    private $baseController;

    /**
     * OrderStatusMappingController constructor.
     */
    public function __construct()
    {
        $this->baseController = new OrderStatusMappingControllerBase();
    }

    /**
     * Gets system order statuses and saved mappings.
     */
    public function getMappingAndStatuses()
    {
        $mappings = $this->baseController->getMappings();
        $packlinkStatuses = $this->baseController->getPacklinkStatuses();
        $systemStatuses = $this->getSystemOrderStatuses();

        $this->output(
            array(
                'systemName' => $this->getConfigService()->getIntegrationName(),
                'mappings' => $mappings,
                'orderStatuses' => $systemStatuses,
                'packlinkStatuses' => $packlinkStatuses,
            )
        );
    }

    /**
     * Saves order status mappings.
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function setMappings(Request $request)
    {
        $this->baseController->setMappings($request->getPayload());
    }

    /**
     * Gets system order statuses.
     *
     * @return array
     */
    private function getSystemOrderStatuses()
    {
        return array(
            '' => Translator::translate('orderStatusMapping.none'),
            'open' => 'open',
            'await' => 'awaiting payment',
            'paid' => 'paid',
            'proc' => 'processing',
            'done' => 'completed',
        );
    }
}
