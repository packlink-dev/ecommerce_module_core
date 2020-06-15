<?php

use Logeecom\Infrastructure\Configuration\Configuration;
use Packlink\DemoUI\Services\Integration\UrlService;

require_once __DIR__ . '/../../vendor/autoload.php';

session_start();

Configuration::setCurrentLanguage('es');
$lang = Configuration::getCurrentLanguage() ?: 'en';

?>
<!DOCTYPE html>
<html lang="<?php echo $lang ?>">
<head>
    <title>Demo UI</title>
    <link rel="icon" href="data:;base64,iVBORwOKGO="/>
    <link rel="stylesheet" type="text/css" href="./resources/css/app.css"/>
    <style>
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            background-color: #e6e6e6;
            box-sizing: border-box;
        }

        body {
            padding: 10px 10px 10px 250px;
        }

        @media (max-width: 768px) {
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
<div id="pl-page">
    <div class="pl-input-mask" id="pl-input-mask"></div>
    <div class="pl-spinner" id="pl-spinner">
        <div></div>
    </div>
    <header>
        <div class="pl-main-logo">
            <img src="https://cdn.packlink.com/apps/giger/logos/packlink-pro.svg" alt="logo">
        </div>
        <div class="header-holder" id="pl-header-section"></div>
    </header>
    <main id="pl-main-page-holder">
    </main>

    <div id="pl-footer-extension-point"></div>
</div>
<script src="./resources/js/AjaxService.js"></script>
<script src="./resources/js/TranslationService.js"></script>
<script src="./resources/js/DefaultParcelController.js"></script>
<script src="./resources/js/DefaultWarehouseController.js"></script>
<script src="./resources/js/FooterController.js"></script>
<script src="./resources/js/OrderStateMappingController.js"></script>
<script src="./resources/js/PageControllerFactory.js"></script>
<script src="./resources/js/ShippingMethodsController.js"></script>
<script src="./resources/js/StateController.js"></script>
<script src="./resources/js/TemplateService.js"></script>
<script src="./resources/js/UtilityService.js"></script>
<script src="./resources/js/CountrySelectorController.js"></script>
<script src="./resources/js/LoginController.js"></script>
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
            Packlink.errorMsgs = {
                required: 'This field is required.',
                numeric: 'Value must be valid number.',
                invalid: 'This field is not valid.',
                phone: 'This field must be valid phone number.',
                titleLength: 'Title can have at most 64 characters.',
                greaterThanZero: 'Value must be greater than 0.',
                numberOfDecimalPlaces: 'Field must have 2 decimal places.',
                integer: 'Field must be an integer.',
                invalidCountryList: 'You must select destination countries.'
            };

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

                    templates: {
                        'pl-login-page': {
                            'pl-main-page-holder': <?php echo json_encode(
                                file_get_contents($baseResourcesPath . 'Resources/templates/login.html')
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