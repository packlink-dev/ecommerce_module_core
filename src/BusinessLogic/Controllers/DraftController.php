<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\Order\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\Tasks\SendDraftTask;

/**
 * Class DraftController
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class DraftController
{
    /**
     * Order details repository instance.
     *
     * @var RepositoryInterface $orderDetailsRepository order details repository.
     */
    protected static $orderDetailsRepository;
    /**
     * QueueService instance.
     *
     * @var QueueService $queue Queue Service.
     */
    protected static $queue;
    /**
     * Configruation instance.
     *
     * @var Configuration $configService Configuration Service.
     */
    protected static $configService;

    /**
     * Creates draft task for provided shop order id.
     *
     * @param string $orderId Shop order id.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public static function createDraft($orderId)
    {
        $orderDetails = new OrderShipmentDetails();
        $orderDetails->setOrderId($orderId);
        static::getOrderDetailsRepository()->save($orderDetails);

        $configService = static::getConfigService();
        $draftTask = new SendDraftTask($orderId);
        static::getQueue()->enqueue($configService->getDefaultQueueName(), $draftTask, $configService->getContext());

        if ($draftTask->getExecutionId() !== null) {
            // get again from database since it can happen that task already finished and
            // reference has been set, so we don't delete it here.
            $orderDetails = static::getOrderDetailsByOrderId($orderId);
            if ($orderDetails !== null) {
                $orderDetails->setTaskId($draftTask->getExecutionId());
                static::getOrderDetailsRepository()->update($orderDetails);
            }
        }
    }

    /**
     * Retrieves order details by order id.
     *
     * @param string $orderId Shop order id.
     *
     * @return OrderShipmentDetails | null Order Details instance.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected static function getOrderDetailsByOrderId($orderId)
    {
        $filter = new QueryFilter();
        $filter->where('orderId', Operators::EQUALS, $orderId);

        /** @var OrderShipmentDetails $details */
        $details = static::getOrderDetailsRepository()->selectOne($filter);

        return $details;
    }

    /**
     * Retrieves order details repository.
     *
     * @return RepositoryInterface Order details repository instance.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected static function getOrderDetailsRepository()
    {
        if (static::$orderDetailsRepository === null) {
            static::$orderDetailsRepository = RepositoryRegistry::getRepository(OrderShipmentDetails::getClassName());
        }

        return static::$orderDetailsRepository;
    }

    /**
     * Retrieves queue service.
     *
     * @return QueueService Queue Service instance.
     */
    protected static function getQueue()
    {
        if (static::$queue === null) {
            static::$queue = ServiceRegister::getService(QueueService::CLASS_NAME);
        }

        return static::$queue;
    }

    /**
     * Retrieves configuration service.
     *
     * @return \Logeecom\Infrastructure\Configuration\Configuration Configuration Service instance.
     */
    protected static function getConfigService()
    {
        if (static::$configService === null) {
            static::$configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return static::$configService;
    }
}