<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('counselor');

if (is_post()) {
    $id = (int)($_POST['post_id'] ?? 0); $action = $_POST['action'] ?? '';
    if ($action === 'review') {
        q("UPDATE posts SET review_state = 'reviewed' WHERE id = ? AND is_flagged = 1", [$id]); flash('ok', 'Marked as reviewed. The post stays visible.');
    } elseif ($action === 'hide') {
        q("UPDATE posts SET is_hidden = 1, review_state = 'reviewed' WHERE id = ? AND is_flagged = 1", [$id]); flash('ok', 'Post hidden from the Peer Circle.');
    }
    redirect('counselor/flagged.php');
}
// Only the alias is selected, never the student's id or identity.
$posts = q("SELECT p.id, p.body, p.flag_source, p.created_at, p.parent_id, u.alias FROM posts p JOIN users u ON u.id = p.student_id
            WHERE p.is_flagged = 1 AND p.review_state = 'open' AND p.is_hidden = 0 ORDER BY p.flag_source = 'crisis' DESC, p.created_at")->fetchAll();
render_header('Flagged Posts', 'flagged', $u); ?>
<div class="page-head"><h1>Flagged posts</h1><p>Posts the system flagged for crisis language, or that students reported. You see only the poster's alias.</p></div>
<section class="panel">
<?php if (!$posts): ?><p class="empty">Nothing to review right now.</p><?php else: foreach ($posts as $p): ?>
  <article class="post">
    <div class="post-head"><b><?= e($p['alias']) ?></b><span class="muted small"><?= e(date('M j, g:i A', strtotime($p['created_at']))) ?></span>
      <span class="pill <?= $p['flag_source'] === 'crisis' ? 'pill-pending' : '' ?>"><?= $p['flag_source'] === 'crisis' ? 'Possible crisis language' : 'Reported by a student' ?></span>
      <?php if ($p['parent_id']): ?><span class="muted small">Reply</span><?php endif; ?></div>
    <div class="post-body"><?= e($p['body']) ?></div>
    <div class="actions">
      <form method="post"><input type="hidden" name="action" value="review"><input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>"><button class="btn btn-sm" type="submit"><?= icon('check', 16) ?>Mark as reviewed</button></form>
      <form method="post"><input type="hidden" name="action" value="hide"><input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>"><button class="btn btn-danger btn-sm" type="submit"><?= icon('eyeoff', 16) ?>Hide this post</button></form>
    </div>
  </article>
<?php endforeach; endif; ?></section>
<?php render_footer();
