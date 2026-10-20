<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('counselor');

if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $date = $_POST['date'] ?? ''; $from = $_POST['start'] ?? ''; $to = $_POST['end'] ?? '';
        $s = strtotime("$date $from"); $en = strtotime("$date $to");
        if (!$s || !$en || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) flash('error', 'Enter a date, a start time, and an end time.');
        elseif ($s <= time()) flash('error', 'Choose a start time in the future.');
        elseif ($en <= $s) flash('error', 'The end time must be after the start time.');
        elseif ($en - $s > 3 * 3600) flash('error', 'A slot can be at most 3 hours long.');
        else {
            $a = date('Y-m-d H:i:s', $s); $b = date('Y-m-d H:i:s', $en);
            if (q('SELECT 1 FROM slots WHERE counselor_id = ? AND starts_at < ? AND ends_at > ?', [$u['id'], $b, $a])->fetch())
                flash('error', 'That time overlaps a slot you already added.');
            else { q('INSERT INTO slots (counselor_id, starts_at, ends_at) VALUES (?,?,?)', [$u['id'], $a, $b]); flash('ok', 'Slot added. Students can book it now.'); }
        }
    } elseif ($action === 'delete') {
        $n = q('DELETE FROM slots WHERE id = ? AND counselor_id = ? AND is_taken = 0', [(int)($_POST['slot_id'] ?? 0), $u['id']])->rowCount();
        flash($n ? 'ok' : 'error', $n ? 'Slot removed.' : 'That slot is already booked, so it cannot be removed. Decline or complete the request instead.');
    }
    redirect('counselor/slots.php');
}
$slots = q('SELECT id, starts_at, ends_at, is_taken FROM slots WHERE counselor_id = ? AND ends_at > ? ORDER BY starts_at', [$u['id'], now()])->fetchAll();
render_header('My Slots', 'slots', $u); ?>
<div class="page-head"><h1>My slots</h1><p>Add the times you are available. Students see open slots and can book them.</p></div>
<form method="post" class="panel">
  <h2>Add a slot</h2><input type="hidden" name="action" value="add">
  <div class="grid-2" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
    <div class="field"><label for="date">Date</label><input id="date" name="date" type="date" required min="<?= date('Y-m-d') ?>"></div>
    <div class="field"><label for="start">Starts at</label><input id="start" name="start" type="time" required></div>
    <div class="field"><label for="end">Ends at</label><input id="end" name="end" type="time" required></div>
  </div>
  <button class="btn" type="submit"><?= icon('plus') ?>Add this slot</button>
</form>
<section class="panel"><h2>Upcoming slots</h2>
<?php if (!$slots): ?><p class="empty">You have no upcoming slots. Add one above.</p><?php else: ?><ul class="rows">
<?php foreach ($slots as $s): ?><li><div class="row-top"><strong><?= e(fmt_slot($s['starts_at'], $s['ends_at'])) ?></strong>
  <?php if ($s['is_taken']): ?><span class="pill pill-confirmed">Booked</span><?php else: ?><span class="pill">Open</span><?php endif; ?></div>
  <?php if (!$s['is_taken']): ?><form method="post" class="actions"><input type="hidden" name="action" value="delete"><input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
    <button class="btn btn-danger btn-sm" type="submit"><?= icon('trash', 16) ?>Remove this slot</button></form><?php endif; ?></li>
<?php endforeach; ?></ul><?php endif; ?></section>
<?php render_footer();
