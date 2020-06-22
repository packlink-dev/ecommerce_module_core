<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Controllers\DTO\RegistrationRequest;
use Packlink\BusinessLogic\Controllers\DTO\RegistrationResponse;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException;
use Packlink\BusinessLogic\Registration\RegistrationInfoService;
use Packlink\BusinessLogic\Registration\RegistrationRequest as RegistrationRequestDTO;
use Packlink\BusinessLogic\Registration\RegistrationService;
use Packlink\BusinessLogic\User\UserAccountService;

class RegistrationController
{
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
     * @return RegistrationResponse
     *
     * @throws FrontDtoValidationException
     */
    public function getRegisterData()
    {
        $rawResponse = $this->getRawRegisterDataResponse();

        return RegistrationResponse::fromArray($rawResponse);
    }

    /**
     * Registers the user to the Packlink system.
     *
     * @param RegistrationRequest $payload
     *
     * @return bool, flag indicating whether the registration was successful.
     *
     * @throws UnableToRegisterAccountException
     */
    public function register(RegistrationRequest $payload)
    {
        /** @var RegistrationRequestDTO $request */
        $registrationRequest = FrontDtoFactory::get(RegistrationRequestDTO::CLASS_KEY, $payload->toArray());

        /** @var RegistrationService $registrationService */
        $registrationService = ServiceRegister::getService(RegistrationService::CLASS_NAME);

        /** @noinspection PhpParamsInspection */
        $token = $registrationService->register($registrationRequest);

        if (!empty($token)) {
            $userAccountService = ServiceRegister::getService(UserAccountService::CLASS_NAME);
            if ($userAccountService->login($token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns base response.
     *
     * @return array
     */
    private function getRawRegisterDataResponse()
    {
        /** @var RegistrationInfoService $registrationInfoService */
        $registrationInfoService = ServiceRegister::getService(RegistrationInfoService::CLASS_NAME);
        $registrationData = $registrationInfoService->getRegistrationInfoData();

        return array(
            'context' => $this->getConfigService()->getContext(),
            'email' => $registrationData->getEmail(),
            'phone' => $registrationData->getPhone(),
            'source' => $registrationData->getSource(),
            'termsAndConditionsUrl' => $this->getTermsAndConditionsUrl(),
            'privacyPolicyUrl' => $this->getPrivacyPolicyUrl(),
        );
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
     * Returns URL for Packlink terms and conditions in user's preferred language.
     *
     * @return string
     */
    private function getTermsAndConditionsUrl()
    {
        $locale = $this->getUrlLocaleKey();

        return self::$termsAndConditionsUrls[$locale];
    }

    /**
     * Returns URL for Packlink privacy policy in user's preferred language.
     *
     * @return string
     */
    private function getPrivacyPolicyUrl()
    {
        $locale = $this->getUrlLocaleKey();

        return self::$privacyPolicyUrls[$locale];
    }

    /**
     * Returns locale for support URLs.
     *
     * @return string
     */
    private function getUrlLocaleKey()
    {
        $locale = 'EN';

        $userInfo = $this->getConfigService()->getUserInfo();
        $currentLang = $this->getConfigService()->getCurrentLanguage();

        if ($userInfo !== null && in_array($userInfo->country, array('ES', 'DE', 'FR', 'IT'), true)) {
            $locale = $userInfo->country;
        } else if (in_array(strtoupper($currentLang), array('ES', 'DE', 'FR', 'IT'), true)) {
            $locale = strtoupper($currentLang);
        }

        return $locale;
    }
}
