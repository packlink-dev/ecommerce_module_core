<?php

namespace Packlink\BusinessLogic\Warehouse;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\Http\Proxy;

/**
 * Class WarehouseService.
 *
 * @package Packlink\BusinessLogic\Warehouse
 */
class WarehouseService extends BaseService
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
     * Saves warehouse data.
     *
     * @param array $payload
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function setWarehouse(array $payload)
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
        $configService->setDefaultWarehouse($warehouse);
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
    protected function validatePostalCode(array $payload)
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