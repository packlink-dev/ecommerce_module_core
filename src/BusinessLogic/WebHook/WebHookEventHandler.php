<?php

namespace Packlink\BusinessLogic\WebHook;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;

/**
 * Class WebHookService.
 *
 * @package Packlink\BusinessLogic\WebHook
 */
class WebHookEventHandler extends BaseService
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * List of valid events that are handled by webhook handler.
     *
     * @var array
     */
    protected static $validEvents = array(
        'shipment.carrier.success',
        'shipment.carrier.fail',
        'shipment.label.ready',
        'shipment.label.fail',
        'shipment.tracking.update',
        'shipment.delivered',
    );

    /**
     * Validates input and handles Packlink webhook event.
     *
     * @param string $input Request input.
     *
     * @return bool Result.
     */
    public function handle($input)
    {
        Logger::logDebug(
            'Webhook from Packlink received.',
            'Core',
            array('payload' => $input)
        );

        $payload = json_decode($input, false);

        if (!$this->validatePayload($payload)) {
            return false;
        }

        if ($this->checkAuthToken() && $this->shouldHandleEvent($payload->event)) {
            $this->handleEvent($payload->event, $payload->data);
        }

        return true;
    }

    /**
     * Validates request payload.
     *
     * @param \stdClass $payload Request data.
     *
     * @return bool
     */
    protected function validatePayload($payload)
    {
        return $payload !== null
            && $payload->datetime
            && $payload->data
            && in_array($payload->event, self::$validEvents, true);
    }

    /**
     * Check whether auth token exists.
     *
     * @return bool
     */
    protected function checkAuthToken()
    {
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $authToken = $configService->getAuthorizationToken();

        return !empty($authToken);
    }

    /**
     * Handles concrete event based on event name.
     *
     * @param string $eventName Name of the event.
     * @param \stdClass $eventData Event payload data.
     */
    protected function handleEvent($eventName, $eventData)
    {
        /** @var OrderService $orderService */
        $orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        try {
            /** @var \Packlink\BusinessLogic\Http\DTO\Shipment $shipment */
            $shipment = $proxy->getShipment($eventData->shipment_reference);
        } catch (HttpBaseException $e) {
            Logger::logError($e->getMessage(), 'Core', array('referenceId' => $eventData->shipment_reference));

            return;
        }

        if ($shipment === null) {
            return;
        }

        switch ($eventName) {
            case 'shipment.carrier.success':
                $orderService->updateShippingStatus($shipment, ShipmentStatus::STATUS_ACCEPTED);
                break;
            case 'shipment.delivered':
                $orderService->updateShippingStatus($shipment, ShipmentStatus::STATUS_DELIVERED);
                break;
            case 'shipment.tracking.update':
                $orderService->updateTrackingInfo($shipment);
                break;
        }
    }

    /**
     * Checks if event should be handled further.
     *
     * @param string $eventName The name of the event.
     *
     * @return bool TRUE if the event handing should be done; otherwise, FALSE.
     */
    private function shouldHandleEvent($eventName)
    {
        return in_array(
            $eventName,
            array('shipment.carrier.success', 'shipment.delivered', 'shipment.label.ready', 'shipment.tracking.update'),
            true
        );
    }
}
