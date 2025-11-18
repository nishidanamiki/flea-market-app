<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class RegisterTest extends TestCase
{
    use RefreshDatabase;

    // テスト用の基本データ
    private function validData($overrides = [])
    {
        return array_merge([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_名前が未入力の場合はバリデーションメッセージが表示される()
    {
        $this->get('/register')->assertStatus(200);

        $response = $this->post('/register', $this->validData(['name' => '']));

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    public function test_メールアドレスが未入力の場合はバリデーションメッセージが表示される()
    {
        $this->get('/register')->assertStatus(200);

        $response = $this->post('/register', $this->validData(['email' => '']));

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_パスワードが未入力の場合はバリデーションメッセージが表示される()
    {
        $this->get('/register')->assertStatus(200);

        $response = $this->post('/register', $this->validData([
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_パスワードが7文字以下の場合はバリデーションメッセージが表示される()
    {
        $this->get('/register')->assertStatus(200);

        $response = $this->post('/register', $this->validData([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]));

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    public function test_パスワード確認用と一致しない場合はバリデーションメッセージが表示される()
    {
        $this->get('/register')->assertStatus(200);

        $response = $this->post('/register', $this->validData([
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]));

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    public function test_全て入力されている場合会員登録が完了してメール認証誘導画面に遷移する()
    {
        $this->get('/register')->assertStatus(200);

        $response = $this->post('/register', $this->validData());

        $response->assertRedirect('/email/verify');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_会員登録後に認証メールが送信される()
    {
        Notification::fake();

        $this->post('/register', $this->validData());

        $user = User::first();

        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
    }
}
