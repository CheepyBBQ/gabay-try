<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('student');
const MOOD = [1 => 'Very low', 2 => 'Low', 3 => 'Okay', 4 => 'Good', 5 => 'Great'];

if (is_post()) {
    $score = (int)($_POST['score'] ?? 0);
    if (!isset(MOOD[$score])) { flash('error', 'Choose one of the five options to save your check-in.'); redirect('student/mood.php'); }
    q('INSERT INTO mood_entries (student_id, entry_date, score) VALUES (?,?,?) ON DUPLICATE KEY UPDATE score = VALUES(score)',
      [$u['id'], date('Y-m-d'), $score]);
    $recent = array_map('intval', array_column(array_reverse(q('SELECT score FROM mood_entries WHERE student_id = ? ORDER BY entry_date DESC LIMIT 3', [$u['id']])->fetchAll()), 'score'));
    if ($score <= 2 || (count($recent) === 3 && array_sum($recent) / 3 <= 2.5))
        flash('info', 'Your check-in is saved. You do not have to wait for things to get worse to talk to someone.', 'The last few days sound heavy',
              ['Find a time to talk to a counselor', url('student/book.php')]);
    else flash('ok', 'Your check-in is saved.', 'Saved');
    redirect('student/mood.php');
}
$rows = array_reverse(q('SELECT entry_date, score FROM mood_entries WHERE student_id = ? ORDER BY entry_date DESC LIMIT 7', [$u['id']])->fetchAll());
$today = null; foreach ($rows as $r) if ($r['entry_date'] === date('Y-m-d')) $today = (int)$r['score'];

render_header('Mood Check-in', 'mood', $u); ?>
<div class="page-head"><h1>Mood check-in</h1><p>Private to you. Counselors and OSDS cannot see your entries.</p></div>

<form method="post" class="panel">
  <fieldset><legend><h2>How are you feeling today?</h2></legend>
    <div class="choices-inline">
      <?php foreach (MOOD as $n => $label): ?>
        <label class="choice"><input type="radio" name="score" value="<?= $n ?>" required<?= $today === $n ? ' checked' : '' ?>><span><?= e($label) ?></span></label>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <button class="btn" type="submit">Save my check-in</button>
</form>

<section class="panel"><h2>Your recent check-ins</h2>
  <?php if ($rows): ?>
    <?= line_chart(array_map(fn($r) => [date('M j', strtotime($r['entry_date'])), (int)$r['score']], $rows),
        'Your mood scores on a scale from 1 (very low) to 5 (great) over your most recent check-ins.') ?>
    <p class="scale-key">Scale: 1 Very low, 2 Low, 3 Okay, 4 Good, 5 Great</p>
  <?php else: ?><p class="empty">Your chart will appear after your first check-in.</p><?php endif; ?></section>
<?php render_footer();
