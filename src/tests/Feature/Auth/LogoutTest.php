<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;
    public function test_ログアウトができる()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_未ログイン状態でもログアウトするとトップページに遷移する()
    {
        $response = $this->post('/logout');

        $response->assertRedirect('/');

        $this->assertGuest();
    }
}
