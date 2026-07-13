<?php
// ログイン画面＋ログイン処理（同じファイルで表示とPOSTを扱う）
require_once 'config.php';

// すでにログイン済みなら一覧へ
if (current_user_id()) {
  header('Location: select.php');
  exit;
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email    = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $err = 'メールアドレスとパスワードを入力してください。';
  } else {
    // メールでユーザーを1件引く（プリペアド）
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // パスワードはハッシュと照合（password_verify）。生パスワードはDBに無い
    if ($user && password_verify($password, $user['password_hash'])) {
      // セッション固定攻撃対策：ログイン成功時にセッションIDを振り直す
      session_regenerate_id(true);
      $_SESSION['user_id']   = (int)$user['id'];
      $_SESSION['user_name'] = $user['name'];
      header('Location: select.php');
      exit;
    } else {
      // どちらが違うかは言わない（存在推測を防ぐため一律メッセージ）
      $err = 'メールアドレスまたはパスワードが違います。';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ログイン — CastMap</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <div class="logo"><span class="pin"></span>CastMap</div>
</header>

<div class="wrap narrow">
  <h1>ログイン</h1>

  <?php if ($err): ?>
    <div class="msg"><?= h($err) ?></div>
  <?php endif; ?>

  <form class="card" action="login.php" method="post">
    <div class="field">
      <label>メールアドレス</label>
      <input type="email" name="email" placeholder="例：tanaka@example.com" required autofocus>
    </div>
    <div class="field">
      <label>パスワード</label>
      <input type="password" name="password" placeholder="パスワード" required>
    </div>
    <button class="btn" type="submit">ログイン</button>
  </form>

  <p class="muted" style="margin-top:14px;">
    動作確認用：tanaka@example.com ／ sato@example.com（パスワードはどちらも <code>password</code>）
  </p>
</div>
</body>
</html>
