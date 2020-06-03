<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$urlService = new \Packlink\DemoUI\Services\Integration\UrlService();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Demo UI</title>
    <link rel="icon" href="data:;base64,iVBORwOKGO=" />
    <link rel="stylesheet" type="text/css" href="./resources/css/index.css" />
</head>
<body>
<div class="container-fluid pl-main-wrapper" id="pl-main-page-holder">
    <div class="pl-input-mask" id="pl-input-mask"></div>

    <div class="pl-spinner" id="pl-spinner">
        <div></div>
    </div>

    <div class="pl-page-wrapper">
        <div class="pl-sidebar-wrapper">
            <div class="pl-logo-wrapper">
                <img src="" class="pl-dashboard-logo" alt="Packlink PRO Shipping">
            </div>

            <div id="pl-login-page"></div>

            <div id="pl-sidebar-shipping-methods-btn" class="pl-sidebar-link-wrapper pl-sidebar-link"
                 data-pl-sidebar-btn="shipping-methods">
                <div class="pl-sidebar-link-wrapper-line"></div>
                <div class="pl-sidebar-link-wrapper-inner">
                    <svg class="pl-sidebar-icon" xmlns="http://www.w3.org/2000/svg"
                         width="24" height="24" viewBox="0 0 22 20">
                        <g class="pl-icon" fill="#627482" fill-rule="evenodd">
                            <path d="M5.26086957 19.6086957C3.92173913 19.6086957 2.86956522 18.5565217 2.86956522 17.2173913 2.86956522 15.8782609 3.92173913 14.826087 5.26086957 14.826087 6.6 14.826087 7.65217391 15.8782609 7.65217391 17.2173913 7.65217391 18.5565217 6.6 19.6086957 5.26086957 19.6086957L5.26086957 19.6086957zM5.26086957 15.7826087C4.44782609 15.7826087 3.82608696 16.4043478 3.82608696 17.2173913 3.82608696 18.0304348 4.44782609 18.6521739 5.26086957 18.6521739 6.07391304 18.6521739 6.69565217 18.0304348 6.69565217 17.2173913 6.69565217 16.4043478 6.07391304 15.7826087 5.26086957 15.7826087L5.26086957 15.7826087zM16.7391304 19.6086957C15.4 19.6086957 14.3478261 18.5565217 14.3478261 17.2173913 14.3478261 15.8782609 15.4 14.826087 16.7391304 14.826087 18.0782609 14.826087 19.1304348 15.8782609 19.1304348 17.2173913 19.1304348 18.5565217 18.0782609 19.6086957 16.7391304 19.6086957L16.7391304 19.6086957zM16.7391304 15.7826087C15.926087 15.7826087 15.3043478 16.4043478 15.3043478 17.2173913 15.3043478 18.0304348 15.926087 18.6521739 16.7391304 18.6521739 17.5521739 18.6521739 18.173913 18.0304348 18.173913 17.2173913 18.173913 16.4043478 17.5521739 15.7826087 16.7391304 15.7826087L16.7391304 15.7826087z"></path>
                            <path d="M21.0434783 17.6956522L20.0869565 17.6956522C19.8 17.6956522 19.6086957 17.5043478 19.6086957 17.2173913 19.6086957 16.9304348 19.8 16.7391304 20.0869565 16.7391304L21.0434783 16.7391304 21.0434783 10.9521739C21.0434783 9.99565217 20.4695652 9.13478261 19.6086957 8.75217391L13.6782609 6.16956522C13.4391304 6.07391304 13.3434783 5.78695652 13.4391304 5.54782609 13.5347826 5.30869565 13.8217391 5.21304348 14.0608696 5.30869565L19.9913043 7.89130435C21.1869565 8.4173913 22 9.61304348 22 10.9521739L22 16.7391304C22 17.2652174 21.5695652 17.6956522 21.0434783 17.6956522L21.0434783 17.6956522zM12.4347826 17.6956522L8.60869565 17.6956522C8.32173913 17.6956522 8.13043478 17.5043478 8.13043478 17.2173913 8.13043478 16.9304348 8.32173913 16.7391304 8.60869565 16.7391304L12.4347826 16.7391304C12.7217391 16.7391304 12.9130435 16.9304348 12.9130435 17.2173913 12.9130435 17.5043478 12.7217391 17.6956522 12.4347826 17.6956522L12.4347826 17.6956522z"></path>
                            <path d="M1.91304348,17.6956522 L1.43478261,17.6956522 C0.62173913,17.6956522 0,17.073913 0,16.2608696 L0,1.43478261 C0,0.62173913 0.62173913,0 1.43478261,0 L12.9130435,0 C13.726087,0 14.3478261,0.62173913 14.3478261,1.43478261 L14.3478261,14.3478261 C14.3478261,14.6347826 14.1565217,14.826087 13.8695652,14.826087 C13.5826087,14.826087 13.3913043,14.6347826 13.3913043,14.3478261 L13.3913043,1.43478261 C13.3913043,1.14782609 13.2,0.956521739 12.9130435,0.956521739 L1.43478261,0.956521739 C1.14782609,0.956521739 0.956521739,1.14782609 0.956521739,1.43478261 L0.956521739,16.2608696 C0.956521739,16.5478261 1.14782609,16.7391304 1.43478261,16.7391304 L1.91304348,16.7391304 C2.2,16.7391304 2.39130435,16.9304348 2.39130435,17.2173913 C2.39130435,17.5043478 2.2,17.6956522 1.91304348,17.6956522 L1.91304348,17.6956522 Z"></path>
                            <path d="M13.3913043,12.9130435 L0.956521739,12.9130435 C0.669565217,12.9130435 0.47826087,12.7217391 0.47826087,12.4347826 C0.47826087,12.1478261 0.669565217,11.9565217 0.956521739,11.9565217 L13.3913043,11.9565217 C13.6782609,11.9565217 13.8695652,12.1478261 13.8695652,12.4347826 C13.8695652,12.7217391 13.6782609,12.9130435 13.3913043,12.9130435 L13.3913043,12.9130435 Z"></path>
                        </g>
                    </svg>
                    <div class="pl-sidebar-text-wrapper">
                        SHIPPING SERVICES
                    </div>
                </div>
            </div>
            <div id="pl-sidebar-basic-settings-btn" class="pl-sidebar-link-wrapper"
                 data-pl-sidebar-btn="basic-settings">
                <div class="pl-sidebar-link-wrapper-line"></div>
                <div class="pl-sidebar-link-wrapper-inner">
                    <svg class="pl-sidebar-icon" xmlns="http://www.w3.org/2000/svg"
                         width="24" height="24" viewBox="0 0 22 20">
                        <g class="pl-icon" fill="#627482" fill-rule="evenodd">
                            <path d="M11.3744681,21.5319149 L10.1106383,21.5319149 C9.40851064,21.5319149 8.84680851,21.0638298 8.70638298,20.3617021 L8.51914894,19.3787234 C7.67659574,19.1914894 6.88085106,18.8170213 6.1787234,18.3957447 L5.33617021,18.9574468 C4.77446809,19.3319149 4.02553191,19.2851064 3.55744681,18.8170213 L2.66808511,17.9276596 C2.2,17.4595745 2.10638298,16.7106383 2.52765957,16.1489362 L3.0893617,15.306383 C2.66808511,14.5574468 2.34042553,13.7617021 2.10638298,12.9659574 L1.12340426,12.7787234 C0.468085106,12.6382979 0,12.0765957 0,11.3744681 L0,10.1106383 C0,9.40851064 0.468085106,8.84680851 1.17021277,8.70638298 L2.15319149,8.51914894 C2.34042553,7.67659574 2.71489362,6.88085106 3.13617021,6.1787234 L2.57446809,5.33617021 C2.2,4.77446809 2.24680851,4.02553191 2.71489362,3.55744681 L3.60425532,2.66808511 C4.07234043,2.2 4.86808511,2.10638298 5.38297872,2.52765957 L6.22553191,3.0893617 C6.97446809,2.66808511 7.77021277,2.34042553 8.56595745,2.10638298 L8.75319149,1.12340426 C8.89361702,0.468085106 9.45531915,0 10.1574468,0 L11.4212766,0 C12.1234043,0 12.6851064,0.468085106 12.8255319,1.17021277 L13.012766,2.15319149 C13.8553191,2.34042553 14.6510638,2.71489362 15.3531915,3.13617021 L16.1957447,2.57446809 C16.7574468,2.2 17.506383,2.24680851 17.9744681,2.71489362 L18.8638298,3.60425532 C19.3319149,4.07234043 19.4255319,4.8212766 19.0042553,5.38297872 L18.4425532,6.22553191 C18.8638298,6.97446809 19.1914894,7.77021277 19.4255319,8.56595745 L20.4085106,8.75319149 C21.0638298,8.89361702 21.5787234,9.45531915 21.5787234,10.1574468 L21.5787234,11.4212766 C21.5787234,12.1234043 21.1106383,12.6851064 20.4085106,12.8255319 L19.4255319,13.012766 C19.2382979,13.8553191 18.8638298,14.6510638 18.4425532,15.3531915 L19.0042553,16.1957447 C19.3787234,16.7574468 19.3319149,17.506383 18.8638298,17.9744681 L17.9744681,18.8638298 C17.693617,19.1446809 17.3659574,19.2851064 16.9914894,19.2851064 L16.9914894,19.2851064 C16.7106383,19.2851064 16.4297872,19.1914894 16.1957447,19.0510638 L15.3531915,18.4893617 C14.6042553,18.9106383 13.8085106,19.2382979 13.012766,19.4723404 L12.8255319,20.4553191 C12.6382979,21.0638298 12.0765957,21.5319149 11.3744681,21.5319149 L11.3744681,21.5319149 Z M6.22553191,17.3659574 C6.31914894,17.3659574 6.41276596,17.412766 6.45957447,17.4595745 C7.25531915,17.9744681 8.14468085,18.3489362 9.08085106,18.5361702 C9.26808511,18.5829787 9.40851064,18.7234043 9.45531915,18.9106383 L9.6893617,20.2212766 C9.73617021,20.4553191 9.92340426,20.5957447 10.1574468,20.5957447 L11.4212766,20.5957447 C11.6553191,20.5957447 11.8425532,20.4553191 11.8893617,20.2212766 L12.1234043,18.9106383 C12.1702128,18.7234043 12.3106383,18.5829787 12.4978723,18.5361702 C13.4340426,18.3489362 14.3234043,17.9744681 15.1191489,17.4595745 C15.2595745,17.3659574 15.493617,17.3659574 15.6340426,17.4595745 L16.7106383,18.2085106 C16.8978723,18.3489362 17.1319149,18.3021277 17.3191489,18.1617021 L18.2085106,17.2723404 C18.3489362,17.1319149 18.3957447,16.8510638 18.2553191,16.6638298 L17.506383,15.587234 C17.412766,15.4468085 17.412766,15.212766 17.506383,15.0723404 C18.0212766,14.2765957 18.3957447,13.387234 18.5829787,12.4510638 C18.6297872,12.2638298 18.7702128,12.1234043 18.9574468,12.0765957 L20.2680851,11.8425532 C20.5021277,11.7957447 20.6425532,11.6085106 20.6425532,11.3744681 L20.6425532,10.1106383 C20.6425532,9.87659574 20.5021277,9.6893617 20.2680851,9.64255319 L18.9574468,9.40851064 C18.7702128,9.36170213 18.6297872,9.2212766 18.5829787,9.03404255 C18.3957447,8.09787234 18.0212766,7.20851064 17.506383,6.41276596 C17.412766,6.27234043 17.412766,6.03829787 17.506383,5.89787234 L18.2553191,4.8212766 C18.3957447,4.63404255 18.3489362,4.4 18.2085106,4.21276596 L17.3191489,3.32340426 C17.1787234,3.18297872 16.8978723,3.13617021 16.7106383,3.27659574 L15.6340426,4.02553191 C15.493617,4.11914894 15.2595745,4.11914894 15.1191489,4.02553191 C14.3234043,3.5106383 13.4340426,3.13617021 12.4978723,2.94893617 C12.3106383,2.90212766 12.1702128,2.76170213 12.1234043,2.57446809 L11.8893617,1.26382979 C11.8425532,1.02978723 11.6553191,0.889361702 11.4212766,0.889361702 L10.1574468,0.889361702 C9.92340426,0.889361702 9.73617021,1.02978723 9.6893617,1.26382979 L9.45531915,2.57446809 C9.40851064,2.76170213 9.26808511,2.90212766 9.08085106,2.94893617 C8.14468085,3.13617021 7.25531915,3.5106383 6.45957447,4.02553191 C6.31914894,4.11914894 6.08510638,4.11914894 5.94468085,4.02553191 L4.86808511,3.27659574 C4.68085106,3.13617021 4.44680851,3.18297872 4.25957447,3.32340426 L3.37021277,4.21276596 C3.22978723,4.35319149 3.18297872,4.63404255 3.32340426,4.8212766 L4.07234043,5.89787234 C4.16595745,6.03829787 4.16595745,6.27234043 4.07234043,6.41276596 C3.55744681,7.20851064 3.18297872,8.09787234 2.99574468,9.03404255 C2.94893617,9.2212766 2.80851064,9.36170213 2.6212766,9.40851064 L1.3106383,9.64255319 C1.07659574,9.6893617 0.936170213,9.87659574 0.936170213,10.1106383 L0.936170213,11.3744681 C0.936170213,11.6085106 1.07659574,11.7957447 1.3106383,11.8425532 L2.6212766,12.0765957 C2.80851064,12.1234043 2.94893617,12.2638298 2.99574468,12.4510638 C3.18297872,13.387234 3.55744681,14.2765957 4.07234043,15.0723404 C4.16595745,15.212766 4.16595745,15.4468085 4.07234043,15.587234 L3.32340426,16.6638298 C3.18297872,16.8510638 3.22978723,17.0851064 3.37021277,17.2723404 L4.25957447,18.1617021 C4.4,18.3021277 4.68085106,18.3489362 4.86808511,18.2085106 L5.94468085,17.4595745 C6.03829787,17.412766 6.13191489,17.3659574 6.22553191,17.3659574 L6.22553191,17.3659574 Z"></path>
                            <path d="M10.7659574,14.9787234 C8.42553191,14.9787234 6.55319149,13.106383 6.55319149,10.7659574 C6.55319149,8.42553191 8.42553191,6.55319149 10.7659574,6.55319149 C13.106383,6.55319149 14.9787234,8.42553191 14.9787234,10.7659574 C14.9787234,13.106383 13.106383,14.9787234 10.7659574,14.9787234 L10.7659574,14.9787234 Z M10.7659574,7.4893617 C8.94042553,7.4893617 7.4893617,8.94042553 7.4893617,10.7659574 C7.4893617,12.5914894 8.94042553,14.0425532 10.7659574,14.0425532 C12.5914894,14.0425532 14.0425532,12.5914894 14.0425532,10.7659574 C14.0425532,8.94042553 12.5914894,7.4893617 10.7659574,7.4893617 L10.7659574,7.4893617 Z"></path>
                        </g>
                    </svg>
                    <div class="pl-sidebar-text-wrapper">
                        BASIC SETTINGS
                    </div>
                </div>
            </div>

            <div id="pl-sidebar-extension-point"></div>

            <div class="pl-help">
                <a class="pl-link" href="" target="_blank">
                    <span><Help</span>
                    <svg height="16" width="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 22">
                        <defs>
                            <style>.cls-1 {
                                    fill: #fff;
                                }

                                .cls-2 {
                                    fill: #2095f2;
                                }</style>
                        </defs>
                        <circle class="cls-1" cx="11" cy="11" r="10.5"></circle>
                        <path class="cls-2"
                              d="M11,22A11,11,0,1,1,22,11,11,11,0,0,1,11,22ZM11,1A10,10,0,1,0,21,11,10,10,0,0,0,11,1Z"></path>
                        <path class="cls-2"
                              d="M10.07,12c0-2,2.78-2.12,2.78-3.75,0-.77-.6-1.43-1.81-1.43A2.86,2.86,0,0,0,8.59,8.12l-.77-.83a4.08,4.08,0,0,1,3.34-1.57c1.88,0,3,1.06,3,2.38,0,2.32-3,2.52-3,4a1,1,0,0,0,.41.75l-.94.4A1.59,1.59,0,0,1,10.07,12Zm.08,3.4a.85.85,0,1,1,.85.85A.85.85,0,0,1,10.15,15.43Z"></path>
                    </svg>
                </a>
                <div class="pl-contact">Contact us:</div>
                <a href="mailto:business@packlink.com" class="pl-link" target="_blank">business@packlink.com</a>
            </div>
        </div>
        <div class="pl-content-wrapper">
            <div class="pl-input-mask" id="pl-input-mask"></div>
            <div class="row">
                <div class="pl-content-wrapper-panel" id="pl-content-extension-point"></div>
            </div>
        </div>
    </div>

    <div id="pl-footer-extension-point"></div>
