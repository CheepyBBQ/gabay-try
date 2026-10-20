<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('student');

if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'post' || $action === 'reply') {
        $body = trim($_POST['body'] ?? '');
        $parent = $action === 'reply' ? (int)($_POST['parent_id'] ?? 0) : null;
        if ($body === '' || mb_strlen($body) > 1000) {
            flash('error', 'Write something first. Posts can be up to 1000 characters.');
        } elseif ($parent !== null && !q('SELECT 1 FROM posts WHERE id = ? AND parent_id IS NULL AND is_hidden = 0', [$parent])->fetch()) {
            flash('error', 'That post is no longer available.');
        } else {
            $crisis = has_crisis_language($body);
            q('INSERT INTO posts (student_id, parent_id, body, is_flagged, flag_source, review_state) VALUES (?,?,?,?,?,?)',
              [$u['id'], $parent, $body, $crisis ? 1 : 0, $crisis ? 'crisis' : null, $crisis ? 'open' : null]);
            if ($crisis) flash('support', 'crisis');
            else flash('ok', $parent ? 'Your reply is posted.' : 'Your post is in the circle.', 'Posted');
        }
    } elseif ($action === 'report') {
        $pid = (int)($_POST['post_id'] ?? 0);
        $p = q('SELECT student_id FROM posts WHERE id = ? AND is_hidden = 0', [$pid])->fetch();
        if ($p && (int)$p['student_id'] !== (int)$u['id']) {
            q('INSERT IGNORE INTO post_reports (post_id, reporter_id) VALUES (?,?)', [$pid, $u['id']]);
            q("UPDATE posts SET is_flagged = 1, flag_source = COALESCE(flag_source, 'report'),
                 review_state = IF(review_state = 'reviewed', 'reviewed', 'open') WHERE id = ?", [$pid]);
            flash('ok', 'Thank you. A counselor will look at this post.');
        } else flash('error', 'You cannot report that post.');
    }
    redirect('student/peer.php');
}

$posts = q('SELECT p.id, p.body, p.created_at, p.student_id, u.alias FROM posts p JOIN users u ON u.id = p.student_id
            WHERE p.parent_id IS NULL AND p.is_hidden = 0 ORDER BY p.created_at DESC LIMIT 30')->fetchAll();
$replies = [];
if ($posts) {
    $in = implode(',', array_fill(0, count($posts), '?'));
    $rs = q("SELECT p.parent_id, p.body, p.created_at, p.student_id, u.alias FROM posts p JOIN users u ON u.id = p.student_id
             WHERE p.parent_id IN ($in) AND p.is_hidden = 0 ORDER BY p.created_at", array_column($posts, 'id'))->fetchAll();
    foreach ($rs as $r) $replies[$r['parent_id']][] = $r;
}

render_header('Peer Circle', 'peer', $u); ?>
<div class="page-head"><h1>Peer Circle</h1>
  <p>A space for students to listen to each other. You post as <strong><?= e($u['alias']) ?></strong>. Counselors and OSDS staff can read posts to keep everyone safe, and they only see your anonymous name.</p></div>

<form method="post" class="panel">
  <input type="hidden" name="action" value="post">
  <div class="field"><label for="body">What is on your mind?</label>
    <textarea id="body" name="body" maxlength="1000" required></textarea>
    <p class="hint">Be kind. Do not share names or personal details. This is peer support, not professional care.</p></div>
  <button class="btn" type="submit">Post to the circle</button>
</form>

<section class="panel"><h2>Recent posts</h2>
<?php if (!$posts): ?><p class="empty">No posts yet. Yours could be the first.</p><?php endif; ?>
<?php foreach ($posts as $p): $mine = (int)$p['student_id'] === (int)$u['id']; ?>
  <article class="post">
    <div class="post-head"><b><?= e($p['alias']) ?><?= $mine ? ' (you)' : '' ?></b>
      <span class="muted small"><?= e(date('M j, g:i A', strtotime($p['created_at']))) ?></span></div>
    <div class="post-body"><?= e($p['body']) ?></div>
    <?php if (!empty($replies[$p['id']])): ?><div class="replies">
      <?php foreach ($replies[$p['id']] as $r): ?>
        <div class="reply"><div class="post-head"><b><?= e($r['alias']) ?><?= (int)$r['student_id'] === (int)$u['id'] ? ' (you)' : '' ?></b>
          <span class="muted small"><?= e(date('M j, g:i A', strtotime($r['created_at']))) ?></span></div>
          <div class="post-body" style="margin:0"><?= e($r['body']) ?></div></div>
      <?php endforeach; ?></div><?php endif; ?>
    <details><summary>Reply to this post</summary>
      <form method="post"><input type="hidden" name="action" value="reply"><input type="hidden" name="parent_id" value="<?= (int)$p['id'] ?>">
        <label for="r<?= (int)$p['id'] ?>" class="small">Your reply</label>
        <textarea id="r<?= (int)$p['id'] ?>" name="body" maxlength="1000" required style="min-height:80px"></textarea>
        <div class="actions"><button class="btn btn-sm" type="submit">Post my reply</button></div></form>
    </details>
    <?php if (!$mine): ?>
      <form method="post" class="actions" style="margin-top:.2rem"><input type="hidden" name="action" value="report"><input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
        <button class="btn btn-quiet btn-sm" type="submit"><?= icon('flag', 16) ?>Report this post</button></form>
    <?php endif; ?>
  </article>
<?php endforeach; ?></section>
<?php render_footer();
