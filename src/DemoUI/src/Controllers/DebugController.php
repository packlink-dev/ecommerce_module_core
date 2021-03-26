<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\Controllers\DebugController as CoreDebugController;
use Packlink\DemoUI\Controllers\Models\Request;
use Packlink\DemoUI\Services\Integration\UrlService;

/**
 * Class DebugController
 *
 * @package Packlink\DemoUI\Controllers
 */
class DebugController extends BaseHttpController
{
    /**
     * @var CoreDebugController
     */
    private $controller;

    /**
     * DebugController constructor.
     */
    public function __construct()
    {
        $this->controller = new CoreDebugController();
    }

    /**
     * Gets the debug status and the download URL
     */
    public function getStatus()
    {
        $this->output(
            array(
                'status' => $this->controller->getStatus(),
                'downloadUrl' => UrlService::getEndpointUrl('Debug', 'getSystemInfo'),
            )
        );
    }

    /**
     * @param \Packlink\DemoUI\Controllers\Models\Request $request
     */
    public function setStatus(Request $request)
    {
        $data = $request->getPayload();
        $this->controller->setStatus((bool)$data['status']);
    }

    /**
     * Gets the system info file.
     *
     * @throws \Exception
     */
    public function getSystemInfo()
    {
        $filePath = $this->controller->getSystemInfo();

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header(
            'Content-Disposition: attachment; filename=' . CoreDebugController::SYSTEM_INFO_FILE_NAME
        );
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);

        http_response_code(200);
        die();
    }
}