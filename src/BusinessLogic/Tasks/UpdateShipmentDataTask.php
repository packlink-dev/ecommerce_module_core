<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Task;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\Order\OrderService;

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
        /** @var OrderService $orderService */
        $orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        $orderReferences = $orderRepository->getIncompleteOrderReferences();

        foreach ($orderReferences as $orderReference) {
            $shipment = $proxy->getShipment($orderReference);
            if ($shipment !== null) {
                $orderService->updateShipmentLabel($orderReference);
                $orderService->updateTrackingInfo($orderReference, $shipment);
                $orderService->updateShippingStatus($orderReference, $shipment->status, $shipment);
                $orderRepository->setShippingPriceByReference($orderReference, (float)$shipment->price);
            }
        }
    }
}
