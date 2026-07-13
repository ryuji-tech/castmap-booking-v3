# CastMap - 案件（スケジュール）管理アプリ v3

> タレントマネージャー向けスケジュール管理サービス「CastMap」の案件管理パーツ。
> **v3 で ログイン機能 を追加。ユーザーごとに自分の案件だけを管理できるようにした。**（v1/v2 とは別リポジトリ・別DB）

- GitHub: `castmap-booking-v3`（`castmap-booking-v2` の続編）
- 公開URL(v3): https://prema.sakura.ne.jp/booking_app_v3/login.php
- 使用技術: PHP / MySQL(MariaDB) / HTML / CSS / JavaScript

## （1）タイトル

**CastMap（案件管理パーツ）v3 — ログイン対応**

## （2）説明

タレントの「案件（タレント×場所×日時×ステータス）」を登録・一覧・編集・削除できる、ログイン制のWebアプリ。開発中のスケジュール管理ツール「CastMap」の心臓部で、「担当タレントが増えると全員の予定が頭に入らない」という課題を、案件を一元管理して認知負荷を下げることで解決するのが狙い。v1でC・R、v2でU・D（CRUD完成）を作り、**v3でログイン機能を足して、ユーザーごとにデータを分離した。**

## 技術仕様

### ファイル構成

| ファイル | 役割 | 分類 |
|---|---|---|
| `config.php` | DB接続（PDO）＋セッション開始＋共通関数・認証ヘルパー。※秘密情報を含みGitHub非公開 | — |
| `config.sample.php` | 接続設定テンプレート（公開用） | — |
| `db.sql` | テーブル作成用SQL（users 2名＋bookings サンプル） | — |
| `login.php` | ログイン画面＋ログイン処理（password_verify） | 認証 |
| `logout.php` | ログアウト（セッション破棄） | 認証 |
| `input.php` / `create.php` | 作成画面・作成処理（INSERT） | **C** |
| `select.php` | 一覧表示（SELECT）＋操作ボタン＋絞り込み | **R** |
| `edit.php` / `update.php` | 編集画面・更新処理（UPDATE） | **U** |
| `delete.php` | 削除処理（論理削除・POST限定） | **D** |
| `style.css` | スタイル | — |

### データベース構成（v2から継承。ログインで users を活用）

ログイン・地図ビューを見据え、**区分値（status / category）は文字列でなくコード(TINYINT)で管理**、削除は物理削除でなく**論理削除**にした。

**users**（ログイン用。今週は seed ユーザー1名で運用）

| カラム | 型 | 説明 |
|---|---|---|
| id | INT (PK) | ユーザーID |
| name | VARCHAR(50) | 表示名 |
| email | VARCHAR(255) UNIQUE | ログインID |
| password_hash | VARCHAR(255) | `password_hash()` の結果（平文は保存しない） |
| role | TINYINT | 1:producer 2:manager 3:admin |
| created_at / updated_at | DATETIME | |

**bookings**

| カラム | 型 | 説明 |
|---|---|---|
| id | INT (PK, AI) | 案件ID |
| user_id | INT (FK→users) | 所有者 |
| title | VARCHAR(100) | 案件名 |
| talent_name | VARCHAR(50) | タレント名（正規化は次段階） |
| category | **TINYINT** | 種別：1収録 2イベント 3撮影 4打合せ 5その他 |
| place / address | VARCHAR | 場所の呼び名／住所（地図用） |
| lat / lng | DECIMAL(10,7) | 緯度経度（取得は別課題・列だけ用意） |
| start_at / end_at / meet_at | DATETIME | 開始／終了／集合時間 |
| all_day | TINYINT(1) | 終日フラグ |
| status | **TINYINT** | 状態：1打診中 2仮 3確定 4完了 |
| fee | INT | ギャラ／予算（数値管理） |
| client | VARCHAR(100) | 取引先 |
| memo | TEXT | メモ |
| created_at / updated_at | DATETIME | 登録／最終更新（自動） |
| deleted_at | DATETIME NULL | 論理削除（NULL=有効） |

## （3）工夫した点

