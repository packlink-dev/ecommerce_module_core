<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\Registration\RegistrationInfoService;
use Packlink\BusinessLogic\Registration\RegistrationRequest;
use Packlink\BusinessLogic\Registration\RegistrationService;
use Packlink\BusinessLogic\User\UserAccountService;

class RegistrationController
{
    /**
     * string
     */
    const DEFAULT_COUNTRY = 'ES';
    /**
     * @var Configuration
     */
    protected $configService;
    /**
     * @var BrandConfigurationService
     */
    protected $brandConfigurationService;
    /**
     * List of terms and conditions URLs for different country codes.
     *
     * @var array
     */
    private static $termsAndConditionsUrls = array(
        'EN' => 'https://support-pro.packlink.com/hc/en-gb/articles/360010011480',
        'ES' => 'https://pro.packlink.es/terminos-y-condiciones/',
        'DE' => 'https://pro.packlink.de/agb/',
        'FR' => 'https://pro.packlink.fr/conditions-generales/',
        'IT' => 'https://pro.packlink.it/termini-condizioni/',
        'AT' => 'https://support-pro.packlink.com/hc/de/articles/360010011480',
        'NL' => 'https://support-pro.packlink.com/hc/nl/articles/360010011480',
        'BE' => 'https://support-pro.packlink.com/hc/nl/articles/360010011480',
        'PT' => 'https://support-pro.packlink.com/hc/pt/articles/360010011480',
        'TR' => 'https://support-pro.packlink.com/hc/tr/articles/360010011480',
        'IE' => 'https://support-pro.packlink.com/hc/en-gb/articles/360010011480',
        'GB' => 'https://support-pro.packlink.com/hc/en-gb/articles/360010011480',
        'HU' => 'https://support-pro.packlink.com/hc/hu/articles/360010011480',
    );
    /**
     * List of terms and conditions URLs for different country codes.
     *
     * @var array
     */
    private static $privacyPolicyUrls = array(
        'EN' => 'https://support-pro.packlink.com/hc/en-gb/articles/360010011560',
        'ES' => 'https://support-pro.packlink.com/hc/es-es/articles/360010011560-Pol%C3%ADtica-de-Privacidad',
        'DE' => 'https://support-pro.packlink.com/hc/de/articles/360010011560-Datenschutzerkl%C3%A4rung-der-Packlink-Shipping-S-L-',
        'FR' => 'https://support-pro.packlink.com/hc/fr-fr/articles/360010011560-Politique-de-confidentialit%C3%A9',
        'IT' => 'https://support-pro.packlink.com/hc/it/articles/360010011560-Politica-di-Privacy',
        'AT' => 'https://support-pro.packlink.com/hc/de/articles/360010011480',
        'NL' => 'https://support-pro.packlink.com/hc/nl/articles/360010011560',
        'BE' => 'https://support-pro.packlink.com/hc/nl/articles/360010011560',
        'PT' => 'https://support-pro.packlink.com/hc/pt/articles/360010011560',
        'TR' => 'https://support-pro.packlink.com/hc/tr/articles/360010011560',
        'IE' => 'https://support-pro.packlink.com/hc/en-gb/articles/360010011560',
        'GB' => 'https://support-pro.packlink.com/hc/en-gb/articles/360010011560',
        'HU' => 'https://support-pro.packlink.com/hc/hu/articles/360010011560',
    );

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
            'termsAndConditionsUrl' => !empty(self::$termsAndConditionsUrls[$country]) ?
                self::$termsAndConditionsUrls[$country] : self::$termsAndConditionsUrls[self::DEFAULT_COUNTRY],
            'privacyPolicyUrl' => !empty(self::$privacyPolicyUrls[$country]) ?
                self::$privacyPolicyUrls[$country] : self::$privacyPolicyUrls[self::DEFAULT_COUNTRY],
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
        $payload['language'] = $this->getLanguage();

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
     * Returns shop language in format which Packlink expects.
     *
     * @return string
     */
    private function getLanguage()
    {
        $supportedLanguages = array(
            'en' => 'en_GB',
            'es' => 'es_ES',
            'de' => 'de_DE',
            'fr' => 'fr_FR',
            'it' => 'it_IT',
        );

        $locale = Configuration::getUICountryCode();
        $language = 'en_GB';

        if (array_key_exists($locale, $supportedLanguages)) {
            $language = $supportedLanguages[$locale];
        }

        return $language;
    }
}
