<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Logeecom\Infrastructure\Configuration\Configuration;
use Packlink\BusinessLogic\DTO\FrontDto;

class RegistrationRequest extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * User email.
     *
     * @var string
     */
    public $email;
    /**
     * User password.
     *
     * @var string
     */
    public $password;
    /**
     * Estimated delivery volume.
     *
     * Can be one of the following: "1 - 10", "11 - 50", "51 - 100", "101 - 200", "> 200"
     *
     * @var string
     */
    public $estimatedDeliveryVolume;
    /**
     * User phone number.
     *
     * @var string
     */
    public $phone;
    /**
     * Full language code (de_DE, es_ES...).
     *
     * @var string
     */
    public $language;
    /**
     * Platform (only supported platform is "PRO").
     *
     * @var string
     */
    public $platform;
    /**
     * Country based on the platform country map.
     *
     * @var string
     */
    public $platformCountry;
    /**
     * Property based on the legal policy selection box.
     *
     * @var array
     */
    public $policies;
    /**
     * Shop/integration base URL.
     *
     * @var string
     */
    public $source;
    /**
     * Selected online store ("Shopify" | "PrestaShop" etc).
     *
     * @var array
     */
    public $ecommerces;
    /**
     * Selected online marketplace ("eBay" | "Amazon" etc).
     *
     * @var array
     */
    public $marketplaces;

    /**
     * @var boolean
     */
    public $termsAndConditions;

    /**
     * @var boolean
     */
    public $marketingCalls;

    /**
     * Fields for this DTO.
     *
     * @var array
     */
    protected static $fields = array(
        'email',
        'password',
        'estimated_delivery_volume',
        'phone',
        'language',
        'platform',
        'platform_country',
        'policies',
        'source',
        'ecommerces',
        'marketplaces',
    );

    /**
     * RegistrationRequest constructor.
     * @param string $email
     * @param string $password
     * @param string $estimatedDeliveryVolume
     * @param string $phone
     * @param string $platformCountry
     * @param string $source
     * @param array $ecommerces
     * @param array $marketplaces
     * @param bool $termsAndConditions
     * @param bool $marketingCalls
     */
    public function __construct(
        $email,
        $password,
        $estimatedDeliveryVolume,
        $phone,
        $platformCountry,
        $source,
        array $ecommerces,
        array $marketplaces,
        $termsAndConditions,
        $marketingCalls
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->estimatedDeliveryVolume = $estimatedDeliveryVolume;
        $this->phone = $phone;
        $this->language = $this->getLanguage();
        $this->platform = 'PRO';
        $this->platformCountry = $platformCountry;
        $this->policies = array();
        $this->source = 'https://' . $source;
        $this->ecommerces = $ecommerces;
        $this->marketplaces = $marketplaces;
        $this->termsAndConditions = $termsAndConditions;
        $this->marketingCalls = $marketingCalls;
    }

    /**
     * Transforms DTO to its array format.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            array(
                'platform' => 'PRO',
                'language' => $this->language,
                'estimated_delivery_volume' => $this->estimatedDeliveryVolume,
                'platform_country' => $this->platformCountry,
                'policies' => array(
                    'data_processing' => $this->termsAndConditions ? true : false,
                    'terms_and_conditions' =>  $this->termsAndConditions ? true : false,
                    'marketing_emails' =>  $this->marketingCalls ? true : false,
                    'marketing_calls' => $this->marketingCalls ? true : false,
                ),
            )
        );
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
