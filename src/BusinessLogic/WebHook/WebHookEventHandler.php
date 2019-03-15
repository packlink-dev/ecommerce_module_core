<?php

namespace Packlink\BusinessLogic\WebHook;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Exceptions\OrderNotFound;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\BusinessLogic\WebHook\Events\ShipmentLabelEvent;
use Packlink\BusinessLogic\WebHook\Events\ShipmentStatusChangedEvent;
use Packlink\BusinessLogic\WebHook\Events\TrackingInfoEvent;

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
     * Handles web hook shipment label event.
     *
     * @param ShipmentLabelEvent $event Web hook event.
     */
    public function handleShipmentLabelEvent(ShipmentLabelEvent $event)
    {
        $labels = array();
        $referenceId = $event->referenceId;
        try {
            $labels = $this->proxy->getLabels($referenceId);
            if (count($labels) > 0) {
                $this->orderRepository->setLabelsByReference($referenceId, $labels);
                if ($event->updateShipmentStatus) {
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
     * @param ShipmentStatusChangedEvent $event Web hook event.
     */
    public function handleShippingStatusEvent(ShipmentStatusChangedEvent $event)
    {
        $referenceId = $event->getReferenceId();
        $shipment = null;
        try {
            $shipment = $this->proxy->getShipment($referenceId);
            if ($shipment !== null) {
                $this->orderRepository->setShippingStatusByReference($referenceId, $event->getStatus());
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
     * @param TrackingInfoEvent $event Web hook event.
     */
    public function handleTrackingInfoEvent(TrackingInfoEvent $event)
    {
        $referenceId = $event->referenceId;
        $trackingHistory = array();
        try {
            $trackingHistory = $this->proxy->getTrackingInfo($referenceId);
            $shipment = $this->proxy->getShipment($referenceId);
            if ($shipment !== null) {
                $this->orderRepository->updateTrackingInfo($referenceId, $trackingHistory, $shipment);
                if ($event->updateShipmentStatus) {
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
}
