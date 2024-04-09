<?php

namespace Packlink\BusinessLogic\Customs;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;

/**
 * Class CustomsService
 *
 * @package Packlink\BusinessLogic\Customs
 */
abstract class CustomsService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Updates customs mapping.
     *
     * @param array $data
     *
     * @return void
     *
     * @throws FrontDtoValidationException
     * @throws FrontDtoNotRegisteredException
     */
    public function updateCustomsMapping(array $data)
    {
        $validationErrors = array();

        try {
            /** @var CustomsMapping $customsMapping */
            $customsMapping = FrontDtoFactory::get(CustomsMapping::CLASS_KEY, $data);
        } catch (FrontDtoValidationException $exception) {
            $validationErrors = $exception->getValidationErrors();
        }

        if (!empty($validationErrors)) {
            throw new FrontDtoValidationException($validationErrors);
        }

        $this->getConfigService()->setCustomsMappings($customsMapping);
    }

    /**
     * @return CustomsMapping|null
     */
    public function getCustomsMappings()
    {
        return $this->getConfigService()->getCustomsMappings();
    }

    /**
     * @return array
     */
    abstract public function getReceiverTaxIdOptions();

    /**
     * @return \Packlink\BusinessLogic\Configuration
     */
    private function getConfigService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }
}
