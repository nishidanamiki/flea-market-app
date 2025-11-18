<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    //テスト用の基本データ
    private function credentials($overrides = [])
    {
        return array_merge([
            'email' => 'test@example.com',
            'password' => 'password123',
        ], $overrides);
    }

    public function test_メールアドレスが未入力の場合はバリデーションメッセージが表示される()
    {
        $this->get('/login')->assertStatus(200);

        $response = $this->post('/login', $this->credentials(['email' => '']));

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_パスワードが未入力の場合はバリデーションメッセージが表示される()
    {
        $this->get('/login')->assertStatus(200);

        $response = $this->post('/login', $this->credentials(['password' => '']));

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_入力情報が間違っている場合バリデーションメッセージが表示される()
    {
        $this->get('/login')->assertStatus(200);

        $response = $this->post('/login', [
            'email' => 'notfound@example.com',
            'password'=> 'wrongpassword',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    public function test_正しい情報が入力された場合ログイン処理が実行される()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->get('/login')->assertStatus(200);

        $response = $this->post('/login', $this->credentials());

        $response->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }
}
