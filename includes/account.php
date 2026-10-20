<?php
// "My account" page shared by all three roles. Each role has a one-line account.php that calls this.
function account_page(string $role): void {
    $u = require_role($role);
    $self = $role . '/account.php';

    if (is_post()) {
        $action = $_POST['action'] ?? '';
        $hash = q('SELECT password_hash FROM users WHERE id = ?', [$u['id']])->fetchColumn();
        $passwordOk = password_verify($_POST['current_password'] ?? '', $hash);

        if ($action === 'profile') {
            $name = trim($_POST['full_name'] ?? ''); $title = trim($_POST['title'] ?? '');
            if ($name === '' || mb_strlen($name) > 120) flash('error', 'Enter your name (up to 120 characters).');
            elseif ($role === 'counselor' && mb_strlen($title) > 80) flash('error', 'Your title can be up to 80 characters.');
            else {
                if ($role === 'counselor') q('UPDATE users SET full_name = ?, title = ? WHERE id = ?', [$name, $title === '' ? null : $title, $u['id']]);
                else q('UPDATE users SET full_name = ? WHERE id = ?', [$name, $u['id']]);
                flash('ok', 'Your profile is updated.', 'Saved');
            }
        } elseif ($action === 'email') {
            $email = strtolower(trim($_POST['new_email'] ?? ''));
            if (!$passwordOk) flash('error', 'Your current password is not right, so your email was not changed.');
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) flash('error', 'Enter a valid email address.');
            elseif ($role === 'student' && SCHOOL_EMAIL_DOMAIN !== '' && !str_ends_with($email, '@' . SCHOOL_EMAIL_DOMAIN))
                flash('error', 'Use your school email, which ends in @' . SCHOOL_EMAIL_DOMAIN . '.');
            elseif (q('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$email, $u['id']])->fetch()) flash('error', 'Another account already uses that email.');
            else { q('UPDATE users SET email = ? WHERE id = ?', [$email, $u['id']]); flash('ok', 'Your email is updated. Use it the next time you log in.', 'Saved'); }
        } elseif ($action === 'password') {
            $new = $_POST['new_password'] ?? '';
            if (!$passwordOk) flash('error', 'Your current password is not right, so your password was not changed.');
            elseif (strlen($new) < 8) flash('error', 'Choose a new password with at least 8 characters.');
            elseif ($new !== ($_POST['new_password2'] ?? '')) flash('error', 'The two new passwords are different. Type the same one in both boxes.');
            elseif ($new === ($_POST['current_password'] ?? '')) flash('error', 'Choose a password that is different from your current one.');
            else {
                q('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
                flash('ok', 'Your password is changed.', 'Saved');
                if ($u['must_change_password']) redirect(home_for($role));
            }
        } elseif ($action === 'alias' && $role === 'student') {
            $alias = random_alias();
            q('UPDATE users SET alias = ? WHERE id = ?', [$alias, $u['id']]);
            flash('ok', 'From now on, other students see you as ' . $alias . '. Your older posts keep showing your new name too.', 'New anonymous name');
        }
        redirect($self);
    }

    if ($u['must_change_password'])
        notify('info', 'An administrator set your password. Please choose your own below before you continue.', 'Choose your own password');

    render_header('My account', 'account', $u); ?>
<div class="page-head"><h1>My account</h1><p>Update your details and keep your account secure.</p></div>

<div class="grid-2">
  <form method="post" class="panel">
    <h2>Profile</h2><input type="hidden" name="action" value="profile">
    <div class="field"><label for="full_name">Name</label>
      <input id="full_name" name="full_name" type="text" required maxlength="120" value="<?= e($u['full_name']) ?>"></div>
    <?php if ($role === 'counselor'): ?>
    <div class="field"><label for="title">Title or specialty (optional)</label>
      <input id="title" name="title" type="text" maxlength="80" placeholder="Guidance Counselor" value="<?= e($u['title']) ?>">
      <p class="hint">Students see this when they choose a time to book.</p></div>
    <?php endif; ?>
    <button class="btn" type="submit">Save profile</button>
  </form>

  <form method="post" class="panel">
    <h2>Email</h2><input type="hidden" name="action" value="email">
    <p class="muted small">Current email: <?= e($u['email']) ?></p>
    <div class="field"><label for="new_email">New email</label>
      <input id="new_email" name="new_email" type="email" required autocomplete="email"></div>
    <div class="field"><label for="pw_email">Current password</label>
      <input id="pw_email" name="current_password" type="password" required autocomplete="current-password"></div>
    <button class="btn" type="submit">Change my email</button>
  </form>
</div>

<form method="post" class="panel">
  <h2>Password</h2><input type="hidden" name="action" value="password">
  <div class="field"><label for="pw_cur">Current password</label>
    <input id="pw_cur" name="current_password" type="password" required autocomplete="current-password"></div>
  <div class="grid-2" style="gap:1rem">
    <div class="field"><label for="pw_new">New password</label>
      <input id="pw_new" name="new_password" type="password" required minlength="8" autocomplete="new-password">
      <p class="hint">At least 8 characters.</p></div>
    <div class="field"><label for="pw_new2">Type the new password again</label>
      <input id="pw_new2" name="new_password2" type="password" required autocomplete="new-password"></div>
  </div>
  <button class="btn" type="submit">Change my password</button>
</form>

<?php if ($role === 'student'): ?>
<form method="post" class="panel">
  <h2>Your anonymous name</h2><input type="hidden" name="action" value="alias">
  <p>In the Peer Circle, you appear as <strong><?= e($u['alias']) ?></strong>. Counselors and OSDS staff can read posts to keep everyone safe, but they only see this name.</p>
  <button class="btn btn-quiet" type="submit">Get a new anonymous name</button>
</form>
<?php endif;
    render_footer();
}
