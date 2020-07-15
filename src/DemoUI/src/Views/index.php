<?php

use Logeecom\Infrastructure\Configuration\Configuration;
use Packlink\DemoUI\Services\Integration\UrlService;

require_once __DIR__ . '/../../vendor/autoload.php';

Configuration::setCurrentLanguage('es');
$lang = Configuration::getCurrentLanguage() ?: 'en';

function getUrl($controller, $action)
{
    echo UrlService::getEndpointUrl($controller, $action);
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
        }

        @media (max-width: 768px) {
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

<script src="./resources/js/ConfigurationController.js"></script>
<script src="./resources/js/SystemInfoController.js"></script>
<script src="./resources/js/OrderStatusMappingController.js"></script>

<script src="./resources/js/ShippingMethodsController.js"></script>
<!--suppress JSCheckFunctionSignatures -->
<script>
    <?php
    $baseResourcesPath = __DIR__ . '/../../../BusinessLogic/Resources/';
    ?>
    document.addEventListener('DOMContentLoaded', () => {
        Packlink.translations = {
            default: <?php echo file_get_contents($baseResourcesPath . 'lang/en.json') ?>,
            current: <?php $langFile = $baseResourcesPath . 'lang/' . $lang . '.json';
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
                logoPath: "<?php echo UrlService::getResourceUrl('images/flags') ?>",

                stateUrl: "<?php getUrl('ModuleState', 'getCurrentState') ?>",

                // login and register
                loginUrl: "<?php getUrl('Login', 'login') ?>",
                listOfCountriesUrl: "<?php getUrl('Country', 'get') ?>",
                registrationDataUrl: "<?php getUrl('Registration', 'get') ?>",
                registrationSubmitUrl: "<?php getUrl('Registration', 'post') ?>",

                // onboarding
                getOnboardingStateUrl: "<?php getUrl('Onboarding', 'getCurrentState') ?>",

                // parcel
                defaultParcelGetUrl: "<?php getUrl('DefaultParcel', 'getDefaultParcel') ?>",
                defaultParcelSubmitUrl: "<?php getUrl('DefaultParcel', 'setDefaultParcel') ?>",

                // warehouse
                defaultWarehouseGetUrl: "<?php getUrl('DefaultWarehouse', 'getDefaultWarehouse') ?>",
                getSupportedCountriesUrl: "<?php getUrl('DefaultWarehouse', 'getSupportedCountries') ?>",
                defaultWarehouseSubmitUrl: "<?php getUrl('DefaultWarehouse', 'setDefaultWarehouse') ?>",
                defaultWarehouseSearchPostalCodesUrl: "<?php getUrl('DefaultWarehouse', 'searchPostalCodes') ?>",

                // configuration
                configurationGetDataUrl: "<?php getUrl('Configuration', 'getData')?>",

                // system info
                debugGetStatusUrl: "<?php getUrl('Debug', 'getStatus') ?>",
                debugSetStatusUrl: "<?php getUrl('Debug', 'setStatus') ?>",

                // order status mapping
                orderStatusMappingsGetMappingsAndStatusesUrl: "<?php getUrl(
                    'OrderStatusMapping',
                    'getMappingAndStatuses'
                ) ?>",
                orderStatusMappingsSaveUrl: "<?php getUrl('OrderStatusMapping', 'setMappings') ?>",

                // shipping services
                dashboardGetStatusUrl: "<?php getUrl('Dashboard', 'getStatus') ?>",
                shippingMethodsGetStatusUrl: "<?php getUrl('ShippingMethods', 'getTaskStatus') ?>",
                shippingMethodsGetAllUrl: "<?php getUrl('ShippingMethods', 'getAll') ?>",
                shippingMethodsActivateUrl: "<?php getUrl('ShippingMethods', 'activate') ?>",
                shippingMethodsDeactivateUrl: "<?php getUrl('ShippingMethods', 'deactivate') ?>",
                shippingMethodsSaveUrl: "<?php getUrl('ShippingMethods', 'save') ?>",
                autoConfigureStartUrl: "<?php getUrl('AutoConfigure', 'start') ?>",
                getShippingCountriesUrl: "<?php getUrl('ShippingCountries', 'getAll') ?>",

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
                    'pl-shipping-methods-page': {
                        'pl-main-page-holder': '',
                        'pl-header-section': <?php echo json_encode(
                            file_get_contents($baseResourcesPath . 'templates/shipping-methods-header.html')
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