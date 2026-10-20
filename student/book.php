<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('student');

if (is_post()) {
    $slot = (int)($_POST['slot_id'] ?? 0);
    $mode = $_POST['mode'] ?? '';
    $note = trim($_POST['note'] ?? '');
    $anon = isset($_POST['anonymous']) ? 1 : 0;
    if (!$slot || !isset(BOOKABLE_MODES[$mode]) || mb_strlen($note) > 500) {
        flash('error', 'Choose a time and a session type. Your note can be up to 500 characters.');
        redirect('student/book.php');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Atomic claim: only one request can flip is_taken from 0 to 1.
        $claim = q('UPDATE slots s JOIN users c ON c.id = s.counselor_id SET s.is_taken = 1 WHERE s.id = ? AND s.is_taken = 0 AND s.starts_at > ? AND c.is_active = 1', [$slot, now()]);
        if ($claim->rowCount() !== 1) {
            $pdo->rollBack();
            flash('error', 'Someone else just took that time, or it has passed. Please choose another one.');
            redirect('student/book.php');
        }
        q('INSERT INTO appointments (student_id, slot_id, active_slot_id, mode, note, is_anonymous, ref_code) VALUES (?,?,?,?,?,?,?)',
          [$u['id'], $slot, $slot, $mode, $note === '' ? null : $note, $anon, ref_code()]);
        $pdo->commit();
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', 'We could not book that time. Please choose another one.');
        redirect('student/book.php');
    }
    flash('ok', 'Your request is sent. The counselor will confirm it, and you will see the status on this page.');
    redirect('student/appointments.php');
}

$slots = q('SELECT s.id, s.starts_at, s.ends_at, c.full_name, c.title FROM slots s JOIN users c ON c.id = s.counselor_id
            WHERE s.is_taken = 0 AND c.is_active = 1 AND s.starts_at > ? ORDER BY s.starts_at LIMIT 80', [now()])->fetchAll();
$byDay = [];
foreach ($slots as $s) $byDay[date('l, F j', strtotime($s['starts_at']))][] = $s;

render_header('Book a session', 'appointments', $u); ?>
<div class="page-head"><h1>Book a session</h1><p>Choose an open time, then tell us how you would like to meet.</p></div>
<?php if (!$slots): ?>
  <div class="panel"><p class="empty">No open times right now. Counselors add new times regularly, so check back soon.</p>
  <a class="btn btn-quiet" href="<?= e(url('student/dashboard.php#toolkit')) ?>">Open the toolkit while you wait</a></div>
<?php else: ?>
<form method="post" class="panel">
  <fieldset>
    <legend><h2>1. Pick a time</h2></legend>
    <?php foreach ($byDay as $day => $list): ?>
      <div class="day"><h3><?= e($day) ?></h3>
      <?php foreach ($list as $s): ?>
        <label class="choice"><input type="radio" name="slot_id" value="<?= (int)$s['id'] ?>" required>
          <span><?= e(date('g:i A', strtotime($s['starts_at']))) ?> to <?= e(date('g:i A', strtotime($s['ends_at']))) ?>
          <small>With <?= e($s['full_name']) ?><?= $s['title'] ? ', ' . e($s['title']) : '' ?></small></span></label>
      <?php endforeach; ?></div>
    <?php endforeach; ?>
  </fieldset>
  <fieldset>
    <legend><h2>2. Choose how to meet</h2></legend>
    <div class="choices-inline">
      <label class="choice"><input type="radio" name="mode" value="in_person" required><span>In person</span></label>
      <label class="choice"><input type="radio" name="mode" value="video"><span>Video call</span></label>
    </div>
  </fieldset>
  <fieldset>
    <legend><h2>3. Add a note (optional)</h2></legend>
    <label for="note" class="muted" style="font-weight:400">What would you like help with? Share only what you are comfortable with.</label>
    <textarea id="note" name="note" maxlength="500"></textarea>
  </fieldset>
  <label class="choice"><input type="checkbox" name="anonymous" value="1">
    <span>Book anonymously<small>The counselor will see only a reference code, not your name or email.</small></span></label>
  <button class="btn" type="submit">Book this session</button>
</form>
<?php endif; render_footer();
