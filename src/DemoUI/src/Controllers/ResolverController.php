<?php

namespace Packlink\DemoUI\Controllers;

use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\DemoUI\Controllers\Models\Request;

/**
 * Class ResolverController
 *
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
        $payload = json_decode(file_get_contents('php://input'), true) ?: array();
        $headers = getallheaders();

        $controllerClass = __NAMESPACE__ . '\\' . $controllerName . 'Controller';
        $controller = new $controllerClass;

        header('Content-Type: application/json');

        if (!$controller->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(
                array(
                    'success' => false,
                    'error' => 'Unauthorized.',
                )
            );

            return;
        }

        try {
            if (method_exists($controller, $action)) {
                $controller->$action(new Request($query, $payload, $headers));
            } else {
                throw new \RuntimeException("Controller $controllerName does not implement action $action.");
            }
        } catch (FrontDtoValidationException $e) {
            http_response_code(400);
            $result = array(
                'success' => false,
                'messages' => array(),
            );
            foreach ($e->getValidationErrors() as $error) {
                $result['messages'][] = $error->toArray();
            }

            echo json_encode($result);
        } catch (\Exception $e) {
            http_response_code(400);

            echo json_encode(
                array(
                    'success' => false,
                    'error' => $e->getMessage(),
                )
            );
        }
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