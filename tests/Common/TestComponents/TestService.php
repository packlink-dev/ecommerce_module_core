<?php

namespace Logeecom\Tests\Common\TestComponents;

class TestService implements TestServiceInterface
{
    private $instanceNumber;

    /**
     * TestHttpClient constructor.
     * @param $instanceNumber
     */
    public function __construct($instanceNumber)
    {
        $this->instanceNumber = $instanceNumber;
    }

    /**
     * @return mixed
     */
    public function getInstanceNumber()
    {
        return $this->instanceNumber;
    }

}