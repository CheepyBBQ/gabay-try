<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('admin');
// Counts and averages only. No query on this page reads an individual appointment, note, mood entry, or post.
$students = q("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$booked = q("SELECT COUNT(*) FROM appointments WHERE status NOT IN ('cancelled','declined')")->fetchColumn();
$pending = q("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();
$flagged = q("SELECT COUNT(*) FROM posts WHERE is_flagged = 1 AND review_state = 'open' AND is_hidden = 0")->fetchColumn();
$weeks = q('SELECT MIN(entry_date) - INTERVAL WEEKDAY(MIN(entry_date)) DAY AS wk, AVG(score) AS avg_score, COUNT(*) AS n
            FROM mood_entries WHERE entry_date >= CURDATE() - INTERVAL 12 WEEK
            GROUP BY YEARWEEK(entry_date, 1) HAVING COUNT(*) >= ? ORDER BY wk', [MIN_GROUP_SIZE])->fetchAll();
render_header('Dashboard', 'dashboard', $u); ?>
<div class="page-head"><h1>Campus overview</h1><p>Anonymized totals to help plan seminars and counselor hours. Individual records are not available to admins by design.</p></div>
<div class="stats">
  <div class="stat"><b><?= (int)$students ?></b><span>Students registered</span></div>
  <div class="stat"><b><?= (int)$booked ?></b><span>Sessions booked</span></div>
  <div class="stat"><b><?= (int)$pending ?></b><span>Requests still pending</span></div>
  <div class="stat"><b><?= (int)$flagged ?></b><span>Flagged posts awaiting review</span></div>
</div>
<section class="panel"><h2>Average student mood by week</h2>
<?php if ($weeks): ?>
  <?= line_chart(array_map(fn($w) => [date('M j', strtotime($w['wk'])), (float)$w['avg_score']], $weeks),
      'Weekly average mood across all students on a scale from 1 to 5, for the last 12 weeks.') ?>
  <p class="scale-key">Scale: 1 Very low to 5 Great. Each point is the week starting on that date. Dips can show stress peaks such as midterms.</p>
<?php else: ?><p class="empty">The chart appears once a week has at least <?= (int)MIN_GROUP_SIZE ?> check-ins. This protects individual students from being identified.</p><?php endif; ?></section>
<?php render_footer();
