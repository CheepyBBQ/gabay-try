<?php
// Read-only view of the Peer Circle for counselors and admins (aliases only, never real identities).
// Counselors can also hide a post or reply ($canHide). Admins can only read.
function community_page(string $role, bool $canHide): void {
    $u = require_role($role);
    $self = $role . '/community.php';

    if (is_post() && $canHide && ($_POST['action'] ?? '') === 'hide') {
        $n = q("UPDATE posts SET is_hidden = 1, review_state = IF(is_flagged = 1, 'reviewed', review_state) WHERE id = ?", [(int)($_POST['post_id'] ?? 0)])->rowCount();
        flash($n ? 'ok' : 'error', $n ? 'That item is hidden from students.' : 'That item could not be hidden.', $n ? 'Hidden' : null);
        redirect($self);
    }

    $posts = q("SELECT p.id, p.body, p.created_at, p.is_flagged, u.alias FROM posts p JOIN users u ON u.id = p.student_id
                WHERE p.parent_id IS NULL AND p.is_hidden = 0 ORDER BY p.created_at DESC LIMIT 50")->fetchAll();
    $replies = [];
    if ($posts) {
        $in = implode(',', array_fill(0, count($posts), '?'));
        foreach (q("SELECT p.id, p.parent_id, p.body, p.created_at, p.is_flagged, u.alias FROM posts p JOIN users u ON u.id = p.student_id
                    WHERE p.parent_id IN ($in) AND p.is_hidden = 0 ORDER BY p.created_at", array_column($posts, 'id'))->fetchAll() as $r) $replies[$r['parent_id']][] = $r;
    }
    $hideForm = function (int $id, string $label) use ($canHide) {
        if (!$canHide) return '';
        return '<form method="post" class="actions"><input type="hidden" name="action" value="hide"><input type="hidden" name="post_id" value="' . $id
             . '"><button class="btn btn-danger btn-sm" type="submit">' . icon('eyeoff', 16) . e($label) . '</button></form>';
    };

    render_header('Peer Circle', 'community', $u); ?>
<div class="page-head"><h1>Peer Circle</h1>
  <p>Posts and replies from students, shown by anonymous name only. Students are told that staff can read this space to keep everyone safe.<?= $canHide ? '' : ' You can read here, and counselors handle moderation.' ?></p></div>

<section class="panel">
  <h2>Recent posts</h2>
  <?php if (!$posts): ?><p class="empty">No posts yet.</p><?php endif; ?>
  <?php foreach ($posts as $p): ?>
    <article class="post">
      <div class="post-head"><b><?= e($p['alias']) ?></b><span class="muted small"><?= e(date('M j, g:i A', strtotime($p['created_at']))) ?></span>
        <?php if ($p['is_flagged']): ?><span class="pill pill-pending">Flagged</span><?php endif; ?></div>
      <div class="post-body"><?= e($p['body']) ?></div>
      <?php if (!empty($replies[$p['id']])): ?><div class="replies">
        <?php foreach ($replies[$p['id']] as $r): ?>
          <div class="reply"><div class="post-head"><b><?= e($r['alias']) ?></b><span class="muted small"><?= e(date('M j, g:i A', strtotime($r['created_at']))) ?></span>
            <?php if ($r['is_flagged']): ?><span class="pill pill-pending">Flagged</span><?php endif; ?></div>
            <div class="post-body" style="margin:0"><?= e($r['body']) ?></div><?= $hideForm((int)$r['id'], 'Hide this reply') ?></div>
        <?php endforeach; ?></div><?php endif; ?>
      <?= $hideForm((int)$p['id'], 'Hide this post') ?>
    </article>
  <?php endforeach; ?>
</section>
<?php render_footer();
}
