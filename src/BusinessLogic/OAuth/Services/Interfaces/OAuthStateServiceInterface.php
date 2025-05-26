<?php

namespace Packlink\BusinessLogic\OAuth\Services\Interfaces;

interface OAuthStateServiceInterface
{
    const CLASS_NAME = __CLASS__;

    /**
     * @param $tenantId
     *
     * @return mixed
     */
    public function generateAndSaveState($tenantId);

    /**
     * @param string $tenantId
     *
     * @return string
     */
    public function generate($tenantId);

    /**
     * @param string $encodedState
     *
     * @return string
     */
    public function extractTenantIdFromState($encodedState);

    /**
     * @param string $encodedState
     *
     * @return void
     */
    public function validateState($encodedState);

    /**
     * @param string $tenantId
     * @param string $state
     *
     * @return void
     */
    public function saveState($tenantId, $state);

    /**
     * @param string $tenantId
     * @param string $state
     *
     * @return string
     */
    public function getState($tenantId, $state);
}