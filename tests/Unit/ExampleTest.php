<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

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
