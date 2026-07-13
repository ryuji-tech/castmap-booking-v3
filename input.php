<?php
// 作成画面（フォームを表示するだけ。送信先は create.php）
require_once 'config.php';
require_login();   // 未ログインは弾く

// create.php でエラーがあった場合、メッセージを ?err=... で受け取って表示
$err = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>案件を登録 — CastMap</title>
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
  <h1>案件を登録</h1>

  <?php if ($err): ?>
    <div class="msg"><?= h($err) ?></div>
  <?php endif; ?>

  <form class="card" action="create.php" method="post">
    <div class="field">
      <label>案件名 <span class="req">*必須</span></label>
      <input type="text" name="title" placeholder="例：音楽番組 収録" required>
    </div>

    <div class="row">
      <div class="field">
        <label>タレント名 <span class="req">*必須</span></label>
        <input type="text" name="talent_name" placeholder="例：春野 凜" required>
      </div>
      <div class="field">
        <label>種別</label>
        <select name="category">
          <?php foreach (CATEGORY as $code => $label): ?>
            <option value="<?= $code ?>"<?= $code === 1 ? ' selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="field">
      <label>場所</label>
      <input type="text" name="place" placeholder="例：東京 スタジオA">
    </div>

    <div class="field">
      <label>住所 <span class="muted">（地図表示用・任意）</span></label>
      <input type="text" name="address" placeholder="例：東京都渋谷区神南1-1-1">
    </div>

    <div class="row">
      <div class="field">
        <label>開始日時 <span class="req">*必須</span></label>
        <input type="datetime-local" name="start_at" id="start_at" required>
      </div>
      <div class="field">
        <label>終了日時</label>
        <input type="datetime-local" name="end_at" id="end_at">
      </div>
    </div>

    <div class="row">
      <div class="field">
        <label>ステータス</label>
        <select name="status">
          <?php foreach (STATUS as $code => $info): ?>
            <option value="<?= $code ?>"<?= $code === 1 ? ' selected' : '' ?>><?= h($info['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>ギャラ/予算 <span class="muted">（円・任意）</span></label>
        <input type="number" name="fee" min="0" step="1000" placeholder="例：150000">
      </div>
    </div>

    <div class="field">
      <label>取引先</label>
      <input type="text" name="client" placeholder="例：〇〇テレビ">
    </div>

    <div class="field">
      <label>メモ</label>
      <textarea name="memo" placeholder="補足（条件・集合場所など）"></textarea>
    </div>

    <button class="btn" type="submit">登録する</button>
    <a class="btn sub" href="select.php">一覧へ戻る</a>
  </form>
</div>

<script>
// 開始日時を選んだら、終了が空なら自動で +2時間を初期値に入れる（JSの小ワザ）
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
