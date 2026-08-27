<?php

namespace Packlink\BusinessLogic\WebHook;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound;

/**
 * Class WebHookService.
 *
 * @package Packlink\BusinessLogic\WebHook
 */
class WebHookEventHandler extends BaseService implements Interfaces\WebHookEventHandlerInterface
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
        'shipment.carrier.delivered',
        'shipment.incident',
    );
    /**
     * Prefix that Packlink's notification service prepends to some event names
     * (for example "shipments.shipment.incident"). Stripped before the name is matched.
     */
    const EVENT_NAME_PREFIX = 'shipments.';

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

        if (is_object($payload) && isset($payload->event)) {
            $payload->event = $this->normalizeEventName($payload->event);
        }

        if (!$this->validatePayload($payload)) {
            return false;
        }

        if (!$this->checkAuthToken()) {
            // No authorization token means the integration is not connected. Every notification
            // is answered with a success response and does nothing, so without this the store
            // silently stops syncing while Packlink still records each delivery as successful.
            Logger::logWarning(
                'Webhook accepted but not processed: the integration has no authorization token.',
                'Core',
                array('event' => $payload->event)
            );

            return true;
        }

        if (!$this->shouldHandleEvent($payload->event)) {
            // Deliberately not synced - see shouldHandleEvent(). Logged at warning level because
            // the names that land here (shipment.carrier.fail, shipment.label.fail) report a
            // failure the merchant needs to know about, and because a merchant who has lowered
            // the log level to keep production quiet would otherwise have no record at all.
            Logger::logWarning(
                'Webhook accepted but deliberately not synced.',
                'Core',
                array(
                    'event' => $payload->event,
                    'reference' => isset($payload->data->shipment_reference)
                        ? $payload->data->shipment_reference
                        : '',
                )
            );

            return true;
        }

        $this->handleEvent($payload->data);

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
            && in_array($payload->event, static::$validEvents, true);
    }

    /**
     * Strips the plural event name prefix used by the notification service, so that a name
     * arriving as "shipments.shipment.incident" is matched as "shipment.incident".
     *
     * @param string $eventName The raw event name from the payload.
     *
     * @return string The normalized event name.
     */
    protected function normalizeEventName($eventName)
    {
        if (is_string($eventName) && strpos($eventName, self::EVENT_NAME_PREFIX) === 0) {
            return substr($eventName, strlen(self::EVENT_NAME_PREFIX));
        }

        return $eventName;
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
     * @param \stdClass $eventData Event payload data.
     */
    protected function handleEvent($eventData)
    {
        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(\Packlink\BusinessLogic\Http\Interfaces\Proxy::CLASS_NAME);
        try {
            /** @var \Packlink\BusinessLogic\Http\DTO\Shipment $shipment */
            $shipment = $proxy->getShipment($eventData->shipment_reference);
        } catch (HttpBaseException $e) {
            Logger::logWarning($e->getMessage(), 'Core', array(
                'referenceId' => $eventData->shipment_reference,
                'trace' => $e->getTraceAsString(),
            ));

            return;
        }

        if ($shipment === null) {
            Logger::logWarning(
                'Received a webhook for an unknown shipment.',
                'Core',
                array('reference' => $eventData->shipment_reference)
            );

            return;
        }

        try {
            /** @var OrderService $orderService */
            $orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
            $orderService->updateShipmentData($shipment);
        } catch (OrderShipmentDetailsNotFound $e) {
            Logger::logWarning($e->getMessage());
        }
    }

    /**
     * Checks if event should be handled further.
     *
     * Narrower than {@see static::$validEvents}: names listed there but not here are answered
     * with a success response and deliberately not synced. The name does not select behaviour -
     * handleEvent() derives everything from the shipment status - so any name added to
     * $validEvents that should move the order must be added here as well.
     *
     * @param string $eventName The normalized name of the event.
     *
     * @return bool TRUE if the event handing should be done; otherwise, FALSE.
     */
    protected function shouldHandleEvent($eventName)
    {
        return in_array(
            $eventName,
            array(
                'shipment.carrier.success',
                'shipment.delivered',
                'shipment.carrier.delivered',
                'shipment.label.ready',
                'shipment.tracking.update',
                'shipment.incident',
            ),
            true
        );
    }
}
