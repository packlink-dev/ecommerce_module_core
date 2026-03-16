<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\IntegrationRegistration;

use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationDataProviderInterface;

class MockIntegrationRegistrationDataProvider implements IntegrationRegistrationDataProviderInterface
{
    /**
     * @var string|null
     */
    private $integrationId = null;

    public function getRegistrationPayload()
    {
        return array(
            'integration_type' => $this->getIntegrationType(),
            'integration' => array(
                'guid' => $this->getIntegrationGuid(),
                'name' => $this->getIntegrationName(),
            ),
            'webhooks' => array(
                'http_header_name' => 'X-Packlink-Webhook-Secret',
                'http_header_value' => $this->getWebhookSecret(),
                'status_update_url' => $this->getIntegrationWebhookStatusUpdateUrl(),
            ),
        );
    }

    public function getIntegrationGuid() { return 'mock_generated_guid'; }
    public function getWebhookSecret() { return 'mock_webhook_secret'; }
    public function getIntegrationId() { return $this->integrationId; }
    public function setIntegrationId($integrationId) { $this->integrationId = $integrationId; }

    /**
     * Allows tests to pre-seed the stored integration ID.
     *
     * @param string|null $id
     */
    public function setStoredIntegrationId($id) { $this->integrationId = $id; }

    public function getIntegrationType() { return 'mock_integration_type'; }
    public function getIntegrationName() { return 'mockIntegration'; }
    public function getIntegrationWebhookStatusUpdateUrl() { return 'https://mock.url/webhook'; }

    public function deleteIntegrationData() { $this->integrationId = null; }
}