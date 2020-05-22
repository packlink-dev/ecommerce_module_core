<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\DemoUI\Controllers\Models\Request;


/**
 * Class ResolverController
 * @package Packlink\DemoUI\Controllers
 */
class ResolverController
{
    /**
     * Main entry point. In this method the request will be resolved.
     */
    public function handleAction()
    {
        $controllerName = $_GET['controller'];
        $action = $_GET['action'];
        $query = $this->getQueryParams();
        $payload = json_decode(file_get_contents('php://input'), true);
        $payload = $payload != null ? $payload : array();
        $headers = getallheaders();

        $controllerClass =  __NAMESPACE__ . '\\' . $controllerName;
        $controller = new $controllerClass;

        header('Content-Type: application/json');

        $controller->$action(new Request($query, $payload, $headers));
    }

    /**
     * @return array
     */
    private function getQueryParams()
    {
        $result = array();

        foreach (array_keys($_GET) as $arrayKey) {
            if ($arrayKey !== 'controller' && $arrayKey !== 'action') {
                $result[$arrayKey] = $_GET[$arrayKey];
            }
        }

        return $result;
    }
}