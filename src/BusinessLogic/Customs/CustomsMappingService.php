<?php

namespace Packlink\BusinessLogic\Customs;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\Customs\Models\TaxIdOption;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\Proxy;

/**
 * Class CustomsService
 *
 * @package Packlink\BusinessLogic\Customs
 */
abstract class CustomsMappingService implements \Packlink\BusinessLogic\Customs\Interfaces\CustomsMappingServiceInterface
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
        $mappings = $this->getConfigService()->getCustomsMappings();

        if (!$mappings) {
            $user = $this->getUser();

            $mappings = new CustomsMapping();
            $mappings->defaultSenderTaxId = $user->taxId;
        }

        return $mappings;
    }

    /**
     * @return TaxIdOption[]
     */
    abstract public function getReceiverTaxIdOptions();

    /**
     * @return User|null
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    protected function getUser()
    {
        $user = $this->getConfigService()->getUserInfo();

        if (empty($user) || empty($user->taxId)) {
            $user = $this->getPacklinkProxy()->getUserData();
            $this->getConfigService()->setUserInfo($user);
        }

        return $user;
    }

    /**
     * @return Proxy
     */
    protected function getPacklinkProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * @return \Packlink\BusinessLogic\Configuration
     */
    private function getConfigService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }
}
