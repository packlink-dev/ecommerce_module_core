<?php

namespace Packlink\BusinessLogic\Warehouse;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Tasks\BusinessTasks\UpdateShippingServicesBusinessTask;

/**
 * Class WarehouseService.
 *
 * @package Packlink\BusinessLogic\Warehouse
 */
class WarehouseService extends BaseService implements \Packlink\BusinessLogic\Warehouse\Interfaces\WarehouseServiceInterface
{
    const CLASS_NAME = __CLASS__;

    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * @var TaskExecutorInterface
     */
    private $taskExecutor;

    public static function getInstance()
    {
        if (static::$instance === null) {
            $taskExecutor = ServiceRegister::getService(TaskExecutorInterface::CLASS_NAME);
            static::$instance = new static($taskExecutor);
        }

        if (!(static::$instance instanceof static)) {
            throw new \RuntimeException('Wrong static instance of a singleton class.');
        }

        return static::$instance;
    }

    public function __construct(TaskExecutorInterface $taskExecutor)
    {
        parent::__construct();
        $this->taskExecutor = $taskExecutor;
    }

    /**
     * Gets a warehouse.
     *
     * @param bool $createIfNotExist [optional] Indicates whether to create a new object if the default does not exist.
     *
     * @return \Packlink\BusinessLogic\Warehouse\Warehouse|null
     */
    public function getWarehouse($createIfNotExist = true)
    {
        /** @var \Packlink\BusinessLogic\Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        $warehouse = $configService->getDefaultWarehouse();
        if (!$warehouse && $createIfNotExist) {
            $userInfo = $configService->getUserInfo();
            $warehouse = new Warehouse();
            $warehouse->country = $userInfo ? $userInfo->country : '';
        }

        return $warehouse;
    }

    /**
     * Updates warehouse data.
     *
     * @param array $payload
     *
     * @return \Packlink\BusinessLogic\Warehouse\Warehouse
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function updateWarehouseData($payload)
    {
        $validationErrors = array();
        try {
            /** @var Warehouse $warehouse */
            $warehouse = FrontDtoFactory::get(Warehouse::CLASS_KEY, $payload);
        } catch (FrontDtoValidationException $e) {
            $validationErrors = $e->getValidationErrors();
        }

        $validationErrors = array_merge($validationErrors, $this->validatePostalCode($payload));
        if (!empty($validationErrors)) {
            throw new FrontDtoValidationException($validationErrors);
        }

        /** @var \Packlink\BusinessLogic\Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $taskExecutor = $this->taskExecutor;

        $oldWarehouse = $configService->getDefaultWarehouse();

        $configService->setDefaultWarehouse($warehouse);
        if ($oldWarehouse === null
            || $oldWarehouse->country !== $warehouse->country
        ) {
            $taskExecutor->enqueue(new UpdateShippingServicesBusinessTask());
        }

        return $warehouse;
    }

    /**
     * Validates postal code.
     *
     * @param array $payload
     *
     * @return ValidationError[] An array of validation errors, if any.
     *
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function validatePostalCode(array $payload): array
    {
        $validationErrors = array();

        if (!empty($payload['country']) && !empty($payload['postal_code'])) {
            $postalCodeError = array(
                'code' => ValidationError::ERROR_INVALID_FIELD,
                'field' => 'postal_code',
                'message' => 'Postal code is not correct.',
            );

            try {
                /** @var Proxy $proxy */
                $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
                $postalCodes = $proxy->getPostalCodes($payload['country'], $payload['postal_code']);
                if (empty($postalCodes)) {
                    $validationErrors[] = FrontDtoFactory::get(ValidationError::CLASS_KEY, $postalCodeError);
                }
            } catch (\Exception $e) {
                $validationErrors[] = FrontDtoFactory::get(ValidationError::CLASS_KEY, $postalCodeError);
            }
        }

        return $validationErrors;
    }
}
