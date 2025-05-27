<?php

namespace Logeecom\Tests\BusinessLogic\OAuth;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Packlink\BusinessLogic\OAuth\Exceptions\InvalidOAuthStateException;
use Packlink\BusinessLogic\OAuth\Models\OAuthState;
use Packlink\BusinessLogic\OAuth\Services\OAuthStateService;

class OAuthStateServiceTest extends BaseTestWithServices
{
    /**
     * OAuthState service instance.
     *
     * @var OAuthStateService
     */
    public $service;

    /**
     * @var MemoryRepository
     */
    public $repository;

    /**
     * @before
     * @inheritdoc
     */
    protected function before()
    {
        parent::before();

        RepositoryRegistry::registerRepository(OAuthState::CLASS_NAME, MemoryRepository::getClassName());

        $this->repository = RepositoryRegistry::getRepository(OAuthState::CLASS_NAME);

        $this->service = new OAuthStateService($this->repository);
    }

    /**
     * @return void
     */
    public function testGenerateReturnsValidBase64String()
    {
        $state = $this->service->generate('test-tenant');

        $decoded = json_decode(base64_decode($state), true);

        $this->assertArrayHasKey('tenantId', $decoded);
        $this->assertArrayHasKey('state', $decoded);
        $this->assertEquals('test-tenant', $decoded['tenantId']);
    }

    /**
     * Test for the extractTenantIdFromState() method
     */
    public function testExtractTenantIdFromState()
    {
        $tenantId = 'tenant_123';
        $encodedState = $this->service->generate($tenantId);

        $extractedTenantId = $this->service->extractTenantIdFromState($encodedState);

        $this->assertEquals($tenantId, $extractedTenantId);
    }

    /**
     * Test for the saveState() method
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testSaveState()
    {
        $tenantId = 'tenant_123';
        $randState = 'random_state_value';

        $this->service->saveState($tenantId, $randState);

        $state = $this->service->getState($tenantId, $randState);

        $this->assertEquals($tenantId, $state->getTenantId());
        $this->assertEquals($randState, $state->getState());
    }

    /**
     * @return void
     *
     * @throws InvalidOAuthStateException
     * @throws QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\EntityClassException
     */
    public function testValidateStateWithValidData()
    {
        $tenantId = 'tenant_456';

        $this->service->generateAndSaveState($tenantId);

        $filter = new QueryFilter();
        $filter->where('tenantId', '=', $tenantId);
        /**@var OAuthState $state*/
        $state = $this->repository->selectOne($filter);

        $result = $this->service->validateState($state->getState());

        $this->assertTrue($result);
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testValidateStateThrowsExceptionForInvalidStructure()
    {
        $encodedState = base64_encode(json_encode(array('invalid_key' => 'value')));

        try {
            $this->service->validateState($encodedState);
            $this->fail('Expected InvalidOAuthStateException was not thrown.');
        } catch (InvalidOAuthStateException $e) {
            $this->assertEquals('Invalid state structure.', $e->getMessage());
        }
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testValidateStateThrowsExceptionForMissingState()
    {
        $encodedState = base64_encode(json_encode(array(
            'tenantId' => 'non_existent_tenant',
            'state' => 'non_existent_state'
        )));

        try {
            $this->service->validateState($encodedState);
            $this->fail('Expected InvalidOAuthStateException was not thrown.');
        } catch (InvalidOAuthStateException $e) {
            $this->assertEquals('State mismatch.', $e->getMessage());
        }
    }
}