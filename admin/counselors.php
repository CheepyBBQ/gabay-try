<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('admin');
$old = ['full_name' => '', 'email' => '', 'title' => ''];

if (is_post()) {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['full_name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $title = trim($_POST['title'] ?? '');
    $id = (int)($_POST['counselor_id'] ?? 0);
    $isCounselor = fn(int $id) => (bool)q("SELECT 1 FROM users WHERE id = ? AND role = 'counselor'", [$id])->fetch();

    if ($action === 'create') {
        $pw = $_POST['password'] ?? ''; $old = ['full_name' => $name, 'email' => $email, 'title' => $title];
        if ($name === '' || mb_strlen($name) > 120) flash('error', 'Enter the counselor\'s name (up to 120 characters).');
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) flash('error', 'Enter a valid email address.');
        elseif (mb_strlen($title) > 80) flash('error', 'The title can be up to 80 characters.');
        elseif (strlen($pw) < 8) flash('error', 'Set a starting password with at least 8 characters.');
        elseif (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) flash('error', 'An account with that email already exists.');
        else {
            q("INSERT INTO users (role, email, password_hash, full_name, title, must_change_password) VALUES ('counselor',?,?,?,?,1)",
              [$email, password_hash($pw, PASSWORD_DEFAULT), $name, $title === '' ? null : $title]);
            flash('ok', 'Share the email and starting password with them directly. They will be asked to choose their own at first login.', 'Counselor account created');
            redirect('admin/counselors.php');
        }
    } elseif ($action === 'update' && $isCounselor($id)) {
        if ($name === '' || mb_strlen($name) > 120) flash('error', 'Enter a name (up to 120 characters).');
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) flash('error', 'Enter a valid email address.');
        elseif (mb_strlen($title) > 80) flash('error', 'The title can be up to 80 characters.');
        elseif (q('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$email, $id])->fetch()) flash('error', 'Another account already uses that email.');
        else {
            q('UPDATE users SET full_name = ?, email = ?, title = ? WHERE id = ?', [$name, $email, $title === '' ? null : $title, $id]);
            flash('ok', 'The counselor\'s details are updated.', 'Saved');
        }
        redirect('admin/counselors.php');
    } elseif ($action === 'deactivate' && $isCounselor($id)) {
        $pdo = db(); $pdo->beginTransaction();
        q('UPDATE users SET is_active = 0 WHERE id = ?', [$id]);
        $upcoming = q("SELECT a.id, a.slot_id FROM appointments a JOIN slots s ON s.id = a.slot_id
                       WHERE s.counselor_id = ? AND a.status IN ('pending','confirmed') AND s.ends_at > ? FOR UPDATE", [$id, now()])->fetchAll();
        foreach ($upcoming as $a) {   // free the slot and cancel, so students are not left waiting for someone who is gone
            q("UPDATE appointments SET status = 'cancelled', active_slot_id = NULL WHERE id = ?", [$a['id']]);
            q('UPDATE slots SET is_taken = 0 WHERE id = ?', [$a['slot_id']]);
        }
        $pdo->commit();
        $name = q('SELECT full_name FROM users WHERE id = ?', [$id])->fetchColumn();
        flash('ok', $name . ' can no longer log in, and students no longer see their open times. ' . count($upcoming) . ' upcoming session(s) were cancelled. Past sessions and private notes are kept.', 'Account deactivated');
        redirect('admin/counselors.php');
    } elseif ($action === 'reactivate' && $isCounselor($id)) {
        q('UPDATE users SET is_active = 1 WHERE id = ?', [$id]);
        $name = q('SELECT full_name FROM users WHERE id = ?', [$id])->fetchColumn();
        flash('ok', $name . ' can log in again, and their open times show to students again.', 'Account reactivated');
        redirect('admin/counselors.php');
    } elseif ($action === 'reset' && $isCounselor($id)) {
        $pw = temp_password();
        q('UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $id]);
        $_SESSION['temp_pw'] = ['name' => q('SELECT full_name FROM users WHERE id = ?', [$id])->fetchColumn(), 'pw' => $pw];
        flash('ok', 'A temporary password is shown at the top of this page, just once.', 'Password reset');
        redirect('admin/counselors.php');
    }
}
$temp = $_SESSION['temp_pw'] ?? null; unset($_SESSION['temp_pw']);
$list = q("SELECT u.id, u.full_name, u.email, u.title, u.created_at, u.is_active,
             (SELECT COUNT(*) FROM appointments a JOIN slots s ON s.id = a.slot_id
               WHERE s.counselor_id = u.id AND a.status IN ('pending','confirmed') AND s.ends_at > ?) AS upcoming
           FROM users u WHERE u.role = 'counselor' ORDER BY u.is_active DESC, u.full_name", [now()])->fetchAll();

render_header('Counselor Accounts', 'counselors', $u); ?>
<div class="page-head"><h1>Counselor accounts</h1><p>Only admins can create counselor accounts and manage their details.</p></div>

<?php if ($temp): ?>
<section class="panel" aria-labelledby="tp-h">
  <h2 id="tp-h">Temporary password for <?= e($temp['name']) ?></h2>
  <p>Share it with them directly. This is the only time it is shown, and they must choose a new password the first time they log in.</p>
  <div class="reveal-row">
    <input id="tp" type="password" readonly value="<?= e($temp['pw']) ?>" aria-label="Temporary password">
    <button type="button" class="btn btn-quiet" data-reveal="tp" aria-pressed="false">Show</button>
  </div>
</section>
<?php endif; ?>

<form method="post" class="panel"><h2>Create a counselor account</h2><input type="hidden" name="action" value="create">
  <div class="grid-2" style="gap:1rem">
    <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" type="text" required value="<?= e($old['full_name']) ?>"></div>
    <div class="field"><label for="title">Title or specialty (optional)</label><input id="title" name="title" type="text" maxlength="80" placeholder="Guidance Counselor" value="<?= e($old['title']) ?>"></div>
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required value="<?= e($old['email']) ?>"></div>
    <div class="field"><label for="password">Starting password</label><input id="password" name="password" type="password" autocomplete="new-password" required minlength="8">
      <p class="hint">At least 8 characters.</p></div>
  </div>
  <button class="btn" type="submit"><?= icon('userplus') ?>Create counselor account</button></form>

<section class="panel"><h2>Current counselors</h2>
<?php if (!$list): ?><p class="empty">No counselor accounts yet. Create the first one above.</p><?php else: ?>
<ul class="rows">
<?php foreach ($list as $c): $k = (int)$c['id']; ?>
  <li>
    <details>
      <summary><?= e($c['full_name']) ?><?= $c['title'] ? ' <span class="muted">(' . e($c['title']) . ')</span>' : '' ?><?= $c['is_active'] ? '' : ' <span class="pill pill-cancelled">Deactivated</span>' ?></summary>
      <dl class="info">
        <div><dt>Email</dt>
          <dd><span id="em<?= $k ?>"><span class="masked"><?= e(mask_email($c['email'])) ?></span><span class="full" hidden><?= e($c['email']) ?></span></span>
            <button type="button" class="link-btn" data-reveal="em<?= $k ?>" aria-pressed="false">Show</button></dd></div>
        <div><dt>Password</dt>
          <dd><span aria-label="Hidden">********</span> <span class="muted small">Passwords are stored in a form nobody can read. Reset it to give them a new one.</span></dd></div>
        <div><dt>Created</dt><dd><?= e(date('M j, Y', strtotime($c['created_at']))) ?></dd></div>
      </dl>
      <form method="post" class="edit-grid"><input type="hidden" name="action" value="update"><input type="hidden" name="counselor_id" value="<?= $k ?>">
        <div class="field"><label for="n<?= $k ?>">Name</label><input id="n<?= $k ?>" name="full_name" type="text" required value="<?= e($c['full_name']) ?>"></div>
        <div class="field"><label for="t<?= $k ?>">Title or specialty</label><input id="t<?= $k ?>" name="title" type="text" maxlength="80" value="<?= e($c['title']) ?>"></div>
        <div class="field"><label for="e<?= $k ?>">Email</label><input id="e<?= $k ?>" name="email" type="email" required value="<?= e($c['email']) ?>"></div>
        <div class="actions" style="align-self:end"><button class="btn btn-sm" type="submit">Save changes</button></div>
      </form>
      <form method="post" class="actions" onsubmit="return confirm('Reset the password for <?= e(addslashes($c['full_name'])) ?>? Their current password will stop working.');">
        <input type="hidden" name="action" value="reset"><input type="hidden" name="counselor_id" value="<?= $k ?>">
        <button class="btn btn-danger btn-sm" type="submit">Reset password</button></form>
      <?php if ($c['is_active']): ?>
      <form method="post" class="actions" onsubmit="return confirm('Deactivate <?= e(addslashes($c['full_name'])) ?>? They will not be able to log in, students will no longer see their open times, and <?= (int)$c['upcoming'] ?> upcoming session(s) will be cancelled. Past sessions and private notes are kept.');">
        <input type="hidden" name="action" value="deactivate"><input type="hidden" name="counselor_id" value="<?= $k ?>">
        <button class="btn btn-danger btn-sm" type="submit">Deactivate account</button>
        <span class="muted small">Use this when a counselor leaves. Nothing is deleted, so their history stays.</span></form>
      <?php else: ?>
      <form method="post" class="actions"><input type="hidden" name="action" value="reactivate"><input type="hidden" name="counselor_id" value="<?= $k ?>">
        <button class="btn btn-sm" type="submit">Reactivate account</button></form>
      <?php endif; ?>
    </details>
  </li>
<?php endforeach; ?></ul><?php endif; ?></section>
<?php render_footer();
