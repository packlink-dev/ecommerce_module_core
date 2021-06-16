<?php

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\CountryLabels\Interfaces\CountryService;
use Packlink\DemoUI\Bootstrap;
use Packlink\DemoUI\Services\Integration\UrlService;

require_once __DIR__ . '/../../../vendor/autoload.php';

Bootstrap::init();

Configuration::setUICountryCode('en');
$lang = Configuration::getUICountryCode() ?: 'en';

function getUrl($controller, $action)
{
    echo UrlService::getEndpointUrl($controller, $action);
}

function getTranslations($language)
{
    /** @var CountryService $countryService */
    $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);

    $labels = $countryService->getAllLabels($language);
    $defaultLabels = json_encode($labels['en']);
    $currentLanguageLabels = json_encode($labels[$language]);

    echo 'default: ' . $defaultLabels . ', current: ' . $currentLanguageLabels;
}

?>
<!DOCTYPE html>
<html lang="<?php
echo $lang ?>">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo UI</title>
    <link rel="icon" href="data:;base64,iVBORwOKGO="/>
    <link rel="stylesheet" type="text/css" href="./resources/css/app.css"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined"
          rel="stylesheet">
    <style>
        /*This is just for the demo page and should not be used in any integration*/
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            background-color: #e6e6e6;
            box-sizing: border-box;
        }

        body {
            display: flex;
            padding: 20px;
        }

        @media (max-width: 768px) {
            body {
                padding: 0;
            }

            aside {
                display: none;
            }
        }
    </style>
</head>
<body>
<aside style="width: 250px">
    <a id="logout" href="<?php
    getUrl('Login', 'logout') ?>">Logout</a>
</aside>

<!-- This is a main placeholder that should be used in all integrations -->
<div id="pl-page">
    <header id="pl-main-header">
        <div class="pl-main-logo">
            <img src="https://cdn.packlink.com/apps/giger/logos/packlink-pro.svg" alt="logo">
        </div>
        <div class="pl-header-holder" id="pl-header-section"></div>
    </header>

    <main id="pl-main-page-holder"></main>

    <div class="pl-spinner pl-hidden" id="pl-spinner">
        <div></div>
    </div>

    <template id="pl-alert">
        <div class="pl-alert-wrapper">
            <div class="pl-alert">
                <span class="pl-alert-text"></span>
                <i class="material-icons">close</i>
            </div>
        </div>
    </template>

    <template id="pl-modal">
        <div id="pl-modal-mask" class="pl-modal-mask pl-hidden">
            <div class="pl-modal">
                <div class="pl-modal-close-button">
                    <i class="material-icons">close</i>
                </div>
                <div class="pl-modal-title">

                </div>
                <div class="pl-modal-body">

                </div>
                <div class="pl-modal-footer">
                </div>
            </div>
        </div>
    </template>

    <template id="pl-error-template">
        <div class="pl-error-message" data-pl-element="error">
        </div>
    </template>
</div>

<script src="./resources/js/AjaxService.js"></script>
<script src="./resources/js/TranslationService.js"></script>
<script src="./resources/js/ModalService.js"></script>
<script src="./resources/js/TemplateService.js"></script>
<script src="./resources/js/UtilityService.js"></script>
<script src="./resources/js/ValidationService.js"></script>
<script src="./resources/js/GridResizerService.js"></script>
<script src="./resources/js/ResponseService.js"></script>
<script src="./resources/js/StateController.js"></script>
<script src="./resources/js/PageControllerFactory.js"></script>

<script src="./resources/js/LoginController.js"></script>
<script src="./resources/js/RegisterModalController.js"></script>
<script src="./resources/js/RegisterController.js"></script>

<script src="./resources/js/OnboardingStateController.js"></script>
<script src="./resources/js/OnboardingWelcomeController.js"></script>
<script src="./resources/js/OnboardingOverviewController.js"></script>
<script src="./resources/js/DefaultParcelController.js"></script>
<script src="./resources/js/DefaultWarehouseController.js"></script>

