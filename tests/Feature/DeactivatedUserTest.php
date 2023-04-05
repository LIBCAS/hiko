<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeactivatedUserTest extends TestCase
{
    use RefreshDatabase;

    protected $deactivatedUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->deactivatedUser = User::factory()->create([
            'deactivated_at' => now(),
        ]);
    }

    public function testDeactivatedUserIsRedirectedToLogin(): void
    {
        $this->actingAs($this->deactivatedUser)
            ->get('/letters')
            ->assertRedirect('login');
    }
}
