# Laravel9tubu-public(laravel13対応版)

## 立ち上げ概要

Laravel9tubuyakisaitoソースを入手し、
ローカル環境でデプロイするものとなります。

必要なソースは以下にまとめてあります。

dockerソース

https://github.com/naritomo08/laravel_docker/tree/laravel13-postgres

つぶやきサイトソース

https://github.com/naritomo08/laravel9tubu-public/tree/laravel13-postgres

## 参考書籍

[プロフェッショナルWebプログラミング　Laravel](https://www.amazon.co.jp/gp/product/B09WMN18TR/ref=ppx_yo_dt_b_d_asin_title_351_o03?ie=UTF8&psc=1)

## 事前準備

mac+DockerCompose+vscode+gitでの環境を構築してること。
*Windowsでもwsl2＋Ubuntuで構築可能。

## 環境構築手順

### ベースリポジトリをクローンする

```bash
git clone -b laravel13 https://github.com/naritomo08/laravel_docker.git laraveldocker
cd laraveldocker
rm -rf .git
git clone -b laravel13 https://github.com/naritomo08/laravel9tubu-public.git backend
cd backend
rm -rf .git
```

### 環境構築用のシェルスクリプトを実行する

```bash
cd ..
chmod u+x build_env.sh && ./build_env.sh
```

### サイト設定を行う

```bash
PHPコンテナログイン
docker-compose exec app /bin/bash

Laracvelキャッシュクリア
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

DB初期化
php artisan migrate:fresh

DB初期化せずマイグレートのみする場合
php artisan migrate

Seeder作成管理者アカウント設定
php artisan db:seed --class=UsersSeeder
```

## 各種サイト確認する

### laravel

http://127.0.0.1:8080/tweet

### 利用規約・プライバシーポリシー・お問い合わせ

利用規約とプライバシーポリシーはMarkdownファイルで管理しています。画面表示時にMarkdownからHTMLへ変換されます。

| 表示ページ | Markdownファイル |
| --- | --- |
| `/terms` | `backend/resources/markdown/terms.md` |
| `/privacy` | `backend/resources/markdown/privacy.md` |

お問い合わせはログイン後に表示される `/contact` から送信できます。ログイン中ユーザーのユーザー名とメールアドレスを固定表示し、問い合わせ内容を入力すると、管理者宛にメールがキューで送信されます。

管理者アドレスを変更したい場合は、`backend/.env` の `ADMIN_EMAIL` に送信先メールアドレスを指定してください。未設定時は `admin@tubuyaki.com` 宛に送信されます。

本文を変更する場合は、該当するMarkdownファイルを編集してビルドしてください。

編集後に表示が更新されない場合は、PHPコンテナ内で以下を実行してビューキャッシュを削除してください。

```bash
php artisan view:clear
```

### adminer(DB管理ツール)

http://127.0.0.1:8081

* ログイン情報
  - サーバ: db
  - ユーザ名: phper
  - パスワード: secret
  - データベース: laravel_local

### mailhog(メールサーバ)

http://127.0.0.1:8025

## コンテナ起動する方法

`docker-compose.yml`が存在するフォルダーで以下のコマンドを実行する。

```bash
docker-compose up -d
```

## コンテナ停止する方法

`docker-compose.yml`が存在するフォルダーで以下のコマンドを実行する。

```bash
docker-compose stop
```

## コンテナ削除する方法

`docker-compose.yml`が存在するフォルダーで以下のコマンドを実行する。

```bash
docker-compose down
```

## 起動中のコンテナに入る

### PHPコンテナ

```bash
docker-compose exec app /bin/bash
```

### DBコンテナ

```bash
docker-compose exec db /bin/bash
```

## その他

つぶやきサイトソース変更して反映したい際は以下コマンドを実施すること。

```bash
docker-compose build && docker-compose up -d
```

### スケジュール実行の確認

スケジュール実行プロセスの確認:

```bash
docker-compose logs -f scheduler
```

キューワーカーの確認:

```bash
docker-compose logs -f queue
```

手動で1回だけスケジューラ評価を走らせたい場合:

```bash
docker-compose exec app php artisan schedule:run
```

## LaravelTest

```bash
PHPコンテナで動かす。
docker-compose exec app /bin/bash
php artisan optimize:clear

ユニットテスト
php artisan test

ブラウザテスト
php artisan dusk
```

> `php artisan test` はテスト用設定 `.env.testing` を使い、`db.test` 上の `laravel_test` データベースに接続します。
>テスト実行前に `php artisan optimize:clear` を実行し、通常アプリの設定キャッシュが残っていない状態で動かしてください。

### テストコマンドごとの実行対象

- `php artisan test`
  - `tests/Unit`
  - `tests/Feature`
- `php artisan dusk`
  - `tests/Browser`

#### php artisan test で実行されるテスト(134テスト)

```bash
tests/Unit/ExampleTest.php
tests/Unit/Services/TweetImageServiceTest.php
tests/Unit/Services/TweetQueryServiceTest.php
tests/Unit/Services/TweetServiceTest.php
tests/Unit/Services/UserDeletionServiceTest.php
tests/Unit/View/Components/Tweet/FormattedContentTest.php
tests/Feature/AccountTest.php
tests/Feature/Admin/UserManagementTest.php
tests/Feature/Auth/AuthenticationTest.php
tests/Feature/Auth/GoogleAuthTest.php
tests/Feature/Auth/EmailVerificationTest.php
tests/Feature/Auth/PasswordConfirmationTest.php
tests/Feature/Auth/PasswordResetTest.php
tests/Feature/Auth/RegistrationTest.php
tests/Feature/Console/DeleteUnverifiedUsersTest.php
tests/Feature/Console/SendDailyTweetCountMailTest.php
tests/Feature/ContactTest.php
tests/Feature/ExampleTest.php
tests/Feature/LegalDocumentTest.php
tests/Feature/Tweet/ContentLengthTest.php
tests/Feature/Tweet/DeleteTest.php
tests/Feature/Tweet/LatestTest.php
tests/Feature/Tweet/ProtectionTest.php
tests/Feature/Tweet/ScheduledPostTest.php
tests/Feature/Tweet/SearchTest.php
tests/Feature/Tweet/SecretModeTest.php
tests/Feature/Tweet/UpdateTest.php
```

| ファイル | テスト概要 |
| --- | --- |
| `tests/Unit/ExampleTest.php` | `true` が `true` であることだけを確認するサンプルテスト。 |
| `tests/Unit/Services/TweetImageServiceTest.php` | つぶやき画像の保存・紐付けと、画像削除時のファイル・中間テーブル・画像レコード削除を検証。 |
| `tests/Unit/Services/TweetQueryServiceTest.php` | つぶやき一覧取得時のいいね状態・件数の一括付与を検証。 |
| `tests/Unit/Services/TweetServiceTest.php` | `TweetService::checkOwnTweet` の自分の投稿判定を検証。 |
| `tests/Unit/Services/UserDeletionServiceTest.php` | ユーザー削除時に本人のつぶやき、いいね、つぶやき画像が削除され、他ユーザーのデータが残ることを検証。 |
| `tests/Unit/View/Components/Tweet/FormattedContentTest.php` | ツイート本文表示コンポーネントのURLリンク化、改行反映、HTMLエスケープを検証。 |
| `tests/Feature/AccountTest.php` | アカウント設定の表示制御、プロフィール更新、メール変更時の再認証、パスワード更新、通知メール設定、本人統計、本人の予約投稿一覧の表示・動的取得・編集・削除、退会処理を検証。 |
| `tests/Feature/Admin/UserManagementTest.php` | 管理者画面のメールアドレス表示専用化、複数管理者の昇格/降格、自己権限変更拒否、Seeder固定管理者の維持、管理者削除拒否、集計・ユーザー一覧の動的取得、予約投稿一覧の表示・動的取得・削除、通知メール設定表示、Google連携表示、非管理者の操作拒否を検証。 |
| `tests/Feature/Auth/AuthenticationTest.php` | ログイン画面表示、正しい認証でログイン成功、非管理者ログイン時に古い管理画面遷移先へ戻されないこと、誤パスワードでログイン失敗を検証。 |
| `tests/Feature/Auth/GoogleAuthTest.php` | Google連携、連携済みアカウントでのGoogleログイン、未連携メールでの拒否、連携解除、Google API失敗時のエラー表示を検証。 |
| `tests/Feature/Auth/EmailVerificationTest.php` | メール認証画面、認証状態API、未認証ユーザーの監視表示、登録直後とメール変更後で未認証通知の削除警告表示が切り替わること、署名付きURLでの認証成功、ログインなし・別ユーザー認証中・ホスト差異ありでも認証できること、無効な認証リンクでは認証されないことを検証。 |
| `tests/Feature/Auth/PasswordConfirmationTest.php` | パスワード確認画面表示、正しい/誤ったパスワードでの確認結果を検証。 |
| `tests/Feature/Auth/PasswordResetTest.php` | 再設定リンク送信、再設定画面表示、トークンを使ったパスワード再設定を検証。 |
| `tests/Feature/Auth/RegistrationTest.php` | ユーザー登録画面表示、新規登録後の認証状態・遷移先、認証済みユーザーへの紹介メール送信を検証。 |
| `tests/Feature/Console/DeleteUnverifiedUsersTest.php` | 登録後1時間を過ぎた未認証ユーザーの削除と、既存ユーザーのメール変更後アカウントが削除対象外であることを検証。 |
| `tests/Feature/Console/SendDailyTweetCountMailTest.php` | 日次送付メールに各ユーザーのつぶやき数・いいね数が含まれること、未認証ユーザーや通知無効ユーザーへ送信されないことを検証。 |
| `tests/Feature/ContactTest.php` | 問い合わせ画面のログイン必須、ログイン済みユーザー情報の固定表示、管理者アドレスへの問い合わせメールのキュー投入、バリデーション失敗時に送信されないことを検証。 |
| `tests/Feature/ExampleTest.php` | `/tweet` が `200 OK` を返すことを確認する基本スモークテスト。 |
| `tests/Feature/LegalDocumentTest.php` | 利用規約・プライバシーポリシーのMarkdown表示と、ゲスト画面・通常画面で共通リンクと問い合わせリンクが表示されることを検証。 |
| `tests/Feature/Tweet/ContentLengthTest.php` | つぶやき作成・編集で設定値に基づく最大文字数バリデーションが効くこと、投稿フォーム・編集フォームに最大文字数表示と動的カウント用の設定が出力されることを検証。 |
| `tests/Feature/Tweet/DeleteTest.php` | ログインユーザーが投稿削除後に一覧へ遷移すること、検索画面から削除した場合は検索条件を維持して戻り通知が出ること、Seeder作成つぶやきはSeeder固定管理者本人以外の管理者が削除できないこと、既存のSeeder固定管理者つぶやきを削除保護対象に自動反映できることを検証。 |
| `tests/Feature/Tweet/LatestTest.php` | `/tweet/latest` の新着取得と、ユーザー名・画像更新時の差分HTML返却を検証。 |
| `tests/Feature/Tweet/ProtectionTest.php` | Seeder固定管理者だけが一般ユーザーのつぶやきを保護/解除できること、Seeder固定管理者のつぶやきは保護対象外であること、保護されたつぶやきはSeeder固定管理者以外が編集・削除できないこと、Seeder固定管理者は保護済みつぶやきを削除できるが編集できないこと、保護表記とメニュー表示を検証。 |
| `tests/Feature/Tweet/ScheduledPostTest.php` | 予約投稿の作成、予約時刻までの一覧・検索・新着取得からの非表示、予約時刻後の表示、予約日時を表示時刻として扱うこと、予約日時順で並ぶことを検証。 |
| `tests/Feature/Tweet/SearchTest.php` | つぶやき検索画面のログイン必須、本文検索、空検索0件、ページネーション、空ページ時の最終ページ移動、ユーザー検索時は入力文字列では絞り込まず選択ユーザーで絞り込むこと、ユーザー選択候補の表示・JSON取得、登録直後の未認証ユーザー除外とメール変更後の未認証ユーザー表示、`user:""` を通常キーワードとして扱うことを検証。 |
| `tests/Feature/Tweet/SecretModeTest.php` | シークレットモードのつぶやきが投稿者本人と管理者だけに表示されること、作成・編集時に設定が保存されること、検索・新着取得・いいね状態取得・いいね操作で第三者に参照されないことを検証。 |
| `tests/Feature/Tweet/UpdateTest.php` | つぶやき編集時の画像追加・削除、画像合計4枚までのバリデーション、検索画面から編集した場合は検索条件を維持して戻り通知が出ること、公開済み投稿は予約投稿へ戻せず、未公開の予約投稿だけ予約日時を編集できることを検証。 |

#### php artisan dusk で実行されるテスト(1テスト)

```bash
tests/Browser/LoginTest.php
```

| ファイル | テスト概要 |
| --- | --- |
| `tests/Browser/LoginTest.php` | ブラウザでログイン操作を行い、`/tweet` への遷移と表示文言を確認するE2Eテスト。 |

## Google認証

通常登録したユーザーは、ログイン後の `/account` から Google アカウントを連携できます。連携後はログイン画面の `Googleでログイン` から同じアカウントへ入れます。

Google OAuth を使うには、Google Cloud Console で OAuth クライアントを作成し、以下を `backend/.env` に設定してください。

```bash
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8080/auth/google/callback
```

Google 側の OAuth 設定にも、環境に対応した同じリダイレクト URI を登録します。

```bash
http://127.0.0.1:8080/auth/google/callback
```

本実装では、Google ログイン時に自動で新規登録や自動連携はしません。既存アカウントへ Google を紐付けたユーザーのみ、Google 認証でログインできます。

以下のページを参考にgoogle認証情報を入手してください。

https://qiita.com/mnoguchi/items/7d7795444afb9d9dafa8

## 管理者画面

管理者画面にアクセスしたい際は
初期値では以下のアカウントで入ることができます。

ユーザ：admin@tubuyaki.com
パスワード：test

トップ画面にリンクが乗り、各アカウント情報確認と削除が行えます。

管理者アカウント情報は `backend/.env` の以下の値で変更できます。

設定ファイル：

```bash
SEED_ADMIN_NAME=admin
SEED_ADMIN_EMAIL=admin@tubuyaki.com
SEED_ADMIN_PASSWORD=test
```

適用：

```bash
docker-compose build && docker-compose up -d
docker-compose exec app /bin/bash
php artisan db:seed --class=UsersSeeder
```

## 複数つぶやきをテスト的に入れたい場合

以下のファイルで60件の画像リンク付き書き込みを追加できます。
実施するたびに追記が可能。

設定ファイル：

```bash
database/seeeders/TweetsSeeder.php
```

適用(事前にUsersSeederを実施していること。)：

```bash
docker-compose exec app /bin/bash
php artisan db:seed --class=TweetsSeeder
```

先ほど出したUsersSeederと同時に全消しして入れる場合

```bash
docker-compose exec app /bin/bash
php artisan migrate:fresh
php artisan db:seed
```

一旦消して初期状態にしたい場合は以下のようにする。
UsersSeeder(管理者作成)は必ず適用すること。

```bash
docker-compose exec app /bin/bash
rm -rf storage/app/public/images/*
php artisan migrate:fresh
php artisan db:seed --class=UsersSeeder
```

## つぶやき数通知メール時間変更

```bash
vi backend/app/Console/Kernel.php

以下の行の時刻部分を書き換える。

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('mail:send-daily-tweet-count-mail')
            ->dailyAt('7:00');
        $schedule->command('users:delete-unverified')->everyMinute();
    }

適用：

docker-compose build && docker-compose up -d
```

>この構成では `scheduler` コンテナが `php artisan schedule:work` を常駐実行し、
>`queue` コンテナが `php artisan queue:work` を常駐実行します。
>そのため、`Kernel.php` に登録したスケジュール実行と、`ShouldQueue` のメール送信が自動で流れます。

## npm依存の脆弱性対応

フロントエンド依存の脆弱性は `package-lock.json` で固定されたバージョンに対して確認します。
Dockerfile の `npm ci --no-audit --no-fund` は監査を省略するだけで、脆弱性を自動修正しません。
ビルドの再現性を保つため、Docker build 中に `npm audit fix` を実行せず、手動で `package-lock.json` を更新してコミットしてください。

```bash
cd backend

# 脆弱性を確認
npm audit --audit-level=moderate

# 修正可能な依存を package-lock.json に反映
npm audit fix

# 修正後に脆弱性が残っていないか確認
npm audit --audit-level=moderate

# フロントエンドのビルド確認
npm run build
```

`npm audit fix` 後は、主に `package-lock.json` が更新されます。
`npm audit fix --force` はメジャーバージョン更新を含む可能性があるため、通常の `npm audit fix` で解消できない場合だけ、影響範囲を確認してから実行してください。
