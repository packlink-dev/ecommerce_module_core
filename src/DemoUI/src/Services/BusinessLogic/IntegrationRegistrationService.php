<?php

namespace Packlink\DemoUI\Services\BusinessLogic;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationServiceInterface;

/**
 * Class IntegrationRegistrationService (demo stub).
 *
 * Packlink integration registration (POST /integrations) requires a publicly reachable
 * HTTPS webhook URL and a recognized integration type, which a local demo running on
 * localhost cannot provide - so the real registration always fails and blocks login.
 *
 * This stub persists a dummy integration ID locally (so login completes and the Proxy's
 * auto-registration check passes for later calls) WITHOUT calling Packlink. It is intended
 * for the DemoUI only.
 *
 * @package Packlink\DemoUI\Services\BusinessLogic
 */
class IntegrationRegistrationService implements IntegrationRegistrationServiceInterface
{
    /**
     * Dummy integration identifier used by the demo.
     */
    const DEMO_INTEGRATION_ID = 'demo-ui-integration-id';

    /**
     * Persists and returns a dummy integration ID (no Packlink call).
     *
     * @return string
     */
    public function registerIntegration()
    {
        $config = $this->getConfig();
        $existing = $config->getIntegrationId();
        if (!empty($existing)) {
            return $existing;
        }

        $config->setIntegrationId(self::DEMO_INTEGRATION_ID);

        return self::DEMO_INTEGRATION_ID;
    }

    /**
     * @return bool
     */
    public function disconnectIntegration()
    {
        return true;
    }

    /**
     * @return string|null
     */
    public function updateIntegrationUrl()
    {
        return $this->getConfig()->getIntegrationId();
    }

    /**
     * @return string|null
     */
    public function getIntegrationId()
    {
        return $this->getConfig()->getIntegrationId();
    }

    /**
     * @return Configuration
     */
    private function getConfig()
    {
        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);

        return $config;
    }
}
