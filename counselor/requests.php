<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('counselor');

if (is_post()) {
    $id = (int)($_POST['appointment_id'] ?? 0); $action = $_POST['action'] ?? '';
    // Every update joins slots and checks counselor_id, so a counselor can only touch their own requests.
    if ($action === 'confirm') {
        $n = q("UPDATE appointments a JOIN slots s ON s.id = a.slot_id SET a.status = 'confirmed' WHERE a.id = ? AND s.counselor_id = ? AND a.status = 'pending'", [$id, $u['id']])->rowCount();
        flash($n ? 'ok' : 'error', $n ? 'Request confirmed.' : 'That request cannot be confirmed.');
    } elseif ($action === 'decline') {
        $pdo = db(); $pdo->beginTransaction();
        $row = q("SELECT a.slot_id FROM appointments a JOIN slots s ON s.id = a.slot_id WHERE a.id = ? AND s.counselor_id = ? AND a.status = 'pending' FOR UPDATE", [$id, $u['id']])->fetch();
        if ($row) {
            q("UPDATE appointments SET status = 'declined', active_slot_id = NULL WHERE id = ?", [$id]);
            q('UPDATE slots SET is_taken = 0 WHERE id = ?', [$row['slot_id']]);
            $pdo->commit(); flash('ok', 'Request declined. The slot is open again.');
        } else { $pdo->rollBack(); flash('error', 'That request cannot be declined.'); }
    } elseif ($action === 'done') {
        $n = q("UPDATE appointments a JOIN slots s ON s.id = a.slot_id SET a.status = 'done' WHERE a.id = ? AND s.counselor_id = ? AND a.status = 'confirmed'", [$id, $u['id']])->rowCount();
        flash($n ? 'ok' : 'error', $n ? 'Session marked as done.' : 'Only confirmed sessions can be marked as done.');
    } elseif ($action === 'notes') {
        $notes = mb_substr(trim($_POST['notes'] ?? ''), 0, 5000);
        q("UPDATE appointments a JOIN slots s ON s.id = a.slot_id SET a.counselor_notes = ? WHERE a.id = ? AND s.counselor_id = ? AND a.status IN ('confirmed','done')", [$notes === '' ? null : $notes, $id, $u['id']]);
        flash('ok', 'Notes saved.');
    }
    redirect('counselor/requests.php');
}

// For anonymous bookings the name and email are never selected.
$rows = q("SELECT a.id, a.mode, a.status, a.is_anonymous, a.ref_code, a.note, a.counselor_notes, s.starts_at, s.ends_at,
             IF(a.is_anonymous = 1, NULL, st.full_name) AS student_name, IF(a.is_anonymous = 1, NULL, st.email) AS student_email
           FROM appointments a JOIN slots s ON s.id = a.slot_id JOIN users st ON st.id = a.student_id
           WHERE s.counselor_id = ? ORDER BY FIELD(a.status,'pending','confirmed','done','declined','cancelled'), s.starts_at", [$u['id']])->fetchAll();

render_header('Requests', 'requests', $u); ?>
<div class="page-head"><h1>Requests</h1><p>Sessions booked with you. Your private notes are visible only to you.</p></div>
<section class="panel">
<?php if (!$rows): ?><p class="empty">No one has booked with you yet. Add slots so students can find you.</p><?php else: ?><ul class="rows">
<?php foreach ($rows as $r): ?><li>
  <div class="row-top"><strong><?= e(fmt_slot($r['starts_at'], $r['ends_at'])) ?></strong><?= status_pill($r['status']) ?></div>
  <div class="meta"><span><?= e(MODES[$r['mode']]) ?></span>
    <?php if ($r['is_anonymous']): ?><span>Anonymous student, reference <span class="ref"><?= e($r['ref_code']) ?></span></span>
    <?php else: ?><span><?= e($r['student_name']) ?> (<?= e($r['student_email']) ?>)</span><span>Reference <span class="ref"><?= e($r['ref_code']) ?></span></span><?php endif; ?></div>
  <?php if ($r['note']): ?><p style="margin:.5rem 0 0">Student's note: <?= e($r['note']) ?></p><?php endif; ?>
  <div class="actions">
    <?php if ($r['status'] === 'pending'): ?>
      <form method="post"><input type="hidden" name="action" value="confirm"><input type="hidden" name="appointment_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm" type="submit"><?= icon('check', 16) ?>Confirm request</button></form>
      <form method="post"><input type="hidden" name="action" value="decline"><input type="hidden" name="appointment_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger btn-sm" type="submit"><?= icon('x', 16) ?>Decline request</button></form>
    <?php elseif ($r['status'] === 'confirmed'): ?>
      <form method="post"><input type="hidden" name="action" value="done"><input type="hidden" name="appointment_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm" type="submit"><?= icon('check', 16) ?>Mark as done</button></form>
    <?php endif; ?>
  </div>
  <?php if (in_array($r['status'], ['confirmed', 'done'])): ?>
  <details><summary>Private session notes</summary>
    <form method="post"><input type="hidden" name="action" value="notes"><input type="hidden" name="appointment_id" value="<?= (int)$r['id'] ?>">
      <label for="n<?= (int)$r['id'] ?>" class="small">Only you can read these notes.</label>
      <textarea id="n<?= (int)$r['id'] ?>" name="notes" maxlength="5000"><?= e($r['counselor_notes']) ?></textarea>
      <div class="actions"><button class="btn btn-quiet btn-sm" type="submit">Save notes</button></div></form></details>
  <?php endif; ?>
</li><?php endforeach; ?></ul><?php endif; ?></section>
<?php render_footer();
