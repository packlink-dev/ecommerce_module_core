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
    );
    /**
     * List of terms and conditions URLs for different country codes.
     *
     * @var array
     */
    private static $privacyPolicyUrls = array(
        'EN' => 'https://support-pro.packlink.com/hc/en-gb/articles/360010011560',
        'ES' => 'https://support-pro.packlink.com/hc/en-gb/articles/360010011560',
        'DE' => 'https://support-pro.packlink.com/hc/de/articles/360010011560',
        'FR' => 'https://support-pro.packlink.com/hc/en-gb/articles/360010011560',
        'IT' => 'https://support-pro.packlink.com/hc/it/articles/360010011560',
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
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     * @throws \Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException
     */
    public function register(array $payload)
    {
        $payload['platform'] = 'PRO';
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

        $locale = Configuration::getCurrentLanguage();
        $language = 'en_GB';

        if (array_key_exists($locale, $supportedLanguages)) {
            $language = $supportedLanguages[$locale];
        }

        return $language;
    }
}