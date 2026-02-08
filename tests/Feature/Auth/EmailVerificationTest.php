<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Small;
use Tests\TestCase;

#[Small]
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function testEmailVerificationScreenCanBeRendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function testEmailCanBeVerified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1((string) $user->email)],
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $freshUser = $user->fresh();
        static::assertNotNull($freshUser);
        static::assertTrue($freshUser->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false) . '?verified=1');
    }

    public function testEmailIsNotVerifiedWithInvalidHash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')],
        );

        $this->actingAs($user)->get($verificationUrl);

        $freshUser = $user->fresh();
        static::assertNotNull($freshUser);
        static::assertFalse($freshUser->hasVerifiedEmail());
    }
}
