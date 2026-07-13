<?php
// 更新処理（編集フォームから送られた値を検証してDBを UPDATE する）
require_once 'config.php';
require_login();
$me = current_user_id();

// POST以外でアクセスされたら一覧へ
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: select.php');
  exit;
}

// 1) どの案件を更新するか（id）を受け取り検証
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  header('Location: select.php?err=' . urlencode('更新対象が不正です。'));
  exit;
}

// 2) 入力値を受け取る
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

// 3) 入力チェック（バリデーション）
$errors = [];
if ($title === '')       { $errors[] = '案件名は必須です。'; }
if ($talent_name === '') { $errors[] = 'タレント名は必須です。'; }
if ($start_at === '')    { $errors[] = '開始日時は必須です。'; }
// 区分値はコードが定義済みかチェック。未知なら既定値へ
if (!array_key_exists($status, STATUS))     { $status = 1; }
if (!array_key_exists($category, CATEGORY)) { $category = 1; }

// datetime-local の "T" を空白へ。空欄は NULL に整える
$start_at = $start_at !== '' ? str_replace('T', ' ', $start_at) : null;
$end_at   = $end_at   !== '' ? str_replace('T', ' ', $end_at)   : null;
$place    = $place   !== '' ? $place   : null;
$address  = $address !== '' ? $address : null;
$client   = $client  !== '' ? $client  : null;
$memo     = $memo    !== '' ? $memo    : null;
$fee      = ($fee !== '' && is_numeric($fee)) ? (int)$fee : null;

// エラーがあれば編集画面へ戻す（idとメッセージ付き）
if ($errors) {
  header('Location: edit.php?id=' . $id . '&err=' . urlencode(implode(' ', $errors)));
  exit;
}

// 4) UPDATE（プリペアド。削除済みの行は更新しない。updated_at はDB側で自動更新）
//    「AND user_id = :me」で他人の案件を更新できないようにする（IDOR対策）
$sql = 'UPDATE bookings SET
          title       = :title,
          talent_name = :talent_name,
          category    = :category,
          place       = :place,
          address     = :address,
          start_at    = :start_at,
          end_at      = :end_at,
          status      = :status,
          fee         = :fee,
          client      = :client,
          memo        = :memo
        WHERE id = :id AND user_id = :me AND deleted_at IS NULL';
$stmt = $pdo->prepare($sql);
$stmt->execute([
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
  ':id'          => $id,
  ':me'          => $me,
]);

// 5) 更新後は一覧へ（PRGパターン）
header('Location: select.php?updated=1');
exit;
