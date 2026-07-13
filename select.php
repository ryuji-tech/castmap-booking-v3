<?php
// 参照 & 表示処理（DBから案件を取り出して一覧表示）
require_once 'config.php';
require_login();                 // 未ログインならログイン画面へ（認可ガード）
$me = current_user_id();         // ログイン中ユーザーID。以降すべてのクエリを自分の案件に限定

// 絞り込み用：ステータスのタブ（?status=3 など。値はコード）。無効値は「すべて」に
$filter = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT);
if (!array_key_exists((int)$filter, STATUS)) { $filter = 0; }  // 0 = すべて

// SELECT（自分の案件のみ／削除済みは除外／開始日時の早い順）。指定があれば status で絞る
if ($filter) {
  $stmt = $pdo->prepare('SELECT * FROM bookings WHERE user_id = :me AND deleted_at IS NULL AND status = :status ORDER BY start_at ASC');
  $stmt->execute([':me' => $me, ':status' => $filter]);
} else {
  $stmt = $pdo->prepare('SELECT * FROM bookings WHERE user_id = :me AND deleted_at IS NULL ORDER BY start_at ASC');
  $stmt->execute([':me' => $me]);
}
$bookings = $stmt->fetchAll();

// 各ステータスの件数（タブのバッジ用）。自分の案件・削除済みは数えない
$counts = [0 => 0];
foreach (STATUS as $code => $info) { $counts[$code] = 0; }
$cstmt = $pdo->prepare('SELECT status, COUNT(*) AS c FROM bookings WHERE user_id = :me AND deleted_at IS NULL GROUP BY status');
$cstmt->execute([':me' => $me]);
$rows = $cstmt->fetchAll();
foreach ($rows as $r) {
  $counts[0] += (int)$r['c'];
  if (isset($counts[(int)$r['status']])) { $counts[(int)$r['status']] = (int)$r['c']; }
}

// 日時を見やすく整える小関数（NULLなら「—」）
function fmt_dt($s) {
  if (!$s) return '—';
  return date('n/j H:i', strtotime($s));
}
// ギャラの表示（NULLなら「—」、数値は 3桁区切り＋円）
function fmt_fee($v) {
  return ($v === null || $v === '') ? '—' : '¥' . number_format((int)$v);
}

$err = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>案件一覧 — CastMap</title>
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
  <h1>案件一覧 <span class="muted">（<?= count($bookings) ?>件<?= $filter ? ' / ' . h(status_label($filter)) . 'で絞込中' : '' ?>）</span></h1>

  <?php if (isset($_GET['done'])): ?>
    <div class="msg ok">✓ 案件を登録しました。</div>
  <?php elseif (isset($_GET['updated'])): ?>
    <div class="msg ok">✓ 案件を更新しました。</div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="msg ok">✓ 案件を削除しました。</div>
  <?php endif; ?>

  <?php if ($err): ?>
    <div class="msg"><?= h($err) ?></div>
  <?php endif; ?>

  <!-- ステータス絞り込みタブ（値はコード） -->
  <div class="tabs">
    <a class="tab<?= $filter === 0 ? ' on' : '' ?>" href="select.php">すべて <span class="cnt"><?= $counts[0] ?></span></a>
    <?php foreach (STATUS as $code => $info): ?>
      <a class="tab<?= $filter === $code ? ' on' : '' ?>" href="select.php?status=<?= $code ?>"><?= h($info['label']) ?> <span class="cnt"><?= $counts[$code] ?></span></a>
    <?php endforeach; ?>
  </div>

  <div class="toolbar">
    <input type="text" id="search" placeholder="🔍 タレント名・案件名・場所で絞り込み">
    <a class="btn" href="input.php">＋ 新規登録</a>
  </div>

  <?php if (!$bookings): ?>
    <div class="card empty">
      <?= $filter ? 'この条件の案件はありません。' : 'まだ案件がありません。「＋ 新規登録」から追加してください。' ?>
    </div>
  <?php else: ?>
    <table id="list">
      <thead>
        <tr>
          <th>案件名</th><th>タレント</th><th>種別</th><th>場所</th><th>開始</th><th>終了</th><th>ギャラ</th><th>ステータス</th><th class="ta-r">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?= h($b['title']) ?></td>
            <td><?= h($b['talent_name']) ?></td>
            <td><span class="cat"><?= h(category_label($b['category'])) ?></span></td>
            <td><?= h($b['place'] ?? '—') ?></td>
            <td><?= fmt_dt($b['start_at']) ?></td>
            <td><?= fmt_dt($b['end_at']) ?></td>
            <td class="nowrap"><?= fmt_fee($b['fee']) ?></td>
            <td><span class="badge <?= h(status_class($b['status'])) ?>"><?= h(status_label($b['status'])) ?></span></td>
            <td class="ta-r nowrap">
              <a class="act edit" href="edit.php?id=<?= h($b['id']) ?>">編集</a>
              <!-- 削除は必ずPOST。JSで確認モーダルを挟む -->
              <form class="del-form" action="delete.php" method="post" data-title="<?= h($b['title']) ?>">
                <input type="hidden" name="id" value="<?= h($b['id']) ?>">
                <button type="submit" class="act del">削除</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<!-- 削除確認モーダル -->
<div class="modal-bg" id="modalBg">
  <div class="modal">
    <h3>案件を削除しますか？</h3>
    <p class="modal-target" id="modalTarget"></p>
    <p class="modal-note">一覧から見えなくなります（データは復元可能な形で保持されます）。</p>
    <div class="modal-actions">
      <button type="button" class="btn sub" id="modalCancel">キャンセル</button>
      <button type="button" class="btn danger" id="modalOk">削除する</button>
    </div>
  </div>
</div>

<script>
// --- キーワード絞り込み（ページ再読み込みなし） ---
const search = document.getElementById('search');
if (search) {
  search.addEventListener('input', function () {
    const kw = this.value.trim().toLowerCase();
    document.querySelectorAll('#list tbody tr').forEach(tr => {
      tr.style.display = tr.textContent.toLowerCase().includes(kw) ? '' : 'none';
    });
  });
}

// --- 削除の確認モーダル ---
const modalBg = document.getElementById('modalBg');
const modalTarget = document.getElementById('modalTarget');
let pendingForm = null;

document.querySelectorAll('.del-form').forEach(form => {
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    pendingForm = this;
    modalTarget.textContent = '「' + this.dataset.title + '」';
    modalBg.classList.add('show');
  });
});

document.getElementById('modalCancel').addEventListener('click', () => {
  modalBg.classList.remove('show');
  pendingForm = null;
});
document.getElementById('modalOk').addEventListener('click', () => {
  if (pendingForm) pendingForm.submit();
});
modalBg.addEventListener('click', (e) => {
  if (e.target === modalBg) { modalBg.classList.remove('show'); pendingForm = null; }
});
</script>
</body>
</html>
