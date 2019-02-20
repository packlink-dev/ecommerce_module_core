<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\BusinessLogic\WebHook\Events\ShipmentLabelEvent;
use Packlink\BusinessLogic\WebHook\Events\ShipmentStatusChangedEvent;
use Packlink\BusinessLogic\WebHook\Events\TrackingInfoEvent;

/**
 * Class UpdateShipmentDataTask
 * @package Packlink\BusinessLogic\Tasks
 */
class UpdateShipmentDataTask extends Task
{
    /**
     * Runs task logic.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function execute()
    {
        /** @var \Packlink\BusinessLogic\Order\Interfaces\OrderRepository $orderRepository */
        $orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        /** @var EventBus $eventBus */
        $eventBus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $orderReferences = $orderRepository->getIncompleteOrderReferences();

        foreach ($orderReferences as $orderReference) {
            $shipment = $proxy->getShipment($orderReference);
            $eventBus->fire(new ShipmentLabelEvent($orderReference, false));
            $eventBus->fire(new TrackingInfoEvent($orderReference, false));
            if ($shipment !== null) {
                $eventBus->fire(
                    new ShipmentStatusChangedEvent($orderReference, ShipmentStatus::getStatus($shipment->status))
                );
                $orderRepository->setShippingPriceByReference($orderReference, (float)$shipment->price);
            }
        }
    }
}
