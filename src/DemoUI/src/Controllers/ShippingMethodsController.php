<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Exceptions\BaseException;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\Controllers\UpdateShippingServicesTaskStatusController;
use Packlink\BusinessLogic\Language\Translator;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;
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

        // No services yet. In the demo there is no async runner, so run the update task
        // synchronously here (self-heal): this resolves the otherwise-permanent polling loop
        // by actually fetching and saving the carriers, then reporting the real outcome.
        $this->runUpdateTaskNow();

        if (count($this->controller->getAll()) > 0) {
            $this->output(array('status' => QueueItem::COMPLETED));

            return;
        }

        // Task ran but produced no services: report failed so the frontend stops polling.
        $this->output(array('status' => QueueItem::FAILED));
    }

    /**
     * Clears any leftover task status and runs the update-shipping-services task synchronously.
     * Used to self-heal the demo when no services are present yet.
     */
    private function runUpdateTaskNow()
    {
        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);
        /** @var UpdateShippingServiceTaskStatusServiceInterface $statusService */
        $statusService = ServiceRegister::getService(UpdateShippingServiceTaskStatusServiceInterface::class);
        /** @var UpdateShippingServicesOrchestratorInterface $orchestrator */
        $orchestrator = ServiceRegister::getService(UpdateShippingServicesOrchestratorInterface::class);

        $context = (string)$config->getContext();

        // Clear stuck/non-terminal status so the orchestrator does not skip the run.
        for ($i = 0; $i < 50; $i++) {
            $entity = $statusService->getLatestByContext($context);
            if (!$entity) {
                break;
            }

            $statusService->delete($entity);
        }

        try {
            // Synchronous executor runs the task in-process and saves the carriers.
            $orchestrator->enqueue($context);
        } catch (\Throwable $e) {
            error_log('Demo: update shipping services task failed: ' . $e->getMessage());
        }
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