</div>

<div class="pl-template-section">
    <div id="pl-sidebar-subitem-template">
        <div class="row pl-sidebar-subitem-wrapper" data-pl-sidebar-btn="order-state-mapping">
            <div>
                Map order statuses
            </div>
        </div>
        <div class="row pl-sidebar-subitem-wrapper" data-pl-sidebar-btn="default-warehouse">
            <div>
                Default warehouse
            </div>
        </div>
        <div class="row pl-sidebar-subitem-wrapper" data-pl-sidebar-btn="default-parcel">
            <div>
                Default parcel
            </div>
        </div>
    </div>

    <div id="pl-default-parcel-template">
        <div class="row">
            <div class="pl-basic-settings-page-wrapper">
                <div class="row">
                    <div class="pl-basic-settings-page-title-wrapper">
                        Set default parcel
                    </div>
                </div>
                <div class="row">
                    <div class="pl-basic-settings-page-description-wrapper">
                        We will use the default parcel in case any item has not defined dimensions and weight.
                        You can edit anytime.
                    </div>
                </div>
                <div class="row">
                    <div class="pl-basic-settings-page-form-wrapper">
                        <div class="row">
                            <div class=" pl-basic-settings-page-form-input-item">
                                <div class=" pl-form-section-input pl-text-input pl-parcel-input">
                                    <input type="text" id="pl-default-parcel-weight"/>
                                    <span class="pl-text-input-label">
                                        Weight (kg)
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="pl-basic-settings-page-form-input-item pl-inline-input">
                                <div class=" pl-form-section-input pl-text-input pl-parcel-input">
                                    <input type="text" id="pl-default-parcel-length"/>
                                    <span class="pl-text-input-label">
                                        Length (cm)
                                    </span>
                                </div>
                                <div class=" pl-form-section-input pl-text-input pl-parcel-input">
                                    <input type="text" id="pl-default-parcel-width"/>
                                    <span class="pl-text-input-label">
                                        Width (cm)
                                    </span>
                                </div>
                                <div class=" pl-form-section-input pl-text-input pl-parcel-input">
                                    <input type="text" id="pl-default-parcel-height"/>
                                    <span class="pl-text-input-label">
                                        Height (cm)
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="pl-basic-settings-page-form-input-item pl-parcel-button">
                                <button type="button"
                                        class="button button-primary btn-lg"
                                        id="pl-default-parcel-submit-btn"
                                >
                                    Save changes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="pl-default-warehouse-template">
        <div class="row">
            <div class="pl-basic-settings-page-wrapper">
                <div class="row">
                    <div class="pl-basic-settings-page-title-wrapper">
                        Set default warehouse
                    </div>
                </div>
                <div class="row">
                    <div class="pl-basic-settings-page-description-wrapper">
                        We will use the default Warehouse address as your sender address. You can edit anytime.
                    </div>
                </div>
                <div class="row">
                    <div class="pl-basic-settings-page-form-wrapper">
                        <div class="row">
                            <div class=" pl-basic-settings-page-form-input-item">
                                <div class=" pl-form-section-input pl-text-input">
                                    <input type="text" class="pl-warehouse-input" id="pl-default-warehouse-alias"/>
                                    <span class="pl-text-input-label">
                                        Warehouse name
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class=" pl-basic-settings-page-form-input-item">
                                <div class=" pl-form-section-input pl-text-input">
                                    <input type="text" class="pl-warehouse-input" id="pl-default-warehouse-name"/>
                                    <span class="pl-text-input-label">
                                        Contact person name
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class=" pl-basic-settings-page-form-input-item">
                                <div class=" pl-form-section-input pl-text-input">
                                    <input type="text" class="pl-warehouse-input" id="pl-default-warehouse-surname"/>
                                    <span class="pl-text-input-label">
                                        Contact person surname
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class=" pl-basic-settings-page-form-input-item">
                                <div class=" pl-form-section-input pl-text-input">
                                    <input type="text" class="pl-warehouse-input" id="pl-default-warehouse-company"/>
                                    <span class="pl-text-input-label">
                                        Company name
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class=" pl-basic-settings-page-form-input-item">
                                <div class=" pl-form-section-input pl-text-input">
                                    <select
                                            class="pl-warehouse-input"
                                            id="pl-default-warehouse-country"
                                            tabindex="-1"
                                    >
                                    </select>
                                    <span class="pl-text-input-label selected">
                                        Country
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class=" pl-basic-settings-page-form-input-item">
                                <div class=" pl-form-section-input pl-text-input">
                                    <input type="text" class="pl-warehouse-input"
                                           id="pl-default-warehouse-postal_code" autocomplete="new-password"/>
                                    <span class="pl-text-input-label">
                                        City or postal code
                                    </span>
                                    <span class="pl-input-search-icon" data-pl-id="search-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="31"
                                             viewBox="0 0 30 31">
                                          <g fill="none" fill-rule="evenodd">
                                            <polygon points=".794 .206 29.106 .206 29.106 30.226 .794 30.226"></polygon>
                                            <path fill="#444"
                                                  d="M11.3050003,21.2060012 C11.0510005,21.2060012 10.7959995,21.1960029 10.5380001,21.1780014 C4.7639999,20.7610015 0.4049997,15.7240009 0.8220005,9.9490013 C1.2350006,4.2310009 6.2310009,-0.1849986 12.0510005,0.2330017 C14.8479995,0.4350013 17.3990001,1.7140016 19.2350006,3.8350019 C21.0699996,5.9560012 21.9689998,8.6650009 21.7680015,11.4620018 C21.3740005,16.9249992 16.7770004,21.2060012 11.3050003,21.2060012 Z M11.2849998,2.2040004 C6.8559989,2.2040004 3.137,5.6690006 2.8169994,10.0930004 C2.4789991,14.7680015 6.0079994,18.8460006 10.6829986,19.184 C15.3799991,19.5109996 19.4389991,15.9470005 19.7729988,11.3169994 C19.9369983,9.0529994 19.2089996,6.861 17.7229995,5.1429996 C16.2379989,3.4259996 14.1719989,2.3909997 11.907999,2.2269992 C11.6989994,2.2119998 11.4920005,2.2040004 11.2849998,2.2040004 Z"></path>
                                            <path fill="#444"
                                                  d="M17.2810001 12.1369991C17.2569999 12.1369991 17.2329998 12.1359996 17.2080001 12.1339988 16.6569995 12.0949993 16.243 11.6149997 16.2830009 11.0649986 16.3790016 9.7329978 15.9510002 8.4439983 15.0770015 7.4339981 14.203001 6.4229984 12.9880008 5.8149986 11.656002 5.7179985 11.1050014 5.6789989 10.6910018 5.1989994 10.7300014 4.6489982 10.769001 4.0989971 11.2410011 3.6699981 11.7990016 3.723998 13.6640014 3.8579978 15.3650016 4.7109985 16.5890007 6.1239986 17.8129997 7.5379981 18.4120006 9.3439979 18.2770004 11.2089996 18.2390003 11.7350006 17.7999992 12.1369991 17.2810001 12.1369991zM26.361 30.2260017C25.5909996 30.2260017 24.8260002 29.9050025 24.2840003 29.2790031L15.2709999 19.6850032C14.8929996 19.2830028 14.9130001 18.6500034 15.3150005 18.2720031 15.7170009 17.8940029 16.3500003 17.9130039 16.729 18.3160037L25.7700004 27.9400024C26.0660018 28.281002 26.538002 28.3160018 26.848999 28.0450019 26.9990005 27.9150009 27.0900001 27.7340011 27.104 27.5350036 27.118 27.3370018 27.0540008 27.1440048 26.9239997 26.9940032L18.2679996 17.6810035C17.8920001 17.2770042 17.914999 16.6440029 18.3199996 16.2680034 18.723999 15.892004 19.3570003 15.9150028 19.7329998 16.3200035L28.413002 25.6600036C28.9160003 26.2400054 29.1520004 26.9490051 29.0990028 27.6810035 29.0460052 28.413002 28.7120018 29.0790023 28.1570014 29.5590019 27.6380004 30.0060005 26.998001 30.2260017 26.361 30.2260017z"></path>
                                          </g>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class=" pl-basic-settings-page-form-input-item">
                                <div class=" pl-form-section-input pl-text-input">
                                    <input type="text" class="pl-warehouse-input" id="pl-default-warehouse-address"/>
                                    <span class="pl-text-input-label">
                                        Address
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class=" pl-basic-settings-page-form-input-item">
                                <div class=" pl-form-section-input pl-text-input">
                                    <input type="text" class="pl-warehouse-input" id="pl-default-warehouse-phone"/>
                                    <span class="pl-text-input-label">
                                        Phone number
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class=" pl-basic-settings-page-form-input-item">
                                <div class="pl-form-section-input pl-text-input">
                                    <input type="text" class="pl-warehouse-input" id="pl-default-warehouse-email"/>
                                    <span class="pl-text-input-label">
                                        Email
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class=" pl-basic-settings-page-form-input-item">
                                <button type="button"
                                        class="button button-primary btn-lg"
                                        id="pl-default-warehouse-submit-btn"
                                >
                                    Save changes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="pl-shipping-methods-page-template">

        <!-- Allowed  shipping countries section -->

        <div id="pl-allowed-shipping-countries-ep"></div>

        <!-- DELETE SHIPPING METHODS MODAL -->

        <div class="pl-dashboard-modal-wrapper hidden" id="pl-disable-methods-modal-wrapper">
            <div class="pl-dashboard-modal pl-disable-methods-modal" id="pl-disable-methods-modal">
                <div class="pl-shipping-modal-title">
                    Congrats! Your first Shipping Method has been successfully created.
                </div>
                <div class="pl-shipping-modal-body">
                    In order to offer you the best possible service, its important to disable your previous carriers.
                    Do you want us to disable them? (recommended)
                </div>
                <div class="pl-shipping-modal-row">
                    <button class="button pl-shipping-modal-btn"
                            id="pl-disable-methods-modal-cancel">
                        Cancel
                    </button>
                    <button class="button button-primary"
                            id="pl-disable-methods-modal-accept">
                        Accept
                    </button>
                </div>
            </div>
        </div>

        <!-- DASHBOARD MODAL SECTION -->

        <div class="pl-dashboard-modal-wrapper hidden" id="pl-dashboard-modal-wrapper">
            <div class="pl-dashboard-modal" id="pl-dashboard-modal">
                <img src="" alt="">
                <div class="pl-dashboard-page-title-wrapper">
                    You're almost there!
                </div>
                <div class="pl-dashboard-page-subtitle-wrapper">
                    Details synced with your existing account
                </div>
                <div class="pl-dashboard-page-step-wrapper pl-dashboard-page-step" id="pl-parcel-step">
                    <div class="pl-empty-checkmark pl-checkmark">
                        <input type="checkbox"/>
                    </div>
                    <div class="pl-checked-checkmark pl-checkmark">
                        <input type="checkbox" checked="checked"/>
                    </div>
                    <div class="pl-step-title">
                        Set default parcel details
                    </div>
                </div>
                <div class="pl-dashboard-page-step-wrapper pl-dashboard-page-step" id="pl-warehouse-step">
                    <div class="pl-empty-checkmark pl-checkmark">
                        <input type="checkbox"/>
                    </div>
                    <div class="pl-checked-checkmark pl-checkmark">
                        <input type="checkbox" checked="checked"/>
                    </div>
                    <div class="pl-step-title">
                        Set default warehouse details
                    </div>
                </div>
                <div class="pl-dashboard-page-subtitle-wrapper" id="pl-step-subtitle">
                    Just a few more steps to complete the setup
                </div>
                <div class="pl-dashboard-page-step-wrapper pl-dashboard-page-step" id="pl-shipping-methods-step">
                    <div class="pl-empty-checkmark pl-checkmark">
                        <input type="checkbox"/>
                    </div>
                    <div class="pl-checked-checkmark pl-checkmark">
                        <input type="checkbox" checked="checked"/>
                    </div>
                    <div class="pl-step-title">
                        Select shipping services
                    </div>
                </div>
            </div>
        </div>

        <!-- SHIPPING PAGE SECTION -->

        <div class="row">
            <div class="pl-flash-msg-wrapper">
                <div class="pl-flash-msg" id="pl-flash-message">
                    <div class="pl-flash-msg-text-section">
                        <i class="material-icons success">
                        </i>
                        <i class="material-icons warning">
                        </i>
                        <i class="material-icons danger">
                        </i>
                        <span id="pl-flash-message-text"></span>
                    </div>
                    <div class="pl-flash-msg-close-btn">
                        <span id="pl-flash-message-close-btn">x</span>
                    </div>
                </div>
            </div>
            <div class="pl-filter-wrapper">
                <div id="pl-shipping-methods-filters-extension-point"></div>
            </div>
            <div class="pl-methods-tab-wrapper">
                <div id="pl-shipping-methods-nav-extension-point"></div>
                <div class="row">
                    <div class="pl-clear-padding">
                        <div id="pl-shipping-methods-result-extension-point"></div>
                    </div>
                </div>
                <div class="pl-table-wrapper" id="pl-table-scroll">
                    <div id="pl-shipping-methods-table-extension-point"></div>
                    <div class="pl-shipping-services-message hidden" id="pl-getting-shipping-services">
                        <div class="title">
                            We are importing the best shipping services for your shipments.
                        </div>
                        <div class="subtitle">
                            This process could take a few seconds.
                        </div>
                        <div class="pl-spinner" id="pl-getting-services-spinner">
                            <div></div>
                        </div>
                    </div>
                    <div class="pl-shipping-services-message hidden" id="pl-no-shipping-services">
                        <div class="title">
                            We are having troubles getting shipping services.
                        </div>
                        <div class="subtitle">Do you want to retry?</div>
                        <button type="button" class="button button-primary"
                                id="pl-shipping-services-retry-btn">
                            Retry
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="pl-shipping-methods-filters-template">
        <div class="row">
            <div class="pl-filter-method-tile">
                Filter services
            </div>
        </div>
        <div class="row">
            <div class="pl-filter-method">
                <b>Type</b>
            </div>
        </div>
        <div class="row">
            <div class="pl-filter-method-item">
                <div class="md-checkbox">
                    <label>
                        <input type="checkbox" data-pl-shipping-methods-filter="title-national" tabindex="-1">
                        National
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="pl-filter-method-item">
                <div class="md-checkbox">
                    <label>
                        <input type="checkbox" data-pl-shipping-methods-filter="title-international" tabindex="-1">
                        International
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="pl-filter-method">
                <b>Delivery type</b>
            </div>
        </div>
        <div class="row">
            <div class="pl-filter-method-item">
                <div class="md-checkbox">
                    <label>
                        <input type="checkbox" data-pl-shipping-methods-filter="deliveryType-economic" tabindex="-1">
                        Economic
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="pl-filter-method-item">
                <div class="md-checkbox">
                    <label>
                        <input type="checkbox" data-pl-shipping-methods-filter="deliveryType-express" tabindex="-1">
                        Express
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="pl-filter-method">
                <b>Parcel origin</b>
            </div>
        </div>
        <div class="row">
            <div class="pl-filter-method-item">
                <div class="md-checkbox">
                    <label>
                        <input type="checkbox" data-pl-shipping-methods-filter="parcelOrigin-pickup" tabindex="-1">
                        Collection
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class=" pl-filter-method-item">
                <div class="md-checkbox">
                    <label>
                        <input type="checkbox" data-pl-shipping-methods-filter="parcelOrigin-dropoff" tabindex="-1">
                        Drop off
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class=" pl-filter-method">
                <b>
                    Parcel destination</b>
            </div>
        </div>
        <div class="row">
            <div class=" pl-filter-method-item">
                <div class="md-checkbox">
                    <label>
                        <input type="checkbox" data-pl-shipping-methods-filter="parcelDestination-home" tabindex="-1">
                        Delivery
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class=" pl-filter-method-item">
                <div class="md-checkbox">
                    <label>
                        <input type="checkbox" data-pl-shipping-methods-filter="parcelDestination-dropoff"
                               tabindex="-1">
                        Pick up
                    </label>
                </div>
            </div>
        </div>
    </div>

    <table>
        <tbody id="pl-shipping-method-configuration-template">
        <tr class="pl-configure-shipping-method-wrapper">
            <td colspan="9">
                <div class="row">
                    <div class=" pl-configure-shipping-method-form-wrapper">
                        <div class="row pl-shipping-method-form">
                            <div class=" pl-form-section-wrapper">
                                <div class="row">
                                    <div class=" pl-form-section-title-wrapper">
                                        <div class="pl-form-section-title">
                                            Add service title
                                        </div>
                                        <div class="pl-form-section-title-line">
                                            <hr>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class=" pl-form-section-subtitle-wrapper">
                                        This title will be visible to your customers
                                    </div>
                                </div>
                                <div class="row">
                                    <div class=" pl-form-section-input-wrapper">
                                        <div class="form-group pl-form-section-input pl-text-input">
                                            <input type="text" class="form-control" id="pl-method-title-input"/>
                                            <span class="pl-text-input-label">
                                                Service title
                                            </span>
                                        </div>
                                        <div class="row">
                                            <div class=" pl-form-section-title-wrapper pl-title-carrier-logo">
                                                <div class="pl-form-section-title">
                                                    Carrier logo
                                                </div>
                                                <div class="pl-form-section-title-line">
                                                    <hr>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="md-checkbox">
                                            <label class="pl-form-section-input-checkbox-label">
                                                <input type="checkbox" name="method-show-logo-input" checked
                                                       id="pl-show-logo">
                                                Show carrier logo to my customers
                                            </label>
                                        </div>
                                        <div class="row">
                                            <div class="pl-form-section-title-wrapper pl-shipping-countries">
                                                <div class="pl-form-section-title">
                                                    Service availability per destination country
                                                </div>
                                                <div class="pl-form-section-title-line">
                                                    <hr>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="md-checkbox">
                                            <label class="pl-form-section-input-checkbox-label">
                                                <input type="checkbox" name="method-all-countries-input"
                                                       id="pl-country-selector-checkbox">
                                                Service available for all destination countries
                                            </label>
                                            <div class="pl-checkbox-sub-action">
                                                <span id="pl-country-list-btn">
                                                    See countries list
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class=" pl-form-section-wrapper">
                                <div class="row">
                                    <div class=" pl-form-section-title-wrapper">
                                        <div class="pl-form-section-title">
                                            Select pricing policy
                                        </div>
                                        <div class="pl-form-section-title-line">
                                            <hr>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class=" pl-form-section-subtitle-wrapper">
                                        Choose the pricing policy to show your customers
                                    </div>
                                </div>
                                <div class="row">
                                    <div class=" pl-form-section-input-wrapper">
                                        <div class="form-group pl-form-section-input">
                                            <select id="pl-pricing-policy-selector">
                                                <option value="1">
                                                    Packlink prices
                                                </option>
                                                <option value="2">
                                                    % of Packlink prices
                                                </option>
                                                <option value="3">
                                                    Fixed prices based on total weight
                                                </option>
                                                <option value="4">
                                                    Fixed prices based on total price
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div id="pl-pricing-extension-point"></div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="pl-configure-shipping-method-button-wrapper">
                        <button type="button" class="button button-primary btn-lg"
                                id="pl-shipping-method-config-save-btn">
                            Save
                        </button>
                        <button type="button" class="button btn-outline-secondary btn-lg"
                                id="pl-shipping-method-config-cancel-btn">
                            Cancel
                        </button>
                    </div>
                </div>
            </td>
        </tr>
        </tbody>
    </table>

    <table>
        <tbody id="pl-shipping-methods-row-template">
        <tr class="pl-table-row-wrapper">
            <td class="pl-table-row-method-select">
                <div id="pl-shipping-method-select-btn" class="pl-switch" tabindex="-1">
                    <div class="pl-empty-checkbox">
                        <input type="checkbox" tabindex="-1"/>
                    </div>
                    <div class="pl-checked-checkbox">
                        <input type="checkbox" checked="checked" tabindex="-1"/>
                    </div>
                </div>
            </td>
            <td class="pl-table-row-method-title">
                <h2 id="pl-shipping-method-name">
                </h2>
                <p class="pl-price-indicator" data-pl-price-indicator="packlink">
                    Packlink prices
                </p>
                <p class="pl-price-indicator" data-pl-price-indicator="percent">
                    Packlink percent
                </p>
                <p class="pl-price-indicator" data-pl-price-indicator="fixed-weight">
                    Fixed prices based on total weight
                </p>
                <p class="pl-price-indicator" data-pl-price-indicator="fixed-value">
                    Fixed prices based on total price
                </p>
            </td>
            <td class="pl-table-row-method-logo">
                <img class="pl-method-logo" id="pl-logo" alt="Logo" src="">
            </td>
            <td class="pl-table-row-method-delivery-type" id="pl-delivery-type">

            </td>
            <td class="pl-table-row-method-type" id="pl-method-title">
                <div class="pl-national">
                    National
                </div>
                <div class="pl-international">
                    International
                </div>
            </td>
            <td>
                <div class="pl-method-pudo-icon-wrapper" id="pl-pudo-icon-origin">
                    <div class="pl-pudo-pickup">
                        <svg width="25" height="25" viewBox="0 0 36 31" version="1.1"
                             xmlns="http://www.w3.org/2000/svg">
                            <g id="Pickup" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"
                               transform="translate(-11.000000, -2.000000)">
                                <g id="home" transform="translate(11.000000, 2.000000)" fill="#1A77C2"
                                   fill-rule="nonzero">
                                    <path d="M30.2660099,15.5485893 C29.868228,15.5485893 29.5453906,15.8514488 29.5453906,16.224615 L29.5453906,28.3930762 C29.5453906,28.7655663 29.2218325,29.0691018 28.8247713,29.0691018 L23.059817,29.0691018 L23.059817,20.2807687 C23.059817,19.9076026 22.7369796,19.6047431 22.3391977,19.6047431 L13.6917664,19.6047431 C13.2939845,19.6047431 12.9711471,19.9076026 12.9711471,20.2807687 L12.9711471,29.0691018 L7.20619282,29.0691018 C6.8091316,29.0691018 6.48557354,28.7655663 6.48557354,28.3930762 L6.48557354,16.224615 C6.48557354,15.8514488 6.1627361,15.5485893 5.76495426,15.5485893 C5.36717241,15.5485893 5.04433498,15.8514488 5.04433498,16.224615 L5.04433498,28.3930762 C5.04433498,29.5112226 6.01428853,30.4211531 7.20619282,30.4211531 L13.6917664,30.4211531 C14.0895482,30.4211531 14.4123856,30.1182936 14.4123856,29.7451274 L14.4123856,20.9567943 L21.6185785,20.9567943 L21.6185785,29.7451274 C21.6185785,30.1182936 21.9414159,30.4211531 22.3391977,30.4211531 L28.8247713,30.4211531 C30.0166756,30.4211531 30.9866291,29.5112226 30.9866291,28.3930762 L30.9866291,16.224615 C30.9866291,15.8514488 30.6637917,15.5485893 30.2660099,15.5485893 Z"
                                          id="Shape"></path>
                                    <path d="M35.0876735,15.0598228 L18.51343,0.187259098 C18.2345503,-0.062870383 17.7956932,-0.062870383 17.5168135,0.187259098 L0.942570021,15.0598228 C0.655042928,15.3180646 0.644954258,15.7459888 0.920230823,16.015723 C1.19478677,16.2854573 1.65165939,16.2942456 1.93918649,16.0366798 L18.0154821,1.61164509 L34.0917776,16.0373559 C34.2308571,16.1624206 34.4102913,16.224615 34.5897255,16.224615 C34.7792484,16.224615 34.9687713,16.1543083 35.1107333,16.015723 C35.3852892,15.7459888 35.3752006,15.3180646 35.0876735,15.0598228 Z"
                                          id="Shape"></path>
                                    <path d="M23.7804363,2.02807687 L28.104152,2.02807687 L28.104152,6.08423061 C28.104152,6.45739676 28.4269894,6.76025624 28.8247713,6.76025624 C29.2225531,6.76025624 29.5453906,6.45739676 29.5453906,6.08423061 L29.5453906,1.35205125 C29.5453906,0.978885103 29.2225531,0.676025624 28.8247713,0.676025624 L23.7804363,0.676025624 C23.3826545,0.676025624 23.059817,0.978885103 23.059817,1.35205125 C23.059817,1.72521739 23.3826545,2.02807687 23.7804363,2.02807687 Z"
                                          id="Shape"></path>
                                </g>
                            </g>
                        </svg>
                        Collection
                    </div>
                    <div class="pl-pudo-dropoff">
                        <svg height="25" viewBox="0 0 22 32" width="22" xmlns="http://www.w3.org/2000/svg">
                            <g fill="none" fill-rule="evenodd" transform="translate(-5)">
                                <path d="m0 0h32.0000013v32.0000013h-32.0000013z"></path>
                                <g fill="#1a77c2">
                                    <path d="m15.9993337 31.3333333c-.3639997 0-.6613338-.2926661-.6660004-.6579997v-.0220006c-.0146662-1.0493342-1.5686671-3.2693329-3.2126668-5.6193339-2.87533317-4.1086655-6.45399983-9.2226664-6.45399983-13.9259987 0-5.69799992 4.63533333-10.3333333 10.33333333-10.3333333 5.6980005 0 10.3333333 4.63533338 10.3333333 10.3333333 0 4.6613337-3.5666656 9.7619998-6.4326668 13.8606682-1.6626663 2.3773346-3.2326672 4.6280009-3.2339998 5.6813329.0006662.3646647-.2919999.6833318-.6579998.6833318zm.0006663-29.22533334c-4.9626669 0-9 4.03733365-9 9.00000044 0 4.2833341 3.4446665 9.2066663 6.2126668 13.1619987 1.1500003 1.6433335 2.1546669 3.079333 2.7800001 4.2806677.6273333-1.2179998 1.6473337-2.6766663 2.815333-4.346667 2.7586657-3.9446665 6.1920001-8.8546664 6.1920001-13.0959994 0-4.96266679-4.0373332-9.00000044-9-9.00000044z"></path>
                                    <path d="m16 15.6666667c-2.3893331 0-4.3333333-1.9440003-4.3333333-4.3333334s1.9440002-4.3333333 4.3333333-4.3333333 4.3333333 1.9440002 4.3333333 4.3333333-1.9440002 4.3333334-4.3333333 4.3333334zm0-7.33333337c-1.6539993 0-3 1.346-3 2.99999997 0 1.6539993 1.3460007 3 3 3s3-1.3460007 3-3-1.3459994-2.99999997-3-2.99999997z"></path>
                                </g>
                            </g>
                        </svg>
                        Drop off
                    </div>
                </div>
            </td>
            <td class="pl-table-row-arrow-wrapper">
                <div class="pl-table-row-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="29">
                        <path d="m0,0h30v29H0" fill="#FFF"></path>
                        <path d="m25,19H5v-1h18m0-2 9,2.5-9,2.5"></path>
                    </svg>
                </div>
            </td>
            <td>
                <div class="pl-method-pudo-icon-wrapper" id="pl-pudo-icon-dest">
                    <div class="pl-pudo-pickup">
                        <svg width="25" height="25" viewBox="0 0 36 31" version="1.1"
                             xmlns="http://www.w3.org/2000/svg">
                            <g id="Pickup" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"
                               transform="translate(-11.000000, -2.000000)">
                                <g id="home" transform="translate(11.000000, 2.000000)" fill="#1A77C2"
                                   fill-rule="nonzero">
                                    <path d="M30.2660099,15.5485893 C29.868228,15.5485893 29.5453906,15.8514488 29.5453906,16.224615 L29.5453906,28.3930762 C29.5453906,28.7655663 29.2218325,29.0691018 28.8247713,29.0691018 L23.059817,29.0691018 L23.059817,20.2807687 C23.059817,19.9076026 22.7369796,19.6047431 22.3391977,19.6047431 L13.6917664,19.6047431 C13.2939845,19.6047431 12.9711471,19.9076026 12.9711471,20.2807687 L12.9711471,29.0691018 L7.20619282,29.0691018 C6.8091316,29.0691018 6.48557354,28.7655663 6.48557354,28.3930762 L6.48557354,16.224615 C6.48557354,15.8514488 6.1627361,15.5485893 5.76495426,15.5485893 C5.36717241,15.5485893 5.04433498,15.8514488 5.04433498,16.224615 L5.04433498,28.3930762 C5.04433498,29.5112226 6.01428853,30.4211531 7.20619282,30.4211531 L13.6917664,30.4211531 C14.0895482,30.4211531 14.4123856,30.1182936 14.4123856,29.7451274 L14.4123856,20.9567943 L21.6185785,20.9567943 L21.6185785,29.7451274 C21.6185785,30.1182936 21.9414159,30.4211531 22.3391977,30.4211531 L28.8247713,30.4211531 C30.0166756,30.4211531 30.9866291,29.5112226 30.9866291,28.3930762 L30.9866291,16.224615 C30.9866291,15.8514488 30.6637917,15.5485893 30.2660099,15.5485893 Z"
                                          id="Shape"></path>
                                    <path d="M35.0876735,15.0598228 L18.51343,0.187259098 C18.2345503,-0.062870383 17.7956932,-0.062870383 17.5168135,0.187259098 L0.942570021,15.0598228 C0.655042928,15.3180646 0.644954258,15.7459888 0.920230823,16.015723 C1.19478677,16.2854573 1.65165939,16.2942456 1.93918649,16.0366798 L18.0154821,1.61164509 L34.0917776,16.0373559 C34.2308571,16.1624206 34.4102913,16.224615 34.5897255,16.224615 C34.7792484,16.224615 34.9687713,16.1543083 35.1107333,16.015723 C35.3852892,15.7459888 35.3752006,15.3180646 35.0876735,15.0598228 Z"
                                          id="Shape"></path>
                                    <path d="M23.7804363,2.02807687 L28.104152,2.02807687 L28.104152,6.08423061 C28.104152,6.45739676 28.4269894,6.76025624 28.8247713,6.76025624 C29.2225531,6.76025624 29.5453906,6.45739676 29.5453906,6.08423061 L29.5453906,1.35205125 C29.5453906,0.978885103 29.2225531,0.676025624 28.8247713,0.676025624 L23.7804363,0.676025624 C23.3826545,0.676025624 23.059817,0.978885103 23.059817,1.35205125 C23.059817,1.72521739 23.3826545,2.02807687 23.7804363,2.02807687 Z"
                                          id="Shape"></path>
                                </g>
                            </g>
                        </svg>
                        Delivery
                    </div>
                    <div class="pl-pudo-dropoff">
                        <svg height="25" viewBox="0 0 22 32" width="22" xmlns="http://www.w3.org/2000/svg">
                            <g fill="none" fill-rule="evenodd" transform="translate(-5)">
                                <path d="m0 0h32.0000013v32.0000013h-32.0000013z"></path>
                                <g fill="#1a77c2">
                                    <path d="m15.9993337 31.3333333c-.3639997 0-.6613338-.2926661-.6660004-.6579997v-.0220006c-.0146662-1.0493342-1.5686671-3.2693329-3.2126668-5.6193339-2.87533317-4.1086655-6.45399983-9.2226664-6.45399983-13.9259987 0-5.69799992 4.63533333-10.3333333 10.33333333-10.3333333 5.6980005 0 10.3333333 4.63533338 10.3333333 10.3333333 0 4.6613337-3.5666656 9.7619998-6.4326668 13.8606682-1.6626663 2.3773346-3.2326672 4.6280009-3.2339998 5.6813329.0006662.3646647-.2919999.6833318-.6579998.6833318zm.0006663-29.22533334c-4.9626669 0-9 4.03733365-9 9.00000044 0 4.2833341 3.4446665 9.2066663 6.2126668 13.1619987 1.1500003 1.6433335 2.1546669 3.079333 2.7800001 4.2806677.6273333-1.2179998 1.6473337-2.6766663 2.815333-4.346667 2.7586657-3.9446665 6.1920001-8.8546664 6.1920001-13.0959994 0-4.96266679-4.0373332-9.00000044-9-9.00000044z"></path>
                                    <path d="m16 15.6666667c-2.3893331 0-4.3333333-1.9440003-4.3333333-4.3333334s1.9440002-4.3333333 4.3333333-4.3333333 4.3333333 1.9440002 4.3333333 4.3333333-1.9440002 4.3333334-4.3333333 4.3333334zm0-7.33333337c-1.6539993 0-3 1.346-3 2.99999997 0 1.6539993 1.3460007 3 3 3s3-1.3460007 3-3-1.3459994-2.99999997-3-2.99999997z"></path>
                                </g>
                            </g>
                        </svg>
                        Pick up
                    </div>
                </div>
            </td>
            <td>
                <a href="#" class="pl-link" id="pl-shipping-method-config-btn" tabindex="-1">
                    Configure
                </a>
            </td>
        </tr>
        </tbody>
    </table>

    <div id="pl-shipping-methods-nav-template">
        <div class="row">
            <div class=" pl-nav-wrapper">
                <div class="pl-nav-item selected" data-pl-shipping-methods-nav-button="all" tabindex="-1">
                    All shipping services
                </div>
                <div class="pl-nav-item" data-pl-shipping-methods-nav-button="selected" tabindex="-1">
                    Selected shipping services
                </div>
            </div>
        </div>
    </div>

    <div id="pl-shipping-methods-table-template">
        <table class="table pl-table">
            <thead>
            <tr class="pl-table-header-wrapper">
                <th scope="col" class="pl-table-header-select">
                    SELECT
                </th>
                <th scope="col" class="pl-table-header-title">
                    SHIPPING SERVICES
                </th>
                <th scope="col" class="pl-table-header-carrier">
                    CARRIER
                </th>
                <th scope="col" class="pl-table-header-transit">
                    TRANSIT TIME
                </th>
                <th scope="col" class="pl-table-header-type">
                    TYPE
                </th>
                <th scope="col" class="pl-table-header-origin">
                    ORIGIN
                </th>
                <th scope="col" class="pl-table-header-arrow"></th>
                <th scope="col" class="pl-table-header-destination">
                    DESTINATION
                </th>
                <th scope="col" class="pl-table-header-actions"></th>
            </tr>
            </thead>
            <tbody id="pl-shipping-method-table-row-extension-point" class="pl-tbody">
            </tbody>
        </table>
    </div>

    <div id="pl-shipping-methods-result-template">
        <div class="pl-num-shipping-method-results-wrapper">
            Showing <span id="pl-number-showed-methods"></span>
            results
        </div>
    </div>

    <div id="pl-packlink-percent-template">
        <div class="row">
            <div class=" pl-form-section-subtitle-wrapper">
                Please set pricing rule
            </div>
        </div>
        <div class="row">
            <div class=" pl-form-section-input-wrapper pl-price-increase-wrapper">
                <div class="pl-input-price-switch selected" data-pl-packlink-percent-btn="increase">
                    Increase
                </div>
                <div class="pl-input-price-switch" data-pl-packlink-percent-btn="decrease">
                    Reduce
                </div>
                <div class="form-group pl-form-section-input pl-text-input">
                    <input type="text" class="form-control" id="pl-perecent-amount"/>
                    <span class="pl-text-input-label">BY %</span>
                </div>
            </div>
        </div>
    </div>

    <div id="pl-fixed-prices-by-weight-template">
        <div class="row">
            <div class=" pl-form-section-subtitle-wrapper">
                Please add price for each weight criteria
            </div>
        </div>

        <div class="row">
            <div id="pl-fixed-price-criteria-extension-point" style="width: 100%"></div>
        </div>
        <div class="row">
            <div class=" pl-form-section-input-wrapper">
                <div class="pl-fixed-price-add-criteria-button" id="pl-fixed-price-add">
                    + Add price
                </div>
            </div>
        </div>
    </div>

    <div id="pl-fixed-price-by-weight-criteria-template">
        <div class="pl-fixed-price-criteria">
            <div class="row">
                <div class=" pl-form-section-input-wrapper pl-fixed-price-wrapper">
                    <div class="form-group pl-form-section-input pl-text-input">
                        <input type="text" data-pl-fixed-price="from" tabindex="-1"/>
                        <span class="pl-text-input-label">
                        FROM (kg)</span>
                    </div>
                    <div class="form-group pl-form-section-input pl-text-input">
                        <input type="text" data-pl-fixed-price="to"/>
                        <span class="pl-text-input-label">
                        TO (kg)</span>
                    </div>
                    <div class="form-group pl-form-section-input pl-text-input">
                        <input type="text" data-pl-fixed-price="amount"/>
                        <span class="pl-text-input-label">
                        PRICE (€)</span>
                    </div>
                    <div class="pl-remove-fixed-price-criteria-btn">
                        <svg width="24" height="24" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg"
                             data-pl-remove="criteria">
                            <g fill="none" fill-rule="evenodd">
                                <path d="M11 21c5.523 0 10-4.477 10-10S16.523 1 11 1 1 5.477 1 11s4.477 10 10 10zm0 1C4.925 22 0 17.075 0 11S4.925 0 11 0s11 4.925 11 11-4.925 11-11 11z"
                                      fill="#2095F2" fill-rule="nonzero"></path>
                                <path d="M7.5 7.5l8 7M15.5 7.5l-8 7" stroke="#2095F2" stroke-linecap="square"></path>
                            </g>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="pl-fixed-prices-by-value-template">
        <div class="row">
            <div class=" pl-form-section-subtitle-wrapper">
                Please add price for each price criteria
            </div>
        </div>

        <div class="row">
            <div id="pl-fixed-price-criteria-extension-point" style="width: 100%"></div>
        </div>
        <div class="row">
            <div class=" pl-form-section-input-wrapper">
                <div class="pl-fixed-price-add-criteria-button" id="pl-fixed-price-add">
                    + Add price
                </div>
            </div>
        </div>
    </div>

    <div id="pl-fixed-price-by-value-criteria-template">
        <div class="pl-fixed-price-criteria">
            <div class="row">
                <div class=" pl-form-section-input-wrapper pl-fixed-price-wrapper">
                    <div class="form-group pl-form-section-input pl-text-input">
                        <input type="text" data-pl-fixed-price="from" tabindex="-1"/>
                        <span class="pl-text-input-label">
                        FROM (€)</span>
                    </div>
                    <div class="form-group pl-form-section-input pl-text-input">
                        <input type="text" data-pl-fixed-price="to"/>
                        <span class="pl-text-input-label">
                        TO (€)</span>
                    </div>
                    <div class="form-group pl-form-section-input pl-text-input">
                        <input type="text" data-pl-fixed-price="amount"/>
                        <span class="pl-text-input-label">
                        PRICE (€)</span>
                    </div>
                    <div class="pl-remove-fixed-price-criteria-btn">
                        <svg width="24" height="24" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg"
                             data-pl-remove="criteria">
                            <g fill="none" fill-rule="evenodd">
                                <path d="M11 21c5.523 0 10-4.477 10-10S16.523 1 11 1 1 5.477 1 11s4.477 10 10 10zm0 1C4.925 22 0 17.075 0 11S4.925 0 11 0s11 4.925 11 11-4.925 11-11 11z"
                                      fill="#2095F2" fill-rule="nonzero"></path>
                                <path d="M7.5 7.5l8 7M15.5 7.5l-8 7" stroke="#2095F2" stroke-linecap="square"></path>
                            </g>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="pl-error-template">
        <div class="pl-error-msg" data-pl-element="error">
            <div id="pl-error-text">
            </div>
        </div>
    </div>

    <div id="pl-order-state-mapping-template">
        <div class="pl-mapping-page-wrapper pl-basic-settings-page-wrapper">
            <div class="row">
                <div class=" pl-basic-settings-page-title-wrapper">
                    Map order statuses
                </div>
            </div>
            <div class="row">
                <div class=" pl-basic-settings-page-description-wrapper">
                    Packlink offers you the possibility to update your Magento order status with the shipping info. You can edit anytime.
                </div>
            </div>
            <div>
                <div class="pl-mapping-page-select-section">
                    Packlink PRO Shipping Status
                </div>
                <div class="pl-mapping-page-wrapper-equals">
                </div>
                <div class="pl-mapping-page-select-section">
                    Magento Order Status
                </div>
            </div>
            <?php
            $statuses = array(
                'pending' => 'Pending',
                'processing' => 'Processing',
                'readyForShipping' => 'Ready for shipping',
                'inTransit' => 'In transit',
                'delivered' => 'Delivered',
            );
            foreach ($statuses as $key => $label) { ?>
                <div>
                    <div class="pl-mapping-page-select-section">
                        <input type="text" value="<?php echo $label; ?>" readonly>
                    </div>
                    <div class="pl-mapping-page-wrapper-equals">
                        =
                    </div>
                    <div class="pl-mapping-page-select-section">
                        <select data-pl-status="<?php echo $key; ?>" class="admin__control-select">
                            <option value="" selected>(None)</option>
                        </select>
                    </div>
                </div>
            <?php } ?>
            <div>
                <button class="button button-primary btn-lg" id="pl-save-mappings-btn">
                    Save changes</button>
            </div>
        </div>
    </div>

    <div id="pl-footer-template">
        <div class="pl-footer-row">
            <div class="pl-system-info-panel hidden loading" id="pl-system-info-panel">
                <div class="pl-system-info-panel-close" id="pl-system-info-close-btn">
                    <svg viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg">
                        <g fill="none" fill-rule="evenodd">
                            <path d="M7.5 7.5l8 7M15.5 7.5l-8 7" stroke="#627482" stroke-linecap="square"></path>
                        </g>
                    </svg>
                </div>

                <div class="pl-system-info-panel-content">
                    <div class="md-checkbox">
                        <label class="pl-form-section-input-checkbox-label">
                            <input type="checkbox" id="pl-debug-mode-checkbox">
                            <b>Debug mode</b>
                        </label>
                    </div>

                    <a href=""
                       value="packlink-debug-data.zip"
                       download>
                        <button type="button" class="button button-primary">
                            Download system info file
                        </button>
                    </a>
                </div>

                <div class="pl-system-info-panel-loader">
                    <b>Loading...</b>
                </div>

            </div>


            <div class="pl-footer-wrapper">
                <div class="pl-footer-system-info-wrapper">
                    v1.0.0
                    <span class="pl-system-info-open-btn" id="pl-system-info-open-btn">
                        (system info)
                    </span>
                </div>
                <div class="pl-footer-copyright-wrapper">
                    <a href="" target="_blank">
                        General conditions
                    </a>
                    <p>Developed and managed by Packlink</p>
                </div>
            </div>
        </div>
    </div>

    <div id="pl-allowed-countries-modal-template">
        <div class="pl-dashboard-modal-wrapper">
            <div class="pl-dashboard-modal pl-increased-padding">
                <div class="pl-close-modal-btn" id="pl-close-modal-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" focusable="false" role="presentation">
                        <path d="M12 10.586L6.707 5.293a1 1 0 0 0-1.414 1.414L10.586 12l-5.293 5.293a1 1 0 0 0 1.414 1.414L12 13.414l5.293 5.293a1 1 0 0 0 1.414-1.414L13.414 12l5.293-5.293a1 1 0 1 0-1.414-1.414L12 10.586z"
                              fill="currentColor"></path>
                    </svg>
                </div>
                <div class="pl-shipping-modal-title pl-full-width">
                    Select allowed destination countries for this service
                </div>
                <div class="pl-shipping-modal-body pl-full-width">
                    <select class="pl-destination-countries-selector" name="pl-destination-countries-selector" multiple
                            size="15" id="pl-countries-selector">
                    </select>
                </div>
                <div class="pl-modal-button-wrapper">
                    <button type="button" class="button btn-outline-secondary btn-lg"
                            id="pl-countries-selector-cancel-btn">
                        Cancel
                    </button>
                    <button type="button" class="button button-primary btn-lg pl-button-separator"
                            id="pl-countries-selector-save-btn">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
