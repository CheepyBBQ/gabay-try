<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('counselor');
$pending = q("SELECT COUNT(*) FROM appointments a JOIN slots s ON s.id = a.slot_id WHERE s.counselor_id = ? AND a.status = 'pending'", [$u['id']])->fetchColumn();
$upcoming = q("SELECT COUNT(*) FROM appointments a JOIN slots s ON s.id = a.slot_id WHERE s.counselor_id = ? AND a.status = 'confirmed' AND s.ends_at > ?", [$u['id'], now()])->fetchColumn();
$open = q('SELECT COUNT(*) FROM slots WHERE counselor_id = ? AND is_taken = 0 AND starts_at > ?', [$u['id'], now()])->fetchColumn();
$flagged = q("SELECT COUNT(*) FROM posts WHERE is_flagged = 1 AND review_state = 'open' AND is_hidden = 0")->fetchColumn();
render_header('Dashboard', 'dashboard', $u); ?>
<div class="page-head"><h1>Welcome, <?= e($u['full_name']) ?></h1><p>Here is what needs your attention.</p></div>
<div class="stats">
  <div class="stat"><b><?= (int)$pending ?></b><span>Requests waiting for you</span></div>
  <div class="stat"><b><?= (int)$upcoming ?></b><span>Confirmed upcoming sessions</span></div>
  <div class="stat"><b><?= (int)$open ?></b><span>Open slots students can book</span></div>
  <div class="stat"><b><?= (int)$flagged ?></b><span>Flagged posts to review</span></div>
</div>
<div class="actions">
  <a class="btn" href="<?= e(url('counselor/requests.php')) ?>">Review requests</a>
  <a class="btn btn-quiet" href="<?= e(url('counselor/slots.php')) ?>">Add availability</a>
  <a class="btn btn-quiet" href="<?= e(url('counselor/flagged.php')) ?>">Review flagged posts</a>
</div>
<div style="height:1.5rem"></div>
<?php render_toolkit(false); ?>
<?php render_footer();
