<?php
// 編集画面（既存の案件を1件読み込み、フォームに初期表示する。送信先は update.php）
require_once 'config.php';
require_login();
$me = current_user_id();

// 1) URLの ?id=... を受け取り、整数として検証
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  header('Location: select.php');
  exit;
}

// 2) 対象の1件を取得（自分の案件のみ／削除済みは編集させない）
//    「AND user_id = :me」で他人の案件をURL書き換えで開けないようにする（IDOR対策）
$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id AND user_id = :me AND deleted_at IS NULL');
$stmt->execute([':id' => $id, ':me' => $me]);
$b = $stmt->fetch();

if (!$b) {
  header('Location: select.php?err=' . urlencode('指定された案件は見つかりませんでした。'));
  exit;
}

$err = $_GET['err'] ?? '';

// datetime-local は "YYYY-MM-DDTHH:MM" 形式が必要。DBの値を変換する小関数
function dt_local($s) {
  if (!$s) return '';
  return date('Y-m-d\TH:i', strtotime($s));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>案件を編集 — CastMap</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <div class="logo"><span class="pin"></span>CastMap</div>
  <nav>
    <a href="select.php">案件一覧</a>
    <a href="input.php">＋ 新規登録</a>
    <span class="who">👤 <?= h(current_user_name()) ?></span>
    <a href="logout.php" class="logout">ログアウト</a>
  </nav>
</header>

<div class="wrap">
  <h1>案件を編集 <span class="muted">#<?= h($b['id']) ?></span></h1>

  <?php if ($err): ?>
    <div class="msg"><?= h($err) ?></div>
  <?php endif; ?>

  <form class="card" action="update.php" method="post">
    <input type="hidden" name="id" value="<?= h($b['id']) ?>">

    <div class="field">
      <label>案件名 <span class="req">*必須</span></label>
      <input type="text" name="title" value="<?= h($b['title']) ?>" required>
    </div>

    <div class="row">
      <div class="field">
        <label>タレント名 <span class="req">*必須</span></label>
        <input type="text" name="talent_name" value="<?= h($b['talent_name']) ?>" required>
      </div>
      <div class="field">
        <label>種別</label>
        <select name="category">
          <?php foreach (CATEGORY as $code => $label): ?>
            <option value="<?= $code ?>"<?= (int)$b['category'] === $code ? ' selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="field">
      <label>場所</label>
      <input type="text" name="place" value="<?= h($b['place']) ?>" placeholder="例：東京 スタジオA">
    </div>

    <div class="field">
      <label>住所 <span class="muted">（地図表示用・任意）</span></label>
      <input type="text" name="address" value="<?= h($b['address']) ?>" placeholder="例：東京都渋谷区神南1-1-1">
    </div>

    <div class="row">
      <div class="field">
        <label>開始日時 <span class="req">*必須</span></label>
        <input type="datetime-local" name="start_at" id="start_at" value="<?= h(dt_local($b['start_at'])) ?>" required>
      </div>
      <div class="field">
        <label>終了日時</label>
        <input type="datetime-local" name="end_at" id="end_at" value="<?= h(dt_local($b['end_at'])) ?>">
      </div>
    </div>

    <div class="row">
      <div class="field">
        <label>ステータス</label>
        <select name="status">
          <?php foreach (STATUS as $code => $info): ?>
            <option value="<?= $code ?>"<?= (int)$b['status'] === $code ? ' selected' : '' ?>><?= h($info['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>ギャラ/予算 <span class="muted">（円・任意）</span></label>
        <input type="number" name="fee" min="0" step="1000" value="<?= h($b['fee']) ?>" placeholder="例：150000">
      </div>
    </div>

    <div class="field">
      <label>取引先</label>
      <input type="text" name="client" value="<?= h($b['client']) ?>" placeholder="例：〇〇テレビ">
    </div>

    <div class="field">
      <label>メモ</label>
      <textarea name="memo" placeholder="補足（条件・集合場所など）"><?= h($b['memo']) ?></textarea>
    </div>

    <button class="btn" type="submit">変更を保存</button>
    <a class="btn sub" href="select.php">一覧へ戻る</a>
  </form>
</div>

<script>
document.getElementById('start_at').addEventListener('change', function () {
  const end = document.getElementById('end_at');
  if (this.value && !end.value) {
    const d = new Date(this.value);
    d.setHours(d.getHours() + 2);
    const pad = n => String(n).padStart(2, '0');
    end.value = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }
});
</script>
</body>
</html>
