<?php

use Logeecom\Infrastructure\Configuration\Configuration;
use Packlink\DemoUI\Services\Integration\UrlService;

require_once __DIR__ . '/../../vendor/autoload.php';

Configuration::setCurrentLanguage('es');
$lang = Configuration::getCurrentLanguage() ?: 'en';

?>
<!DOCTYPE html>
<html lang="<?php echo $lang ?>">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo UI</title>
    <link rel="icon" href="data:;base64,iVBORwOKGO="/>
    <link rel="stylesheet" type="text/css" href="./resources/css/app.css"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
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

        #pl-page {
            margin: 10px 10px 10px 250px;
        }

        @media (max-width: 768px) {
            #pl-page {
                margin: 0;
            }
        }
    </style>
</head>
<body>
<a style="position: absolute; left: 10px; top:10px; z-index: 1" id="logout"
   href="<?php
   echo UrlService::getEndpointUrl('Login', 'logout') ?>">Logout</a>

<!-- This is a main placeholder that should be used in all integrations -->
<div id="pl-page">
    <header>
        <div class="pl-main-logo">
            <img src="https://cdn.packlink.com/apps/giger/logos/packlink-pro.svg" alt="logo">
        </div>
        <div class="header-holder" id="pl-header-section"></div>
    </header>

    <main id="pl-main-page-holder"></main>

    <footer id="pl-footer-extension-point"></footer>

    <div class="pl-input-mask pl-hidden" id="pl-input-mask"></div>
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
<script src="./resources/js/ResponseService.js"></script>
<script src="./resources/js/FooterController.js"></script>
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

<script src="./resources/js/ShippingMethodsController.js"></script>
<script>
    <?php
    $baseResourcesPath = __DIR__ . '/../../../BusinessLogic/';
    ?>
    document.addEventListener('DOMContentLoaded', function () {
            Packlink.translations = {
                default: <?php echo file_get_contents($baseResourcesPath . 'Language/Translations/en.json') ?>,
                current: <?php $langFile = $baseResourcesPath . 'Language/Translations/' . $lang . '.json';
                echo file_exists($langFile) ? file_get_contents($langFile) : ''
                ?>,
            };

            Packlink.models = {};

            Packlink.successMsgs = {
                shippingMethodSaved: 'Shipping service successfully saved.'
            };

            Packlink.state = new Packlink.StateController(
                {
                    scrollConfiguration: {
                        rowHeight: 75,
                        scrollOffset: 0
                    },

                    hasTaxConfiguration: false,
                    hasCountryConfiguration: true,

                    stateUrl: "<?php echo UrlService::getEndpointUrl('ModuleState', 'getCurrentState') ?>",
                    loginUrl: "<?php echo UrlService::getEndpointUrl('Login', 'login') ?>",
                    listOfCountriesUrl: "<?php echo UrlService::getEndpointUrl('Country', 'get') ?>",
                    registrationDataUrl: "<?php echo UrlService::getEndpointUrl('Registration', 'get') ?>",
                    registrationSubmitUrl: "<?php echo UrlService::getEndpointUrl('Registration', 'post') ?>",
                    getOnboardingStateUrl: "<?php echo UrlService::getEndpointUrl('Onboarding', 'getCurrentState') ?>",
                    dashboardGetStatusUrl: "<?php echo UrlService::getEndpointUrl('Dashboard', 'getStatus') ?>",
                    defaultParcelGetUrl: "<?php echo UrlService::getEndpointUrl(
                        'DefaultParcel',
                        'getDefaultParcel'
                    ) ?>",
                    defaultParcelSubmitUrl: "<?php echo UrlService::getEndpointUrl(
                        'DefaultParcel',
                        'setDefaultParcel'
                    ) ?>",
                    defaultWarehouseGetUrl: "<?php echo UrlService::getEndpointUrl(
                        'DefaultWarehouse',
                        'getDefaultWarehouse'
                    ) ?>",
                    getSupportedCountriesUrl: "<?php echo UrlService::getEndpointUrl(
                        'DefaultWarehouse',
                        'getSupportedCountries'
                    ) ?>",
                    defaultWarehouseSubmitUrl: "<?php echo UrlService::getEndpointUrl(
                        'DefaultWarehouse',
                        'setDefaultWarehouse'
                    ) ?>",
                    defaultWarehouseSearchPostalCodesUrl: "<?php echo UrlService::getEndpointUrl(
                        'DefaultWarehouse',
                        'searchPostalCodes'
                    ) ?>",
                    shippingMethodsGetStatusUrl: "<?php echo UrlService::getEndpointUrl(
                        'ShippingMethods',
                        'getTaskStatus'
                    ) ?>",
                    shippingMethodsGetAllUrl: "<?php echo UrlService::getEndpointUrl('ShippingMethods', 'getAll') ?>",
                    shippingMethodsActivateUrl: "<?php echo UrlService::getEndpointUrl(
                        'ShippingMethods',
                        'activate'
                    ) ?>",
                    shippingMethodsDeactivateUrl: "<?php echo UrlService::getEndpointUrl(
                        'ShippingMethods',
                        'deactivate'
                    ) ?>",
                    shippingMethodsSaveUrl: "<?php echo UrlService::getEndpointUrl('ShippingMethods', 'save') ?>",
                    getSystemOrderStatusesUrl: "<?php echo UrlService::getEndpointUrl(
                        'OrderStateMapping',
                        'getSystemOrderStatuses'
                    ) ?>",
                    orderStatusMappingsGetUrl: "<?php echo UrlService::getEndpointUrl(
                        'OrderStateMapping',
                        'getMappings'
                    ) ?>",
                    orderStatusMappingsSaveUrl: "<?php echo UrlService::getEndpointUrl(
                        'OrderStateMapping',
                        'setMappings'
                    ) ?>",
                    debugGetStatusUrl: "<?php echo UrlService::getEndpointUrl('Debug', 'getStatus') ?>",
                    debugSetStatusUrl: "<?php echo UrlService::getEndpointUrl('Debug', 'setStatus') ?>",
                    autoConfigureStartUrl: "<?php echo UrlService::getEndpointUrl('AutoConfigure', 'start') ?>",
                    getShippingCountriesUrl: "<?php echo UrlService::getEndpointUrl('ShippingCountries', 'getAll') ?>",
                    logoPath: "<?php echo UrlService::getResourceUrl('images/flags') ?>",

                    templates: {
                        'pl-login-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'Resources/templates/login.html')
                            ) ?>
                        },
                        'pl-register-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'Resources/templates/register.html')
                            ) ?>
                        },
                        'pl-register-modal': <?php echo json_encode(
                            file_get_contents($baseResourcesPath . 'Resources/templates/registerModal.html')
                        ) ?>,
                        'pl-onboarding-welcome-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'Resources/templates/onboarding-welcome.html')
                            ) ?>
                        },
                        'pl-onboarding-overview-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'Resources/templates/onboarding-overview.html')
                            ) ?>
                        },
                        'pl-default-parcel-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'Resources/templates/default-parcel.html')
                            ) ?>
                        },
                        'pl-default-warehouse-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'Resources/templates/default-warehouse.html')
                            ) ?>
                        }
                    },
                }
            );

            Packlink.state.display();
        }, false
    );
</script>
</body>
</html>