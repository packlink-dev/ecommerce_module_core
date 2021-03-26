<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
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

        return array(
            'context' => $this->getConfigService()->getContext(),
            'email' => $registrationData->getEmail(),
            'phone' => $registrationData->getPhone(),
            'source' => $registrationData->getSource(),
            'termsAndConditionsUrl' => !empty(self::$termsAndConditionsUrls[$country]) ?
                self::$termsAndConditionsUrls[$country] : self::$termsAndConditionsUrls[self::DEFAULT_COUNTRY],
            'privacyPolicyUrl' => !empty(self::$privacyPolicyUrls[$country]) ?
                self::$privacyPolicyUrls[$country] : self::$privacyPolicyUrls[self::DEFAULT_COUNTRY],
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
     */
    public function register(array $payload)
    {
        $payload['platform'] = 'PRO';
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
     * Returns shop language in format which Packlink expects, based on user's platform country.
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
