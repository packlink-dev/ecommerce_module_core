<?php

namespace Packlink\BusinessLogic\OrderShipmentDetails;

use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Order\Models\OrderShipmentDetails;

/**
 * Class OrderShipmentDetailsService
 *
 * @package Packlink\BusinessLogic\OrderShipmentDetails
 */
class OrderShipmentDetailsService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * Order shipment details repository.
     *
     * @var RepositoryInterface
     */
    protected $orderShipmentDetailsRepository;

    /**
     * OrderShipmentDetailsService constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->orderShipmentDetailsRepository = $this->getRepository(OrderShipmentDetails::getClassName());
    }

    /**
     * Retrieves order shipment details.
     *
     * @param string | int $orderId Order id in an integration system.
     *
     * @return OrderShipmentDetails | null Instance that retrieved by the specifed id.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function getDetailsByOrderId($orderId)
    {
        $query = new QueryFilter();
        $query->where('orderId', Operators::EQUALS, $orderId);

        /** @var OrderShipmentDetails | null $entity */
        $entity = $this->orderShipmentDetailsRepository->selectOne($query);

        return $entity;
    }
}