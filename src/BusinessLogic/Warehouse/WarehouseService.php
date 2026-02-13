<?php

namespace Packlink\BusinessLogic\Warehouse;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface;
use Packlink\BusinessLogic\Warehouse\Interfaces\WarehouseServiceInterface;

/**
 * Class WarehouseService.
 *
 * @package Packlink\BusinessLogic\Warehouse
 */
class WarehouseService implements WarehouseServiceInterface
{
    /**
     * @var UpdateShippingServicesOrchestratorInterface
     */
    private $orchestrator;

    public function __construct(UpdateShippingServicesOrchestratorInterface $orchestrator)
    {
        $this->orchestrator = $orchestrator;
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

        $oldWarehouse = $configService->getDefaultWarehouse();

        $configService->setDefaultWarehouse($warehouse);
        if ($oldWarehouse === null
            || $oldWarehouse->country !== $warehouse->country
        ) {
            $this->orchestrator->enqueue($configService->getContext());
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
