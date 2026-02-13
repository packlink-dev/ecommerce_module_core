<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\RegistrationController as RegistrationControllerBase;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\UpdateShippingServices\UpdateShippingServicesOrchestrator;
use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class RegistrationController.
 *
 * @package Packlink\DemoUI\Controllers
 */
class RegistrationController extends BaseHttpController
{
    /**
     * @var bool
     */
    protected $requiresAuthentication = false;
    /**
     * @var RegistrationControllerBase
     */
    private $controller;
    /**
     * @var UpdateShippingServicesOrchestrator
     */
    private $orchestrator;

    /**
     * RegistrationController constructor.
     */
    public function __construct(UpdateShippingServicesOrchestrator $orchestrator)
    {
        $this->controller = new RegistrationControllerBase();
        $this->orchestrator = $orchestrator;
    }

    /**
     * Handles GET request.
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function get(Request $request)
    {
        $this->output($this->controller->getRegisterData($request->getQuery('country')));
    }

    /**
     * Handles POST request.
     *
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     * @throws \Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException
     */
    public function post(Request $request)
    {
        $payload = $request->getPayload();
        $payload['ecommerces'] = array('Test');

        $success = $this->controller->register($payload);
        if ($success) {
            /** @var Configuration $configService */
            $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
            $this->orchestrator->enqueue($configService->getContext());
        }

        $this->output(array('success' => $success));
    }
}
