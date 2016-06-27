<?php

namespace Totaltrash\Testing\Assert;

use PHPUnit_Framework_Assert;

/**
 * Generic assertions that apply to all types of assert.
 */
abstract class Assert
{
    /**
     * Helper method to generate PHPUnit assertion exceptions.
     * 
     * @param bool   $success
     * @param string $failMessage Message to use upon failure
     */
    public function assert($success, $failMessage)
    {
        PHPUnit_Framework_Assert::assertTrue($success, $success ? '' : $failMessage);
    }
}
