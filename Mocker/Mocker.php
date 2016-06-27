<?php

namespace Totaltrash\Testing\Mocker;

use Symfony\Component\HttpKernel\KernelInterface;
use Prophecy\Prophet;

/**
 * Mocker.
 *
 * Interacts with the MockContainer to override services. Also provides some
 * helpers around creating prophecy mocks
 */
class Mocker
{
    /**
     * @var MockContainer
     */
    private $container;

    /**
     * @var Prophet
     */
    private $prophet;

    /**
     * Constructor.
     * 
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->container = $kernel->getContainer();
        $this->prophet = new Prophet();
    }

    /**
     * Reset the mocker.
     *
     * Clears the prophet of promises, and resets the mock container
     */
    public function reset()
    {
        $this->prophet = new Prophet();
        $this->container->initialize();
    }

    /**
     * Mock a service.
     * 
     * @param string $serviceId
     * @param string $className
     *
     * @return Mock object
     */
    public function mockService($serviceId, $className)
    {
        if ($this->container instanceof MockContainer === false) {
            throw new \LogicException(
                'Container is not able to store mocked services. Override AppKernel::getContainerBaseClass with "Totaltrash\Testing\Mocker\MockContainer"'
            );
        }

        $mock = $this->createMock($className);

        $this->container->overrideService($serviceId, $mock->reveal());

        return $mock;
    }

    /**
     * Create a prophecy mock helper.
     * 
     * @param string $className
     *
     * @return Mock object
     */
    public function createMock($className)
    {
        return $this->prophet->prophesize($className);
    }

    /**
     * Check the predictions for the mocks.
     */
    public function checkPredictions()
    {
        $this->prophet->checkPredictions();
    }

    /**
     * Unmock the service.
     * 
     * @param string $serviceId
     */
    public function unmockService($serviceId)
    {
        $this->container->removeMock($serviceId);
    }

    /**
     * Get the service from the mock container.
     * 
     * @param string $serviceId
     *
     * @return Mock Object
     */
    public function getMockedService($serviceId)
    {
        return $this->container->get($serviceId);
    }
}
