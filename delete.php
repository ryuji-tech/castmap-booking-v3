<?php
// 削除処理（一覧の「削除」ボタン→確認モーダル→POSTで送信されて削除する）
//   物理削除ではなく deleted_at に日時を入れる「論理削除」。
//   誤削除の復元・履歴保持ができ、案件データを安全に扱える。
require_once 'config.php';
require_login();
$me = current_user_id();

// 削除は必ず POST で受ける（GETで削除できるとURL踏みや誤クリックで消える危険があるため）
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: select.php');
  exit;
}

// 1) 削除対象の id を受け取り、整数として検証
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  header('Location: select.php?err=' . urlencode('削除対象が不正です。'));
  exit;
}

// 2) 論理削除（deleted_at に現在時刻をセット）。自分の案件・未削除の行のみ
//    「AND user_id = :me」で他人の案件を消せないようにする（IDOR対策）
$stmt = $pdo->prepare('UPDATE bookings SET deleted_at = NOW() WHERE id = :id AND user_id = :me AND deleted_at IS NULL');
$stmt->execute([':id' => $id, ':me' => $me]);

// 3) 削除後は一覧へ（PRGパターン）
header('Location: select.php?deleted=1');
exit;
