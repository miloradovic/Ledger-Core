<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Internal;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Internal]
#[Small]
class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function testThatTrueIsTrue(): void
    {
        static::assertTrue(true);
    }
}
