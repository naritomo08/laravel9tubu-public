# Laravel9tubu-public

## 立ち上げ概要

Laravel9tubuyakisaitoソースを入手し、
ローカル環境でデプロイするものとなります。

必要なソースは以下にまとめてあります。

dockerソース

https://github.com/naritomo08/laravel_docker

つぶやきサイトソース

https://github.com/naritomo08/laravel9tubu-public

## 参考書籍

[プロフェッショナルWebプログラミング　Laravel](https://www.amazon.co.jp/gp/product/B09WMN18TR/ref=ppx_yo_dt_b_d_asin_title_351_o03?ie=UTF8&psc=1)

## 事前準備

mac+DockerCompose+vscode+gitでの環境を構築してること。
*Windowsでもｗｓｌ２＋Ubuntuで実施可能。

## 環境構築手順

### ベースリポジトリをクローンする

```bash
git clone -b tubuyaki https://github.com/naritomo08/laravel_docker.git laraveldocker
cd laraveldocker
rm -rf .git
git clone -b v20260110 https://github.com/naritomo08/laravel9tubu-public.git backend
cd backend
rm -rf .git
```

### 環境構築用のシェルスクリプトを実行する

```bash
chmod u+x build_env.sh && ./build_env.sh
```

### サイト設定を行う

```bash
PHPコンテナログイン
docker-compose exec app /bin/bash

つぶやき機能投稿画像参照リンク作成（新たに開発する場合は必要なし）
php artisan storage:link
Laracvelキャッシュクリア
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

DBマイグレーション実施
php artisan migrate

管理者アカウント設定
php artisan db:seed --class=UsersSeeder
```

### 各種サイト確認する

## サイトURL

### laravel

http://127.0.0.1:8080/tweet

### adminer(DB管理ツール)

http://127.0.0.1:8081

* ログイン情報
  - サーバ: laravel_db
  - ユーザ名: laravel
  - パスワード: password
  - データベース: laravel

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
docker-compose exec laravel_php /bin/bash
```

### DBコンテナ

```bash
docker-compose exec laravel_db /bin/bash
```

## LaravelTest(CICDにいれる予定)

```bash
PHPコンテナで動かす。

ユニットテスト
php artisan test tests/Unit/Services/TweetServiceTest.php

フィーチャーテスト
php artisan test tests/Feature/Tweet/DeleteTest.php

上記テストをまとめて実施
vendor/bin/phpunit
```

## 管理者画面

管理者画面にアクセスしたい際は
以下のアカウントで入ることができます。

ユーザ：admin@tubuyaki.com
パスワード：test

トップ画面にリンクが乗り、各アカウント情報確認と削除が行えます。

管理者アカウント情報は以下のファイルを変更して再度適用してください。

設定ファイル：

```bash
database/seeeders/UsersSeeder.php
```

適用：

```bash
docker-compose exec laravel_php /bin/bash
cd project
php artisan db:seed --class=UsersSeeder
```
