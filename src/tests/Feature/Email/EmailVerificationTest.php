<?php

namespace Tests\Feature\Email;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証はこちらからボタン押下でメール認証サイトに遷移する()
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/email/verify')->assertStatus(200);

        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id,
            'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verifyUrl);

        $response->assertRedirect('/mypage/profile');
    }

    public function test_メール認証完了後プロフィール設定画面に遷移する()
    {
        $user = User::factory()->unverified()->create();

        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id,
            'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verifyUrl);

        $this->assertNotNull($user->fresh()->email_verified_at);

        $response->assertRedirect('/mypage/profile');
    }
}