- **ログイン（認証）**：パスワードは `password_hash()` で保存し `password_verify()` で照合（平文はDBに持たない）。ログイン成功時に `session_regenerate_id(true)` でセッションIDを振り直し（セッション固定攻撃対策）。ログイン失敗メッセージは「メールまたはパスワードが違います」と一律にし、ユーザーの存在推測を防ぐ。
- **認可（ガード＋データ分離）**：ログイン必須ページは冒頭で `require_login()` を呼び未ログインを弾く。案件の全クエリに `AND user_id = :me` を付け、**URLの `?id=` を書き換えても他人の案件を閲覧・編集・削除できない（IDOR対策）**。
- **セキュリティ（CRUD共通）**：全SQLをプリペアドステートメント（SQLインジェクション対策）、表示は `htmlspecialchars()`（XSS対策）、`id` は `filter_input(FILTER_VALIDATE_INT)` で整数チェック。
- **区分値をコード(int)で管理（レビュー反映）**：`status`/`category` を TINYINT で保持し、表示名・色は `config.php` の定数マップ＋`status_label()` などで変換。ワークフロー順の並び替え・範囲チェックが素直になった。
- **論理削除**：`delete.php` は `deleted_at` に日時をセットし、全SELECTは `WHERE deleted_at IS NULL`。誤削除の復元・履歴保持ができる。
- **削除は必ずPOST＋確認モーダル**：GET削除の事故を防止し、一覧側で確認を挟んでから送信。
- **PRGパターン**：登録・更新・削除いずれもリダイレクトし二重処理を防止。
- **環境の自動切替＆秘密情報の分離**：`$_SERVER['SERVER_NAME']` でローカル/本番のDB設定を自動切替。`config.php` は `.gitignore` で除外し `config.sample.php` のみ公開。
- **UI・JS**：ステータス絞り込みタブ（件数バッジ付き）、キーワード絞り込み、開始日時→終了+2時間の自動補完。

## （4）難しかった点・次回

### 難しかった点
- **XAMPPのMySQLが起動しない**：`InnoDB: Unable to lock ibdata1 error: 35`。旧mysqldプロセスがデータファイルをロックしていたのが原因（Macのホスト名変更でpid管理がズレた）。ログの読み方とプロセスの止め方を学んだ。
- **PHP↔DBの仕組み**：`new PDO(...)` で接続しSQLを送って結果を受け取る流れ、プレースホルダの意味を1つずつ確認して腑に落とした。
- **さくらへのデプロイ**：DB名の接頭辞・「ユーザー名＝DB名」などさくら固有ルールに戸惑った。

### 今回やったこと（v3）
- ✅ **ログイン／ログアウト**を実装（`login.php` / `logout.php`、セッション管理）
- ✅ `current_user_id()` を **`$_SESSION['user_id']`** に切替、全案件クエリに `AND user_id = :me`（**IDOR対策**）
- ✅ seed ユーザー2名で、**ログインすると自分の案件だけが見える**ことを実演できるように

### 次回
- 削除・更新の **CSRFトークン**（フォーム改ざん対策）
- 新規ユーザー登録（register）画面、パスワード再設定
- `talents` 正規化、`lat`/`lng` を使った地図ビュー・タイムライン接続

## （5）備考

前回に引き続き現在業者側の回線のトラブルにて自身のネットワーク環境が崩壊しておりここ最近はモバイルネットワーク通信を駆使してちまちまと開発を行っている。普段のインターネット環境のありがたさを痛感。最近はAIとの対話も増えてきていたが、ネットワークの都合で自分で考える時間も増えた。新しいアイデアも浮かんできているのでもう少し汎用性、発展性のあるプロダクトを考えていきたい。

---

## ローカルでの動かし方

1. XAMPPでApache・MySQLを起動
2. phpMyAdminで `db.sql` を実行 → **v3専用DB `castmap_v3`** と users（2名）/ bookings を作成（v1/v2とは別DBで共存可）
3. `config.sample.php` を `config.php` にコピー（ローカル設定はそのままでOK）
4. `booking_app_v3` を `htdocs` に置き、`http://localhost/booking_app_v3/login.php` を開く
5. `tanaka@example.com` または `sato@example.com`（パスワードは `password`）でログイン。ユーザーごとに違う案件が見える
