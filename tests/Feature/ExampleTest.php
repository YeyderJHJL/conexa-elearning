<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_root_redirects_each_visitor_to_their_home(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['rol' => 'trabajador']))
            ->get('/')->assertRedirect(route('dashboard'));

        $this->actingAs(User::factory()->create(['rol' => 'admin']))
            ->get('/')->assertRedirect('/admin');
    }
}
