<?php

namespace Packlink\BusinessLogic\WebHook;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Exceptions\OrderNotFound;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
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
     * Order repository instance.
     *
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * Proxy instance.
     *
     * @var Proxy
     */
    private $proxy;

    /**
     * WebHookService constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        $this->orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
    }

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
     * Handles web hook shipment label event.
     *
     * @param string $referenceId
     * @param bool $updateShipmentStatus
     */
    public function handleShipmentLabelEvent($referenceId, $updateShipmentStatus = false)
    {
        $labels = array();
        try {
            $labels = $this->proxy->getLabels($referenceId);
            if (count($labels) > 0) {
                $this->orderRepository->setLabelsByReference($referenceId, $labels);
                if ($updateShipmentStatus) {
                    $this->orderRepository->setShippingStatusByReference(
                        $referenceId,
                        ShipmentStatus::STATUS_READY
                    );
                }
            }
        } catch (HttpBaseException $e) {
            Logger::logError($e->getMessage(), 'Core', array('referenceId' => $referenceId));
        } catch (OrderNotFound $e) {
            Logger::logInfo($e->getMessage(), 'Core', array('referenceId' => $referenceId, 'labels' => $labels));
        }
    }

    /**
     * Handles web hook shipping status update event.
     *
     * @param string $referenceId
     * @param string $status
     */
    public function handleShippingStatusEvent($referenceId, $status)
    {
        $shipment = null;
        try {
            $shipment = $this->proxy->getShipment($referenceId);
            if ($shipment !== null) {
                $this->orderRepository->setShippingStatusByReference($referenceId, $status);
            }
        } catch (HttpBaseException $e) {
            Logger::logError($e->getMessage(), 'Core', array('referenceId' => $referenceId));
        } catch (OrderNotFound $e) {
            Logger::logInfo(
                $e->getMessage(),
                'Core',
                array('referenceId' => $referenceId, 'shipment' => $shipment ? $shipment->toArray() : 'NONE')
            );
        }
    }

    /**
     * Handles web hook tracking info update event.
     *
     * @param string $referenceId
     * @param bool $updateShipmentStatus
     */
    public function handleTrackingInfoEvent($referenceId, $updateShipmentStatus = false)
    {
        $trackingHistory = array();
        try {
            $trackingHistory = $this->proxy->getTrackingInfo($referenceId);
            $shipment = $this->proxy->getShipment($referenceId);
            if ($shipment !== null) {
                $this->orderRepository->updateTrackingInfo($referenceId, $trackingHistory, $shipment);
                if ($updateShipmentStatus) {
                    $this->orderRepository->setShippingStatusByReference(
                        $referenceId,
                        ShipmentStatus::STATUS_IN_TRANSIT
                    );
                }
            }
        } catch (HttpBaseException $e) {
            Logger::logError($e->getMessage(), 'Core', array('referenceId' => $referenceId));
        } catch (OrderNotFound $e) {
            $trackingAsArray = array();
            foreach ($trackingHistory as $item) {
                $trackingAsArray[] = $item->toArray();
            }

            Logger::logInfo(
                $e->getMessage(),
                'Core',
                array('referenceId' => $referenceId, 'trackingHistory' => $trackingAsArray)
            );
        }
    }

    /**
     * Validates request payload.
     *
     * @param \stdClass $payload Request data.
     *
     * @return bool
     */
    private function validatePayload($payload)
    {
        $validEvents = array(
            'shipment.carrier.success',
            'shipment.carrier.fail',
            'shipment.label.ready',
            'shipment.label.fail',
            'shipment.tracking.update',
            'shipment.delivered',
        );

        return !($payload === null
            || !$payload->datetime
            || !$payload->data
            || !in_array($payload->event, $validEvents, true));
    }

    /**
     * Check whether auth token exists.
     *
     * @return bool
     */
    private function checkAuthToken()
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
    private function handleEvent($eventName, $eventData)
    {
        switch ($eventName) {
            case 'shipment.carrier.success':
                $this->handleShippingStatusEvent(
                    $eventData->shipment_reference,
                    ShipmentStatus::STATUS_ACCEPTED
                );
                break;
            case 'shipment.delivered':
                $this->handleShippingStatusEvent(
                    $eventData->shipment_reference,
                    ShipmentStatus::STATUS_DELIVERED
                );
                break;
            case 'shipment.label.ready':
                $this->handleShipmentLabelEvent($eventData->shipment_reference);
                break;
            case 'shipment.tracking.update':
                $this->handleTrackingInfoEvent($eventData->shipment_reference);
                break;
            case 'shipment.carrier.fail':
            case 'shipment.label.fail':
            default:
                // Not handled in core for now.
        }
    }
}
