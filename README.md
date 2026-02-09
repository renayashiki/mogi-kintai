# coachtech フリマ

## 環境構築
**Dockerビルド**
1. `git clone git@github.com:renayashiki/mogi-furima.git`
2. `cd /coachtech/laravel/mogi-furima`
3. DockerDesktopアプリを立ち上げる
4. `docker-compose up -d --build`

<br>

**Laravel環境構築**
1. `docker-compose exec php bash`
2. `# composer install`<br>
`chmod -R 777 storage bootstrap/cache`
>※Docker環境でのログ出力や画像保存時のエラー（Permission Denied）を防ぐため、必ず実行してください。
3. 「.env.example」ファイルを 「.env」ファイルに命名を変更。または、新しく.envファイルを作成

    `# cp .env.example .env`

4. 環境変数の設定
>DBの設定ー.envに以下の環境変数を修正
``` text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
<br>

>メール認証機能の設定・確認方法 (MailHog)ー.env.example` にあらかじめ MailHog 用の設定を記載しているため、環境構築後すぐにメール認証のテストが可能ですが、.envが以下の内容と相違がある場合は、書き換えが必要なため必ず内容を確認してください。

- src/.envに以下のように変更
``` bash
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```
<br>


5. アプリケーションキーの作成
``` bash
php artisan key:generate
```

<br>

6. データベース構築と初期データ投入
``` bash
php artisan migrate:fresh --seed
```
<br>

7. テストの実行方法（PHPUnit）
<br>
今回の模擬案件では、仕様に基づいた自動テスト（PHPUnit）を実装しています。
評価の際は、以下の手順でテスト用環境を構築することで、テストを実行いただけます。

<br>

###### 1.テスト用データベースの作成
コマンドラインから、テスト専用のデータベースを作成してください。

``` bash
docker compose exec mysql bash
```
``` bash
# mysql -u root -p

- パスワード: root
```
> DBを作成
```sql
CREATE DATABASE IF NOT EXISTS mogi_furima_test;
```
<br>

> 権限付与
```sql
GRANT ALL PRIVILEGES ON mogi_furima_test.* TO 'laravel_user'@'%';
```
<br>

```sql
FLUSH PRIVILEGES;
EXIT;
```
<br>


###### 2.テスト用環境変数の設定
phpコンテナにて
``` bash
cp .env .env.testing
```
- .env.testing の修正箇所：
> DB_DATABASE=  ※　ここ今回のに変更mogi_furima_test

<br>

###### 3.テスト用データベースのマイグレーション
``` bash
php artisan migrate --env=testing
```

###### 4.テストの実行
- 全てのテストを一括実行
``` bash
php artisan test
```
- 特定の機能のみを実行（例：会員登録機能）
``` bash
php artisan test tests/Feature/RegisterTest.php
```


###### 5.テストケース ファイル一覧
- 仕様書内のテストケース一覧のID毎にテストケースファイルを作成しております。参考としてお使いください。

![テストケース ファイル表](./docs/テストケース一覧.png)
![テストケース ファイル表](./docs/テストケース後半②.png)

<br>

## 動作確認用情報
### テスト用アカウント
- 以下のダミーデータを使用して、管理者・一般ユーザーそれぞれの機能を動作確認いただけます。 パスワードは全アカウント共通で password123 です。


 **管理者アカウント**

``` bash
 'name' => '管理者',
 'email' => 'admin@coachtech.com',
 'password' => 'password123'
```
> 管理者システムへログインし、操作を確認できます。

**一般ユーザー：西 伶奈（一般ユーザー画面確認用）**
``` bash
 'email' => 'reina.n@coachtech.com',
 'password' => 'password123'
```
> 一般ユーザーページの機能確認ができます。見本の2023年6月の前後3か月と現在の日付から前3ヶ月のデータをダミーデータとして入れており、その期間内で機能確認が可能です。

**特記事項**
- 管理者ページの申請一覧見本UIでは「山田 花子」というユーザーが存在しますが、スタッフ一覧ページ見本UIでは「山田 花子」は存在していません。見本を完全再現し、管理者の申請一覧画面でのみは表示される形で再現しています。


**スタッフ一覧に存在する他ユーザー**
> パスワードは共通のため、以下のユーザー別メールアドレスでログインし操作確認も可能です。ダミーデータが挿入されている期間は上記同様、見本の2023年6月の前後3か月と現在の日付から前3ヶ月です。

| 名前 | メールアドレス | ログイン時の状態 |
| :--- | :--- | :--- | :--- | 山田 太郎 | `taro.y@coachtech.com`| 増田 一世 | `issei.m@coachtech.com`|秋田 朋美 | `tomomi.a@coachtech.com`| 中西 教夫 | `norio.n@coachtech.com` |
<br>

## 使用技術(実行環境)
- PHP: 8.1.34
- Laravel: 8.75 以上
- MySQL: 8.0.26
- nginx: 1.21.1


## URL
- 開発環境：http://localhost/
- phpMyAdmin: http://localhost:8080/
- メール認証サイト(MailHog):http://localhost:8025/

<br>


## テーブル設計
![usersテーブル](./docs/usersテーブル模擬1.png)

![profilesテーブル](./docs/profilesテーブル模擬①.png)
> profilesテーブルはユーザーの表示用・編集用情報を管理するためにusersテーブルと分離しています。認証情報とプロフィール情報の責務を分け、将来的な拡張や管理性を考慮した設計としています

![productsテーブル](./docs/productsテーブル模擬1.png)
> 本アプリでは「1商品につき画像1枚」という仕様のため、商品画像はproductsテーブルにimageカラムとして保持しています。この点に関しては、将来的な画像対応よりも仕様に忠実な形でシンプルな構成を選択しました。将来的に複数画像で対応する場合は別テーブルに切り出す想定。

![categoriesテーブル](./docs/categoriesテーブル模擬①.png)

![product_categoriesテーブル](./docs/product_categoriesテーブル(中間)模擬①.png)
> categories と products は多対多関係となり、中間テーブルproduct_categories を使用しています。複合ユニーク：(category_id, product_id)

![commentsテーブル](./docs/コメントテーブル①.png)
> comments はユーザーと商品の中間テーブルではなく、独立したエンティティとして管理しています

![favoritesテーブル](./docs/新favoritesテーブル.png)
> favoritesは中間テーブルではなく、独立したエンティティとして管理しています。※ 複合ユニーク：(user_id, product_id)

![odersテーブル](./docs/新オーダーテーブル.png)



## ER図
![ER Diagram](./er_diagram.drawio.png)