<script src="./resources/js/ConfigurationController.js"></script>
<script src="./resources/js/SystemInfoController.js"></script>
<script src="./resources/js/OrderStatusMappingController.js"></script>

<script src="./resources/js/MyShippingServicesController.js"></script>
<script src="./resources/js/PickShippingServiceController.js"></script>
<script src="./resources/js/ShippingServicesRenderer.js"></script>
<script src="./resources/js/EditServiceController.js"></script>
<script src="./resources/js/SingleStorePricePolicyController.js"></script>
<script src="./resources/js/ServiceCountriesModalController.js"></script>
<script src="./resources/js/PricePolicyController.js"></script>
<script src="./resources/js/StateUUIDService.js"></script>
<script src="./resources/js/SettingsButtonService.js"></script>

<script>
    <?php
    $baseResourcesPath = __DIR__ . '/../../../../BusinessLogic/Resources/';
    ?>
    document.addEventListener(
        'DOMContentLoaded',
        () => {
            Packlink.translations = {
                <?php getTranslations($lang); ?>
            };

            const pageConfiguration = {
                'login': {
                    submit: "<?php getUrl('Login', 'login') ?>",
                    listOfCountriesUrl: "<?php getUrl('Country', 'get') ?>",
                    logoPath: "<?php echo UrlService::getResourceUrl('images/flags') ?>"
                },
                'register': {
                    getRegistrationData: "<?php getUrl('Registration', 'get') ?>",
                    submit: "<?php getUrl('Registration', 'post') ?>"
                },
                'onboarding-state': {
                    getState: "<?php getUrl('Onboarding', 'getCurrentState') ?>"
                },
                'onboarding-welcome': {},
                'onboarding-overview': {
                    defaultParcelGet: "<?php getUrl('DefaultParcel', 'getDefaultParcel') ?>",
                    defaultWarehouseGet: "<?php getUrl('DefaultWarehouse', 'getDefaultWarehouse') ?>"
                },
                'default-parcel': {
                    getUrl: "<?php getUrl('DefaultParcel', 'getDefaultParcel') ?>",
                    submitUrl: "<?php getUrl('DefaultParcel', 'setDefaultParcel') ?>"
                },
                'default-warehouse': {
                    getUrl: "<?php getUrl('DefaultWarehouse', 'getDefaultWarehouse') ?>",
                    getSupportedCountriesUrl: "<?php getUrl('DefaultWarehouse', 'getSupportedCountries') ?>",
                    submitUrl: "<?php getUrl('DefaultWarehouse', 'setDefaultWarehouse') ?>",
                    searchPostalCodesUrl: "<?php getUrl('DefaultWarehouse', 'searchPostalCodes') ?>"
                },
                'configuration': {
                    getDataUrl: "<?php getUrl('Configuration', 'getData')?>"
                },
                'system-info': {
                    getStatusUrl: "<?php getUrl('Debug', 'getStatus') ?>",
                    setStatusUrl: "<?php getUrl('Debug', 'setStatus') ?>"
                },
                'order-status-mapping': {
                    getMappingAndStatusesUrl: "<?php getUrl('OrderStatusMapping', 'getMappingAndStatuses') ?>",
                    setUrl: "<?php getUrl('OrderStatusMapping', 'setMappings') ?>"
                },
                'my-shipping-services': {
                    getServicesUrl: "<?php getUrl('ShippingMethods', 'getActive') ?>",
                    deleteServiceUrl: "<?php getUrl('ShippingMethods', 'deactivate') ?>",
                    getCurrencyDetailsUrl: "<?php getUrl('SystemInfo', 'get') ?>",
                    systemId: null
                },
                'pick-shipping-service': {
                    getServicesUrl: "<?php getUrl('ShippingMethods', 'getInactive') ?>",
                    getActiveServicesUrl: "<?php getUrl('ShippingMethods', 'getActive') ?>",
                    getTaskStatusUrl: "<?php getUrl('ShippingMethods', 'getTaskStatus') ?>",
                    startAutoConfigureUrl: "<?php getUrl('AutoConfigure', 'start') ?>",
                    disableCarriersUrl: "<?php getUrl('ShippingMethods', 'deactivate') ?>",
                    getCurrencyDetailsUrl: "<?php getUrl('SystemInfo', 'get') ?>",
                    systemId: null
                },
                'edit-service': {
                    getServiceUrl: "<?php getUrl('ShippingMethods', 'getService') ?>",
                    saveServiceUrl: "<?php getUrl('ShippingMethods', 'save') ?>",
                    getTaxClassesUrl: "<?php getUrl('ShippingMethods', 'getTaxClasses') ?>",
                    getCountriesListUrl: "<?php getUrl('Country', 'getShippingCountries') ?>",
                    getCurrencyDetailsUrl: "<?php getUrl('SystemInfo', 'get') ?>",
                    hasTaxConfiguration: true,
                    hasCountryConfiguration: true,
                    canDisplayCarrierLogos: true
                }
            };

            Packlink.state = new Packlink.StateController(
                {
                    baseResourcesUrl: "<?php echo UrlService::getResourceUrl() ?>",
                    stateUrl: "<?php getUrl('ModuleState', 'getCurrentState') ?>",
                    pageConfiguration: pageConfiguration,
                    templates: {
                        'pl-login-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/login.html')
                            ) ?>
                        },
                        'pl-register-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/register.html')
                            ) ?>
                        },
                        'pl-register-modal': <?php echo json_encode(
                            file_get_contents($baseResourcesPath . 'templates/register-modal.html')
                        ) ?>,
                        'pl-onboarding-welcome-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/onboarding-welcome.html')
                            ) ?>
                        },
                        'pl-onboarding-overview-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/onboarding-overview.html')
                            ) ?>
                        },
                        'pl-default-parcel-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/default-parcel.html')
                            ) ?>
                        },
                        'pl-default-warehouse-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/default-warehouse.html')
                            ) ?>
                        },
                        'pl-configuration-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/configuration.html')
                            ) ?>,
                            'pl-header-section': ''
                        },
                        'pl-order-status-mapping-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/order-status-mapping.html')
                            ) ?>,
                            'pl-header-section': ''
                        },
                        'pl-system-info-modal': <?php echo json_encode(
                            file_get_contents($baseResourcesPath . 'templates/system-info-modal.html')
                        ) ?>,
                        'pl-my-shipping-services-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/my-shipping-services.html')
                            ) ?>,
                            'pl-header-section': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/shipping-services-header.html')
                            ) ?>,
                            'pl-shipping-services-table': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/shipping-services-table.html')
                            ) ?>,
                            'pl-shipping-services-list': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/shipping-services-list.html')
                            ) ?>
                        },
                        'pl-disable-carriers-modal': <?php echo json_encode(
                            file_get_contents($baseResourcesPath . 'templates/disable-carriers-modal.html')
                        ) ?>,
                        'pl-pick-service-page': {
                            'pl-header-section': '',
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/pick-shipping-services.html')
                            ) ?>,
                            'pl-shipping-services-table': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/shipping-services-table.html')
                            ) ?>,
                            'pl-shipping-services-list': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/shipping-services-list.html')
                            ) ?>
                        },
                        'pl-edit-service-page': {
                            'pl-header-section': '',
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/edit-shipping-service.html')
                            ) ?>,
                            'pl-pricing-policies': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'templates/pricing-policies-list.html')
                            ) ?>
                        },
                        'pl-pricing-policy-modal': <?php echo json_encode(
                            file_get_contents($baseResourcesPath . 'templates/pricing-policy-modal.html')
                        ) ?>,
                        'pl-countries-selection-modal': <?php echo json_encode(
                            file_get_contents($baseResourcesPath . 'templates/countries-selection-modal.html')
                        ) ?>,
                    }
                }
            );

            Packlink.state.display();
        },
        false
    );
</script>
</body>
</html>