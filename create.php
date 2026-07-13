<?php
// 作成処理（フォームから送られた値を検証してDBに INSERT する）
require_once 'config.php';
require_login();   // 未ログインは弾く

// POST以外でアクセスされたら一覧へ
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: select.php');
  exit;
}

// 1) 入力値を受け取る（未入力は空文字に）
$title       = trim($_POST['title'] ?? '');
$talent_name = trim($_POST['talent_name'] ?? '');
$place       = trim($_POST['place'] ?? '');
$address     = trim($_POST['address'] ?? '');
$start_at    = trim($_POST['start_at'] ?? '');
$end_at      = trim($_POST['end_at'] ?? '');
$status      = (int)($_POST['status'] ?? 1);
$category    = (int)($_POST['category'] ?? 1);
$fee         = trim($_POST['fee'] ?? '');
$client      = trim($_POST['client'] ?? '');
$memo        = trim($_POST['memo'] ?? '');

// 2) 入力チェック（バリデーション）
$errors = [];
if ($title === '')       { $errors[] = '案件名は必須です。'; }
if ($talent_name === '') { $errors[] = 'タレント名は必須です。'; }
if ($start_at === '')    { $errors[] = '開始日時は必須です。'; }
// 区分値はコードが定義済みか（ホワイトリスト）をチェック。未知なら既定値へ
if (!array_key_exists($status, STATUS))     { $status = 1; }
if (!array_key_exists($category, CATEGORY)) { $category = 1; }

// datetime-local の "T" を空白へ。空欄は NULL に整える
$start_at = $start_at !== '' ? str_replace('T', ' ', $start_at) : null;
$end_at   = $end_at   !== '' ? str_replace('T', ' ', $end_at)   : null;
$place    = $place   !== '' ? $place   : null;
$address  = $address !== '' ? $address : null;
$client   = $client  !== '' ? $client  : null;
$memo     = $memo    !== '' ? $memo    : null;
// ギャラは数値のみ。空なら NULL
$fee      = ($fee !== '' && is_numeric($fee)) ? (int)$fee : null;

// エラーがあれば入力画面へ戻す（メッセージ付き）
if ($errors) {
  header('Location: input.php?err=' . urlencode(implode(' ', $errors)));
  exit;
}

// 3) INSERT（プリペアドステートメントでSQLインジェクション対策）
$sql = 'INSERT INTO bookings
          (user_id, title, talent_name, category, place, address, start_at, end_at, status, fee, client, memo)
        VALUES
          (:user_id, :title, :talent_name, :category, :place, :address, :start_at, :end_at, :status, :fee, :client, :memo)';
$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':user_id'     => current_user_id(),   // ログイン中ユーザーを所有者に
  ':title'       => $title,
  ':talent_name' => $talent_name,
  ':category'    => $category,
  ':place'       => $place,
  ':address'     => $address,
  ':start_at'    => $start_at,
  ':end_at'      => $end_at,
  ':status'      => $status,
  ':fee'         => $fee,
  ':client'      => $client,
  ':memo'        => $memo,
]);

// 4) 登録後は一覧へ移動（PRGパターン：再読み込みでの二重登録を防ぐ）
header('Location: select.php?done=1');
exit;
