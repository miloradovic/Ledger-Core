<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Small]
class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    #[Test]
    public function that_true_is_true(): void
    {
        static::assertTrue(true);
    }
}
