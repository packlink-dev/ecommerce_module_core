<html>
<head>
</head>
<body>
<div class="container-fluid pl-main-wrapper" id="pl-main-page-holder">Main page</div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
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

                dashboardGetStatusUrl: "<?php echo $block->getControllerUrl('Dashboard', 'getStatus'); ?>",
                defaultParcelGetUrl: "<?php echo $block->getControllerUrl('DefaultParcel', 'getDefaultParcel'); ?>",
                defaultParcelSubmitUrl: "<?php echo $block->getControllerUrl('DefaultParcel', 'setDefaultParcel'); ?>",
                defaultWarehouseGetUrl: "<?php echo $block->getControllerUrl(
                    'DefaultWarehouse',
                    'getDefaultWarehouse'
                ); ?>",
                getSupportedCountriesUrl: "<?php echo $block->getControllerUrl(
                    'DefaultWarehouse',
                    'getSupportedCountries'
                ); ?>",
                defaultWarehouseSubmitUrl: "<?php echo $block->getControllerUrl(
                    'DefaultWarehouse',
                    'setDefaultWarehouse'
                ); ?>",
                defaultWarehouseSearchPostalCodesUrl: "<?php echo $block->getControllerUrl(
                    'DefaultWarehouse',
                    'searchPostalCodes'
                ); ?>",
                shippingMethodsGetStatusUrl: "<?php echo $block->getControllerUrl(
                    'ShippingMethods',
                    'getTaskStatus'
                ); ?>",
                shippingMethodsGetAllUrl: "<?php echo $block->getControllerUrl('ShippingMethods', 'getAll'); ?>",
                shippingMethodsActivateUrl: "<?php echo $block->getControllerUrl('ShippingMethods', 'activate'); ?>",
                shippingMethodsDeactivateUrl: "<?php echo $block->getControllerUrl(
                    'ShippingMethods',
                    'deactivate'
                ); ?>",
                shippingMethodsSaveUrl: "<?php echo $block->getControllerUrl('ShippingMethods', 'save'); ?>",
                getSystemOrderStatusesUrl: "<?php echo $block->getControllerUrl(
                    'OrderStateMapping',
                    'getSystemOrderStatuses'
                ); ?>",
                orderStatusMappingsGetUrl: "<?php echo $block->getControllerUrl(
                    'OrderStateMapping',
                    'getMappings'
                ); ?>",
                orderStatusMappingsSaveUrl: "<?php echo $block->getControllerUrl(
                    'OrderStateMapping',
                    'setMappings'
                ); ?>",
                debugGetStatusUrl: "<?php echo $block->getControllerUrl('Debug', 'getStatus'); ?>",
                debugSetStatusUrl: "<?php echo $block->getControllerUrl('Debug', 'setStatus'); ?>",
                autoConfigureStartUrl: "<?php echo $block->getControllerUrl('AutoConfigure', 'start'); ?>",
                getShippingCountriesUrl: "<?php echo $block->getControllerUrl('ShippingCountries', 'getAll'); ?>"
            }
        );

        Packlink.state.display();
    }, false);
</script>
</html>