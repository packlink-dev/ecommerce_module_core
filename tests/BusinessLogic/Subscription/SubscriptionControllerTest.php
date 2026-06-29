<?php

namespace Logeecom\Tests\BusinessLogic\Subscription;

use Logeecom\Infrastructure\Configuration\Configuration as InfrastructureConfiguration;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Controllers\DTO\PromotionalBannerResponse;
use Packlink\BusinessLogic\Controllers\DTO\SubscriptionPlanResponse;
use Packlink\BusinessLogic\Controllers\SubscriptionController;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\Subscription\SubscriptionService;

/**
 * Class SubscriptionControllerTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Subscription
 */
class SubscriptionControllerTest extends BaseTestWithServices
{
    /**
     * @before
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     */
    public function before()
    {
        parent::before();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(ShippingMethod::CLASS_NAME, MemoryRepository::getClassName());

        TestServiceRegister::registerService(
            SubscriptionService::CLASS_NAME,
            function () {
                return SubscriptionService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            SubscriptionController::CLASS_NAME,
            function () {
                return new SubscriptionController();
            }
        );
    }

    /**
     * @after
     */
    public function after()
    {
        SubscriptionService::resetInstance();
        InfrastructureConfiguration::setUICountryCode(null);
        parent::after();
    }

    public function testGetPlanSuccess()
    {
        $this->mockSubscriptionResponses(array($this->subscriptionPayload('Free')));

        $response = $this->getController()->getPlan();

        self::assertInstanceOf(SubscriptionPlanResponse::class, $response);
        self::assertSame('FREE', $response->planTier);
        self::assertSame('Free', $response->planName);
    }

    public function testGetPlanApiFailure()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(500, array(), '{"message":"err"}')));

        $response = $this->getController()->getPlan();

        self::assertInstanceOf(SubscriptionPlanResponse::class, $response);
        self::assertNull($response->planTier);
        self::assertNull($response->planName);
    }

    public function testGetPlanPremium()
    {
        $this->mockSubscriptionResponses(array($this->subscriptionPayload('Premium')));

        $response = $this->getController()->getPlan();

        self::assertSame('PREMIUM', $response->planTier);
        self::assertSame('Premium', $response->planName);
    }

    public function testGetPromotionalBannerFreeES()
    {
        $this->setMerchantCountry('ES');
        InfrastructureConfiguration::setUICountryCode('es');

        $this->mockSubscriptionResponses(array($this->subscriptionPayload('Free')));

        $response = $this->getController()->getPromotionalBanner();

        self::assertInstanceOf(PromotionalBannerResponse::class, $response);
        self::assertSame('FREE', $response->planTier);
        // Banner text is resolved on the frontend (CDN -> baked-in); the server only
        // provides the generic fallback label.
        self::assertSame(SubscriptionController::GENERIC_BANNER_LABEL, $response->bannerLabel);
    }

    public function testGetPromotionalBannerPremiumReturnsEmpty()
    {
        $this->setMerchantCountry('ES');
        $this->mockSubscriptionResponses(array($this->subscriptionPayload('Premium')));

        $response = $this->getController()->getPromotionalBanner();

        self::assertSame('PREMIUM', $response->planTier);
        self::assertNull($response->bannerLabel);
        self::assertNull($response->upgradeUrl);
    }

    public function testGetPromotionalBannerApiFailure()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(500, array(), '{"message":"err"}')));

        $response = $this->getController()->getPromotionalBanner();

        self::assertNull($response->planTier);
        self::assertNull($response->bannerLabel);
        self::assertNull($response->upgradeUrl);
    }

    public function testGetPromotionalBannerUnknownCountry()
    {
        $this->setMerchantCountry('NL');
        $this->mockSubscriptionResponses(array($this->subscriptionPayload('Free')));

        $response = $this->getController()->getPromotionalBanner();

        self::assertSame('FREE', $response->planTier);
        self::assertSame(SubscriptionController::GENERIC_BANNER_LABEL, $response->bannerLabel);
        self::assertSame('https://pro.packlink.com/private/subscriptions', $response->upgradeUrl);
    }

    public function testGetPromotionalBannerLabelIsAlwaysGenericFallback()
    {
        // The server no longer builds a per-carrier/price label; it always returns the
        // generic fallback regardless of country/language.
        $this->setMerchantCountry('ES');
        InfrastructureConfiguration::setUICountryCode('de');

        $this->mockSubscriptionResponses(array($this->subscriptionPayload('Free')));

        $response = $this->getController()->getPromotionalBanner();

        self::assertSame(SubscriptionController::GENERIC_BANNER_LABEL, $response->bannerLabel);
    }

    public function testGetPromotionalBannerExposesLanguageAndPlatform()
    {
        // language (=> CDN locale folder) from the UI, platform (=> market suffix) from the account.
        $this->setMerchantCountry('ES');
        InfrastructureConfiguration::setUICountryCode('es');

        $servicesResponse = $this->servicesPayload(array(
            array('carrier_name' => 'Correos Standard', 'total_price' => 4.50),
            array('carrier_name' => 'SEUR Express', 'total_price' => 5.75),
        ));
        $this->mockSubscriptionResponses(array(
            $this->subscriptionPayload('Free'),
            $servicesResponse,
        ));

        $response = $this->getController()->getPromotionalBanner();

        self::assertSame('es', $response->language);
        self::assertSame('es', $response->platform);
    }

    public function testGetPromotionalBannerLanguageFromUiPlatformFromAccount()
    {
        // FR account browsing in Spanish: language=es (locale es-ES), platform=fr
        // => the frontend composes .../pro/es-ES/packlink_pro_fr.json.
        $this->setMerchantCountry('FR');
        InfrastructureConfiguration::setUICountryCode('es');

        $servicesResponse = $this->servicesPayload(array(
            array('carrier_name' => 'Colissimo', 'total_price' => 6.10),
            array('carrier_name' => 'Mondial Relay', 'total_price' => 4.20),
        ));
        $this->mockSubscriptionResponses(array(
            $this->subscriptionPayload('Free'),
            $servicesResponse,
        ));

        $response = $this->getController()->getPromotionalBanner();

        self::assertSame('es', $response->language);
        self::assertSame('fr', $response->platform);
    }

    public function testGetPromotionalBannerEnglishUiKeepsAccountPlatform()
    {
        $this->setMerchantCountry('ES');
        InfrastructureConfiguration::setUICountryCode('en');

        $servicesResponse = $this->servicesPayload(array(
            array('carrier_name' => 'Correos Standard', 'total_price' => 4.50),
            array('carrier_name' => 'SEUR Express', 'total_price' => 5.75),
        ));
        $this->mockSubscriptionResponses(array(
            $this->subscriptionPayload('Free'),
            $servicesResponse,
        ));

        $response = $this->getController()->getPromotionalBanner();

        self::assertSame('en', $response->language);
        self::assertSame('es', $response->platform);
    }

    public function testGetPromotionalBannerUpgradeUrl()
    {
        $this->setMerchantCountry('FR');
        InfrastructureConfiguration::setUICountryCode('fr');

        $this->mockSubscriptionResponses(array($this->subscriptionPayload('Plus')));

        $response = $this->getController()->getPromotionalBanner();

        self::assertSame('https://pro.packlink.fr/private/subscriptions', $response->upgradeUrl);
    }

    /**
     * @param string $country Two-letter country code.
     */
    private function setMerchantCountry($country)
    {
        $user = new User();
        $user->firstName = 'Test';
        $user->lastName = 'Merchant';
        $user->email = 'test@example.com';
        $user->country = $country;
        $user->customerType = 'business';
        $user->taxId = '';

        $this->shopConfig->setUserInfo($user);
    }

    /**
     * @param string $planName
     *
     * @return string JSON payload.
     */
    private function subscriptionPayload($planName)
    {
        return json_encode(array(
            'id' => 'sub_1',
            'client_id' => 'client_1',
            'activated_at' => '2025-01-01T00:00:00Z',
            'current_billing_currency' => 'EUR',
            'current_billing_amount' => 0,
            'plan' => array(
                'id' => 'plan_1',
                'code' => 'code_1',
                'name' => $planName,
            ),
        ));
    }

    /**
     * @param array $services Array of ['carrier_name' => string, 'total_price' => float] rows.
     *
     * @return string JSON payload mimicking GET /v1/services response.
     */
    private function servicesPayload(array $services)
    {
        $payload = array();
        foreach ($services as $service) {
            $payload[] = array(
                'carrier_name' => $service['carrier_name'],
                'service_id' => 1,
                'service_name' => $service['carrier_name'],
                'service_logo' => '',
                'transit_time' => '1 day',
                'transit_hours' => 24,
                'first_estimated_delivery_date' => '2025-01-02',
                'price' => array(
                    'tax_price' => 0,
                    'base_price' => $service['total_price'],
                    'total_price' => $service['total_price'],
                ),
                'national' => true,
                'departure_country' => 'ES',
                'destination_country' => 'ES',
            );
        }

        return json_encode($payload);
    }

    /**
     * @param string[] $bodies Sequence of JSON response bodies.
     */
    private function mockSubscriptionResponses(array $bodies)
    {
        $responses = array();
        foreach ($bodies as $body) {
            $responses[] = new HttpResponse(200, array(), $body);
        }

        $this->httpClient->setMockResponses($responses);
    }

    /**
     * @return SubscriptionController
     */
    private function getController()
    {
        /** @var SubscriptionController $controller */
        $controller = TestServiceRegister::getService(SubscriptionController::CLASS_NAME);

        return $controller;
    }
}
