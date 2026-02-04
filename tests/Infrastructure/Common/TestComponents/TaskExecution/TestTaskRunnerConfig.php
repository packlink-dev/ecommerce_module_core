<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\TaskExecution\AsyncProcessUrlProviderInterface;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerConfig;

class TestTaskRunnerConfig extends TaskRunnerConfig
{
    private $asyncUrlProviderUrl;
    private $autoConfigUrl;

    public function __construct(Configuration $config, AsyncProcessUrlProviderInterface $asyncUrlProvider)
    {
        parent::__construct($config, $asyncUrlProvider);

        $this->asyncUrlProviderUrl = $asyncUrlProvider->getAsyncProcessUrl('auto-configure');
        $this->setAutoConfigurationUrl($asyncUrlProvider);
        $this->autoConfigUrl = $this->asyncUrlProviderUrl;
    }
    /**
     * @return void
     */
    public function setAutoConfigurationUrl($url)
    {
        $this->autoConfigUrl = $url;
    }

    public function resetAutoConfigurationUrl() {
        $this->setAutoConfigurationUrl($this->asyncUrlProviderUrl);
    }

    /**
     * @return string
     */
    public function getAutoConfigurationUrl()
    {
        return $this->autoConfigUrl;
    }

}