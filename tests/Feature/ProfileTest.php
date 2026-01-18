<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Small;
use Tests\TestCase;

#[Small]
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function testProfilePageIsDisplayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function testProfileInformationCanBeUpdated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        static::assertSame('Test User', $user->name);
        static::assertSame('test@example.com', $user->email);
        static::assertNull($user->email_verified_at);
    }

    public function testEmailVerificationStatusIsUnchangedWhenTheEmailAddressIsUnchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        static::assertNotNull($user->refresh()->email_verified_at);
    }

    public function testUserCanDeleteTheirAccount(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        static::assertNull($user->fresh());
    }

    public function testCorrectPasswordMustBeProvidedToDeleteAccount(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        static::assertNotNull($user->fresh());
    }
}
