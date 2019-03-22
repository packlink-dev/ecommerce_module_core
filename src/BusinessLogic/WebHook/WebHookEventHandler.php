<?php

namespace Packlink\BusinessLogic\WebHook;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
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

        $payload = json_decode($input);

        if (!$this->validatePayload($payload)) {
            return false;
        }

        if ($this->checkAuthToken()) {
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

        switch ($eventName) {
            case 'shipment.carrier.success':
                $orderService->updateShippingStatus($eventData->shipment_reference, ShipmentStatus::STATUS_ACCEPTED);
                break;
            case 'shipment.delivered':
                $orderService->updateShippingStatus($eventData->shipment_reference, ShipmentStatus::STATUS_DELIVERED);
                break;
            case 'shipment.label.ready':
                $orderService->updateShipmentLabel($eventData->shipment_reference);
                break;
            case 'shipment.tracking.update':
                $orderService->updateTrackingInfo($eventData->shipment_reference);
                break;
            case 'shipment.carrier.fail':
            case 'shipment.label.fail':
            default:
                // Not handled in core for now.
        }
    }
}