</body>
<script src="./resources/js/AjaxService.js"></script>
<script src="./resources/js/DefaultParcelController.js"></script>
<script src="./resources/js/DefaultWarehouseController.js"></script>
<script src="./resources/js/FooterController.js"></script>
<script src="./resources/js/OrderStateMappingController.js"></script>
<script src="./resources/js/PageControllerFactory.js"></script>
<script src="./resources/js/ShippingMethodsController.js"></script>
<script src="./resources/js/SidebarController.js"></script>
<script src="./resources/js/StateController.js"></script>
<script src="./resources/js/TemplateService.js"></script>
<script src="./resources/js/UtilityService.js"></script>
<script src="./resources/js/CountrySelectorController.js"></script>
<script src="./resources/js/LoginController.js"></script>
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

                stateUrl: "<?php echo $urlService->getEndpointUrl('ModuleState', 'getCurrentState') ?>",
                loginUrl: "<?php echo $urlService->getEndpointUrl('Login', 'login') ?>",
                dashboardGetStatusUrl: "<?php echo $urlService->getEndpointUrl('Dashboard', 'getStatus') ?>",
                defaultParcelGetUrl: "<?php echo $urlService->getEndpointUrl('DefaultParcel', 'getDefaultParcel') ?>",
                defaultParcelSubmitUrl: "<?php echo $urlService->getEndpointUrl('DefaultParcel', 'setDefaultParcel') ?>",
                defaultWarehouseGetUrl: "<?php echo $urlService->getEndpointUrl('DefaultWarehouse', 'getDefaultWarehouse') ?>",
                getSupportedCountriesUrl: "<?php echo $urlService->getEndpointUrl('DefaultWarehouse', 'getSupportedCountries') ?>",
                defaultWarehouseSubmitUrl: "<?php echo $urlService->getEndpointUrl('DefaultWarehouse', 'setDefaultWarehouse') ?>",
                defaultWarehouseSearchPostalCodesUrl: "<?php echo $urlService->getEndpointUrl('DefaultWarehouse', 'searchPostalCodes') ?>",
                shippingMethodsGetStatusUrl: "<?php echo $urlService->getEndpointUrl('ShippingMethods', 'getTaskStatus') ?>",
                shippingMethodsGetAllUrl: "<?php echo $urlService->getEndpointUrl('ShippingMethods', 'getAll') ?>",
                shippingMethodsActivateUrl: "<?php echo $urlService->getEndpointUrl('ShippingMethods', 'activate') ?>",
                shippingMethodsDeactivateUrl: "<?php echo $urlService->getEndpointUrl('ShippingMethods', 'deactivate') ?>",
                shippingMethodsSaveUrl: "<?php echo $urlService->getEndpointUrl('ShippingMethods', 'save') ?>",
                getSystemOrderStatusesUrl: "<?php echo $urlService->getEndpointUrl('OrderStateMapping', 'getSystemOrderStatuses') ?>",
                orderStatusMappingsGetUrl: "<?php echo $urlService->getEndpointUrl('OrderStateMapping', 'getMappings') ?>",
                orderStatusMappingsSaveUrl: "<?php echo $urlService->getEndpointUrl('OrderStateMapping', 'setMappings') ?>",
                debugGetStatusUrl: "<?php echo $urlService->getEndpointUrl('Debug', 'getStatus') ?>",
                debugSetStatusUrl: "<?php echo $urlService->getEndpointUrl('Debug', 'setStatus') ?>",
                autoConfigureStartUrl: "<?php echo $urlService->getEndpointUrl('AutoConfigure', 'start') ?>",
                getShippingCountriesUrl: "<?php echo $urlService->getEndpointUrl('ShippingCountries', 'getAll') ?>",

                templates: {
                    'required': {
                        'pl-login-page': '<?php echo json_encode(file_get_contents('/home/igor/Documents/Projects/packlink/pl_module_core/src/DemoUI/src/Views/resources/templates/login.html', true)) ?>',
                        'pl-register-page': "<?php echo json_encode(file_get_contents(__DIR__ . 'templates/register.html', true)) ?>"
                    }
                },
            }
        );

        Packlink.state.display();
    }, false);
</script>
</html>