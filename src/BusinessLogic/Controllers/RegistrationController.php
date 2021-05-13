<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\CountryLabels\Interfaces\CountryService;
use Packlink\BusinessLogic\Registration\RegistrationInfoService;
use Packlink\BusinessLogic\Registration\RegistrationRequest;
use Packlink\BusinessLogic\Registration\RegistrationService;
use Packlink\BusinessLogic\User\UserAccountService;

class RegistrationController
{
    /**
     * string
     */
    const DEFAULT_COUNTRY = 'es';
    /**
     * @var Configuration
     */
    protected $configService;
    /**
     * @var BrandConfigurationService
     */
    protected $brandConfigurationService;
    /**
     * @var CountryService
     */
    protected $countryService;

    /**
     * Gets the data needed for a registration page.
     *
     * @param string $country
     *
     * @return array
     */
    public function getRegisterData($country)
    {
        /** @var RegistrationInfoService $registrationInfoService */
        $registrationInfoService = ServiceRegister::getService(RegistrationInfoService::CLASS_NAME);
        $registrationData = $registrationInfoService->getRegistrationInfoData();

        $brand = $this->getBrandConfigurationService()->get();

        return array(
            'context' => $this->getConfigService()->getContext(),
            'email' => $registrationData->getEmail(),
            'phone' => $registrationData->getPhone(),
            'source' => $registrationData->getSource(),
            'termsAndConditionsUrl' => $this->getCountryService()
                ->getLabel(strtolower($country), 'register.termsAndConditionsUrl', static::DEFAULT_COUNTRY),
            'privacyPolicyUrl' => $this->getCountryService()
                ->getLabel(strtolower($country), 'register.privacyPolicyUrl', static::DEFAULT_COUNTRY),
            'platform_country' => in_array($country, $brand->platformCountries, true) ?
                $country : $brand->platformCountries[0],
        );
    }

    /**
     * Registers the user to the Packlink system.
     *
     * @param array $payload
     *
     * @return bool A flag indicating whether the registration was successful.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     * @throws \Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException
     * @throws \Packlink\BusinessLogic\Brand\Exceptions\PlatformCountryNotSupportedByBrandException
     */
    public function register(array $payload)
    {
        $brand = $this->getBrandConfigurationService()->get();

        $payload['platform'] = $brand->platformCode;
        $payload['language'] = $this->getLanguage($payload['platform_country']);

        if (isset($payload['source'])) {
            $payload['source'] = 'https://' . str_replace(array('http://', 'https://'), '', $payload['source']);
        }

        $acceptedTermsAndConditions = isset($payload['terms_and_conditions']) && $payload['terms_and_conditions'];
        $acceptedMarketingEmails = isset($payload['marketing_emails']) && $payload['marketing_emails'];

        $payload['policies'] = array(
            'terms_and_conditions' => $acceptedTermsAndConditions,
            'data_processing' => $acceptedTermsAndConditions,
            'marketing_emails' => $acceptedMarketingEmails,
            'marketing_calls' => $acceptedMarketingEmails,
        );

        /** @var RegistrationRequest $request */
        $registrationRequest = FrontDtoFactory::get(RegistrationRequest::CLASS_KEY, $payload);

        /** @var RegistrationService $registrationService */
        $registrationService = ServiceRegister::getService(RegistrationService::CLASS_NAME);

        /** @noinspection PhpParamsInspection */
        $token = $registrationService->register($registrationRequest);

        if (!empty($token)) {
            /** @var UserAccountService $userAccountService */
            $userAccountService = ServiceRegister::getService(UserAccountService::CLASS_NAME);
            if ($userAccountService->login($token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an instance of configuration service.
     *
     * @return Configuration
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Returns an instance of brand configuration service.
     *
     * @return BrandConfigurationService
     */
    protected function getBrandConfigurationService()
    {
        if ($this->brandConfigurationService === null) {
            $this->brandConfigurationService = ServiceRegister::getService(BrandConfigurationService::CLASS_NAME);
        }

        return $this->brandConfigurationService;
    }

    /**
     * Returns an instance of country service.
     *
     * @return CountryService
     */
    protected function getCountryService()
    {
        if ($this->countryService === null) {
            $this->countryService = ServiceRegister::getService(CountryService::CLASS_NAME);
        }

        return $this->countryService;
    }

    /**
     * Returns shop language in format which Packlink expects.
     *
     * @param string $platformCountry
     *
     * @return string
     */
    private function getLanguage($platformCountry)
    {
        $supportedLanguages = array(
            'ES' => 'es_ES',
            'DE' => 'de_DE',
            'FR' => 'fr_FR',
            'IT' => 'it_IT',
        );

        $language = 'en_GB';

        if (array_key_exists($platformCountry, $supportedLanguages)) {
            $language = $supportedLanguages[$platformCountry];
        }

        return $language;
    }
}
