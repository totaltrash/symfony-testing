<?php

namespace Totaltrash\Testing\Symfony;

/**
 * Helpers for getting the Symfony kernel and container.
 */
trait ContainerAware
{
    /**
     * Get the Symfony kernel.
     * 
     * @return Symfony\Component\HttpKernel\Kernel
     */
    public static function getKernel()
    {
        if (null === static::$kernel) {
            static::$kernel = static::createKernel();
        }
        if (!static::$kernel->getContainer()) {
            static::$kernel->boot();
        }

        return static::$kernel;
    }

    /**
     * Get the Symfony container.
     * 
     * @return Symfony\Component\DependencyInjection\Container
     */
    public function getContainer()
    {
        return $this->getKernel()->getContainer();
    }
}
