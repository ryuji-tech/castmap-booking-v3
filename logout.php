<?php
// ログアウト処理（セッションを破棄してログイン画面へ）
require_once 'config.php';

// セッション変数を空にし、セッション自体を破棄
$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
