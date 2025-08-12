<?php

namespace Packlink\BusinessLogic\Http\Subscription;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\Http\HttpClient;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Http\Subscription\DTO\SubscriptionFeatureBehaviours;

class SubscriptionProxy extends Proxy
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    const API_VERSION = 'pro/';

    /**
     * Returns subscription feature behaviors for the current merchant.
     *
     * @return SubscriptionFeatureBehaviours Subscription feature behaviours response.
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getSubscriptionFeatureBehaviours()
    {
        $response = $this->call(HttpClient::HTTP_METHOD_GET, 'subscription-feature-behaviours');

        $data = $response->decodeBodyToArray();
        if (!is_array($data)) {
            $data = array();
        }

        return SubscriptionFeatureBehaviours::fromArray($data);
    }
}