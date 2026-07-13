<?php
/* =========================================================
   config.sample.php ＝ 設定のテンプレート（GitHubに上げる用）
   -------------------------------------------------------
   ■ 使い方
   1) このファイルをコピーして「config.php」という名前で保存
   2) config.php の本番(さくら)欄に、自分のDB情報を入れる
   3) config.php は GitHub に上げない（.gitignore で除外済み）
      → DB名・サーバ名・ユーザー・パスワードの流出を防ぐため
   ========================================================= */

// ログイン状態保持のためセッション開始（全ページ共通）
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$host = $_SERVER['SERVER_NAME'] ?? '';

if ($host === 'localhost' || $host === '127.0.0.1') {
  // ===== ローカル(XAMPP) =====
  $DB_HOST = 'localhost';
  $DB_NAME = 'castmap_v3';   // v3は専用DB（v1/v2とは分離）
  $DB_USER = 'root';
  $DB_PASS = '';
} else {
  // ===== 本番(さくらのレンタルサーバー) =====  ※ここは各自のconfig.phpで埋める
  $DB_HOST = 'mysqlXXXX.db.sakura.ne.jp'; // 割り当てDBサーバ名
  $DB_NAME = 'prema_xxxx';               // 作成したDB名（先頭の prema_ ＝アカウント名は自動で付く）
  $DB_USER = 'prema_xxxx';               // さくらは「ユーザー名＝データベース名」
  $DB_PASS = 'ここにDBパスワード';          // ←config.sample.phpには本物を書かない
}

try {
  $pdo = new PDO(
    "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]
  );
} catch (PDOException $e) {
  exit('DB接続エラー: ' . $e->getMessage());
}

function h($str) {
  return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ステータス（区分値：コード管理。前回レビュー「intで持つべき」を反映）
const STATUS = [
  1 => ['label' => '打診中', 'class' => 'st-1'],
  2 => ['label' => '仮',     'class' => 'st-2'],
  3 => ['label' => '確定',   'class' => 'st-3'],
  4 => ['label' => '完了',   'class' => 'st-4'],
];
const CATEGORY = [
  1 => '収録', 2 => 'イベント', 3 => '撮影', 4 => '打合せ', 5 => 'その他',
];
function status_label($code)   { return STATUS[(int)$code]['label']   ?? '—'; }
function status_class($code)   { return STATUS[(int)$code]['class']   ?? ''; }
function category_label($code) { return CATEGORY[(int)$code]          ?? '—'; }
// ログイン（認証・認可）関連
function current_user_id()   { return $_SESSION['user_id']   ?? null; }
function current_user_name() { return $_SESSION['user_name'] ?? ''; }
function require_login() {
  if (!current_user_id()) { header('Location: login.php'); exit; }
}
