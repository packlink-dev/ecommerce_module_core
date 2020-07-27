<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\Exceptions\BaseException;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\Controllers\UpdateShippingServicesTaskStatusController;
use Packlink\BusinessLogic\Language\Translator;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\Tax\TaxClass;
use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class ShippingMethodsController
 *
 * @package Packlink\DemoUI\Controllers
 */
class ShippingMethodsController extends BaseHttpController
{
    /**
     * @var \Packlink\BusinessLogic\Controllers\ShippingMethodController
     */
    private $controller;

    /**
     * ShippingMethodsController constructor.
     */
    public function __construct()
    {
        $this->controller = new ShippingMethodController();
    }

    /**
     * Gets active services.
     */
    public function getActive()
    {
        $this->outputDtoEntities($this->controller->getActive());
    }

    /**
     * Gets inactive services.
     */
    public function getInactive()
    {
        $this->outputDtoEntities($this->controller->getInactive());
    }

    /**
     * Gets the status of the get services task auto configuration.
     */
    public function getTaskStatus()
    {
        if (count($this->controller->getAll()) > 0) {
            $this->output(array('status' => QueueItem::COMPLETED));

            return;
        }

        try {
            $controller = new UpdateShippingServicesTaskStatusController();
            $status = $controller->getLastTaskStatus();
        } catch (BaseException $e) {
            $status = QueueItem::FAILED;
        }

        $this->output(array('status' => $status));
    }

    /**
     * Gets a single service.
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function getService(Request $request)
    {
        $method = $this->controller->getShippingMethod((int)$request->getQuery('id'));

        $this->output($method ? $method->toArray() : array());
    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function deactivate(Request $request)
    {
        $payload = $request->getPayload();

        $this->output(array('status' => $this->controller->deactivate($payload['id'])));
    }

    /**
     * A mockup for getting system tax classes.
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function getTaxClasses()
    {
        $taxClass1 = TaxClass::fromArray(array('label' => 'Full Rate (20%)', 'value' => 1));
        $taxClass2 = TaxClass::fromArray(array('label' => 'Half Rate (10%)', 'value' => 2));
        $taxClass3 = TaxClass::fromArray(array('label' => 'Tax Free', 'value' => 0));

        $this->outputDtoEntities(array($taxClass1, $taxClass2, $taxClass3));
    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function save(Request $request)
    {
        $shippingMethod = ShippingMethodConfiguration::fromArray($request->getPayload());
        $response = $this->controller->save($shippingMethod);

        $this->output($response ? $response->toArray() : array());
    }

    /**
     * Disables shop carriers.
     */
    public function disableCarriers()
    {
        /** @var ShopShippingMethodService $carrierService */
        $carrierService = ServiceRegister::getService(ShopShippingMethodService::CLASS_NAME);
        if ($carrierService->disableShopServices()) {
            $this->output(
                array(
                    'success' => true,
                    'message' => Translator::translate('shippingServices.successfullyDisabledShippingMethods'),
                )
            );
        } else {
            throw new \RuntimeException(Translator::translate('shippingServices.failedToDisableShippingMethods'));
        }
    }
}