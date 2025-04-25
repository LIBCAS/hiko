<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSwitcherTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function testUserSeeCzechLocalisation(): void
    {
        $this->actingAs($this->user)
            ->withSession(['locale' => 'cs'])
            ->get('/letters')
            ->assertSee(__('hiko.letters', [], 'cs'));
    }

    public function testUserSeeEnglishLocalisation(): void
    {
        $this->actingAs($this->user)
            ->withSession(['locale' => 'en'])
            ->get('/letters')
            ->assertSee(__('hiko.letters', [], 'en'));
    }
}
