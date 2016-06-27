<?php

namespace Totaltrash\Testing\Mocker;

use Symfony\Component\DependencyInjection\Container;

/**
 * Mock Container.
 *
 * Extends a symfony container so services can be overridden with mocks
 */
class MockContainer extends Container
{
    /**
     * @var array
     */
    protected static $mockedServices = array();

    /**
     * Override the service with a mock.
     * 
     * @param string      $id   Service ID to override
     * @param Mock object $mock
     */
    public function overrideService($id, $mock)
    {
        self::$mockedServices[$id] = $mock;
    }

    /**
     * Return the mock, or real service if a mock not overridden for the given 
     * service ID.
     * 
     * {@inheritdoc}
     */
    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        if ($this->hasMockedService($id)) {
            return self::$mockedServices[$id];
        }

        return parent::get($id, $invalidBehavior);
    }

    /**
     * Remove the mock with the given service ID.
     * 
     * @param string $id
     */
    public function removeMock($id)
    {
        if ($this->hasMockedService($id)) {
            unset(self::$mockedServices[$id]);
        }
    }

    /**
     * Initialize the mock container.
     */
    public function initialize()
    {
        self::$mockedServices = array();
    }

    /**
     * Has the service been overridden?
     * 
     * @param string $id
     *
     * @return bool
     */
    public function hasMockedService($id)
    {
        return array_key_exists($id, self::$mockedServices);
    }
}
