<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('student');

$next = q("SELECT a.status, a.mode, a.is_anonymous, a.ref_code, s.starts_at, s.ends_at, c.full_name AS counselor
           FROM appointments a JOIN slots s ON s.id = a.slot_id JOIN users c ON c.id = s.counselor_id
           WHERE a.student_id = ? AND a.status IN ('pending','confirmed') AND s.ends_at > ?
           ORDER BY s.starts_at LIMIT 1", [$u['id'], now()])->fetch();
$today = q('SELECT score FROM mood_entries WHERE student_id = ? AND entry_date = ?', [$u['id'], date('Y-m-d')])->fetch();
$first = explode(' ', trim($u['full_name']))[0];

render_header('Dashboard', 'dashboard', $u); ?>
<div class="page-head"><h1>Hi, <?= e($first) ?></h1>
  <p>In the Peer Circle, other students see you as <strong><?= e($u['alias']) ?></strong>.</p></div>

<div class="grid-2">
  <section class="panel">
    <h2>Your next session</h2>
    <?php if ($next): ?>
      <p><strong><?= e(fmt_slot($next['starts_at'], $next['ends_at'])) ?></strong></p>
      <p class="meta"><span><?= e(MODES[$next['mode']]) ?></span><span>With <?= e($next['counselor']) ?></span><?= status_pill($next['status']) ?></p>
      <a class="btn btn-quiet" href="<?= e(url('student/appointments.php')) ?>">See my appointments</a>
    <?php else: ?>
      <p class="empty">You have no upcoming sessions. Pick a time that works for you whenever you are ready.</p>
      <a class="btn" href="<?= e(url('student/book.php')) ?>"><?= icon('calendar') ?>Find a time to book</a>
    <?php endif; ?>
  </section>

  <section class="panel">
    <h2>How are you today?</h2>
    <?php if ($today): ?>
      <p>You checked in today. You can change your answer if your day changes.</p>
      <a class="btn btn-quiet" href="<?= e(url('student/mood.php')) ?>">See my mood chart</a>
    <?php else: ?>
      <p>A quick check-in takes about ten seconds. Only you can see your answers.</p>
      <a class="btn" href="<?= e(url('student/mood.php')) ?>"><?= icon('smile') ?>Check in now</a>
    <?php endif; ?>
  </section>
</div>


<?php render_toolkit(true); ?>
<?php render_footer();
