# COACHTECH フリマアプリ

Laravel × Docker を用いて開発したフリマアプリです。
ユーザー登録、メール認証、出品、購入、いいね、コメント、商品検索、Stripe 決済などの基本機能を備えています。

## 環境構築

**Docker ビルド**

1.リポジトリのクローン

```bash
git clone https://github.com/nishidanamiki/flea-market-app.git
cd flea-market-app
```

2.DockerDesktop アプリを立ち上げる  
3.`docker compose up -d --build`

> _Mac の M1・M2 チップの PC の場合、`no matching manifest for linux/arm64/v8 in the manifest list entries`のメッセージが表示されビルドができないことがあります。
> エラーが発生する場合は、docker-compose.yml ファイルの「mysql」内に「platform」の項目を追加で記載してください_

```yaml
mysql:
  platform: linux/x86_64 # ← この文を追加
  image: mysql:8.0.26
  environment:
```

**Laravel 環境構築**

1.`docker compose exec php bash`  
2.`composer install`  
3.「.env.example」ファイルをコピーし「.env」ファイルを作成

```bash
cp .env.example .env
```

4..env に以下の環境変数を追加

```text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="no-reply@coachtech-fleamarket.local"
MAIL_FROM_NAME="COACHTECHフリマ"

STRIPE_KEY=your_key
STRIPE_SECRET=your_secret
```

5.アプリケーションキーの作成

```bash
php artisan key:generate
```

6.マイグレーションの実行

```bash
php artisan migrate
```

7.シーディングの実行

```bash
php artisan db:seed
```

8.ストレージリンクの生成

```bash
php artisan storage:link
```

## Stripe の設定（決済機能を利用する場合）

このアプリでは Stripe（テストモード）で決済を行います。

### 1. Stripe テストキーの取得

以下の URL から「公開可能キー（pk*test*〜）」と
「シークレットキー（sk*test*〜）」を取得してください。

https://dashboard.stripe.com/test/apikeys

### 2. .env に設定

```env
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxx
```

※ 上記はダミーです。実際のテストキーを使用してください。

### 3. テスト決済用カード番号

Stripe のテストカード：

- カード番号：4242 4242 4242 4242
- 有効期限：未来の日付
- CVC：任意の 3 桁
- 郵便番号：任意

## テスト環境の準備

1. `.env.testing`を作成。

```bash
cp .env .env.testing
```

テスト用 DB 名を設定(.env.testing 内)

```env
APP_ENV=testing
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=demo_test
DB_USERNAME=root
DB_PASSWORD=root

CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=log
```

2.テスト用データベースを作成

```bash
docker compose exec mysql mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS demo_test;"
```

3.テスト用マイグレーション

```bash
php artisan migrate --env=testing
```

4.テスト実行

```bash
php artisan test
```

## 使用技術（実行環境）

- PHP 7.4.33
- Laravel 8.83.29
- MySQL 8.0.26

## ER 図

![ER図](docs/er-diagram.png)

## URL

- 開発環境: http://localhost/
- phpMyAdmin: http://localhost:8080/
- MailHog: http://localhost:8025
