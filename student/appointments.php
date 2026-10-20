<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('student');

if (is_post() && ($_POST['action'] ?? '') === 'cancel') {
    $id = (int)($_POST['appointment_id'] ?? 0);
    $pdo = db(); $pdo->beginTransaction();
    $row = q("SELECT slot_id FROM appointments WHERE id = ? AND student_id = ? AND status IN ('pending','confirmed') FOR UPDATE", [$id, $u['id']])->fetch();
    if ($row) {
        q("UPDATE appointments SET status = 'cancelled', active_slot_id = NULL WHERE id = ?", [$id]);
        q('UPDATE slots SET is_taken = 0 WHERE id = ?', [$row['slot_id']]);
        $pdo->commit();
        flash('ok', 'Your booking is cancelled and the time is open for others.');
    } else {
        $pdo->rollBack();
        flash('error', 'That booking cannot be cancelled anymore.');
    }
    redirect('student/appointments.php');
}

// counselor_notes is never selected here.
$all = q('SELECT a.id, a.mode, a.status, a.is_anonymous, a.ref_code, a.note, s.starts_at, s.ends_at, c.full_name AS counselor, c.is_active AS counselor_active
          FROM appointments a JOIN slots s ON s.id = a.slot_id JOIN users c ON c.id = s.counselor_id
          WHERE a.student_id = ? ORDER BY s.starts_at DESC', [$u['id']])->fetchAll();
$active = array_filter($all, fn($a) => in_array($a['status'], ['pending', 'confirmed']));
$past = array_filter($all, fn($a) => !in_array($a['status'], ['pending', 'confirmed']));
$active = array_reverse($active);

function appt_row(array $a, bool $cancellable): void { ?>
  <li>
    <div class="row-top"><strong><?= e(fmt_slot($a['starts_at'], $a['ends_at'])) ?></strong><?= status_pill($a['status']) ?></div>
    <div class="meta"><span><?= e(MODES[$a['mode']]) ?></span><span>With <?= e($a['counselor']) ?></span>
      <span>Reference <span class="ref"><?= e($a['ref_code']) ?></span></span>
      <?php if ($a['is_anonymous']): ?><span>Booked anonymously</span><?php endif; ?></div>
    <?php if (!$a['counselor_active'] && in_array($a['status'], ['cancelled', 'declined'])): ?><p class="small" style="margin:.5rem 0 0">This counselor is no longer available. You can book another time.</p><?php endif; ?>
    <?php if ($a['note']): ?><p class="small" style="margin:.5rem 0 0">Your note: <?= e($a['note']) ?></p><?php endif; ?>
    <?php if ($cancellable): ?>
      <div class="actions"><form method="post"><input type="hidden" name="action" value="cancel">
        <input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>">
        <button class="btn btn-danger btn-sm" type="submit"><?= icon('x', 16) ?>Cancel this booking</button></form></div>
    <?php endif; ?>
  </li>
<?php }

render_header('Appointments', 'appointments', $u); ?>
<div class="page-head"><h1>Appointments</h1><p>Only you can see this page.</p></div>
<p><a class="btn" href="<?= e(url('student/book.php')) ?>"><?= icon('plus') ?>Book a session</a></p>

<section class="panel"><h2>Coming up</h2>
  <?php if ($active): ?><ul class="rows"><?php foreach ($active as $a) appt_row($a, true); ?></ul>
  <?php else: ?><p class="empty">Nothing booked right now.</p><?php endif; ?></section>

<section class="panel"><h2>History</h2>
  <?php if ($past): ?><ul class="rows"><?php foreach ($past as $a) appt_row($a, false); ?></ul>
  <?php else: ?><p class="empty">Completed, declined, and cancelled sessions will show up here.</p><?php endif; ?></section>
<?php render_footer();
