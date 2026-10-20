<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('admin');
$old = ['full_name' => '', 'email' => ''];

if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['full_name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
        $old = ['full_name' => $name, 'email' => $email];
        if ($name === '' || mb_strlen($name) > 120) flash('error', 'Enter the admin\'s name (up to 120 characters).');
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) flash('error', 'Enter a valid email address.');
        elseif (strlen($pw) < 8) flash('error', 'Set a starting password with at least 8 characters.');
        elseif (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) flash('error', 'An account with that email already exists.');
        else {
            q("INSERT INTO users (role, email, password_hash, full_name, must_change_password) VALUES ('admin',?,?,?,1)", [$email, password_hash($pw, PASSWORD_DEFAULT), $name]);
            flash('ok', 'Share the email and starting password with them directly. They will choose their own at first login.', 'Admin account created');
            redirect('admin/admins.php');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['admin_id'] ?? 0);
        if ($id === (int)$u['id']) flash('error', 'You cannot delete the account you are logged in with. Ask another admin to do it.');
        else {
            $n = q("DELETE FROM users WHERE id = ? AND role = 'admin'", [$id])->rowCount();
            flash($n ? 'ok' : 'error', $n ? 'That admin account is deleted and can no longer log in.' : 'That admin account could not be found.', $n ? 'Admin deleted' : null);
        }
        redirect('admin/admins.php');
    }
}
$list = q("SELECT id, full_name, email, created_at FROM users WHERE role = 'admin' ORDER BY created_at, id")->fetchAll();

render_header('Admin Accounts', 'admins', $u); ?>
<div class="page-head"><h1>Admin accounts</h1><p>Admins can add other admins and delete them. You cannot delete your own account here.</p></div>

<form method="post" class="panel"><h2>Add an admin</h2><input type="hidden" name="action" value="create">
  <div class="grid-2" style="gap:1rem">
    <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" type="text" required value="<?= e($old['full_name']) ?>"></div>
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required value="<?= e($old['email']) ?>"></div>
  </div>
  <div class="field" style="max-width:420px"><label for="password">Starting password</label><input id="password" name="password" type="password" autocomplete="new-password" required minlength="8">
    <p class="hint">At least 8 characters. They choose their own at first login.</p></div>
  <button class="btn" type="submit"><?= icon('userplus') ?>Add this admin</button></form>

<section class="panel"><h2>Current admins</h2>
<div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Created</th><th><span class="sr-only">Action</span></th></tr></thead><tbody>
<?php foreach ($list as $a): $me = (int)$a['id'] === (int)$u['id']; ?>
  <tr><td><?= e($a['full_name']) ?><?= $me ? ' <span class="muted">(you)</span>' : '' ?></td><td><?= e($a['email']) ?></td><td><?= e(date('M j, Y', strtotime($a['created_at']))) ?></td>
    <td><?php if (!$me): ?><form method="post" onsubmit="return confirm('Delete the admin account for <?= e(addslashes($a['full_name'])) ?>? This cannot be undone.');">
      <input type="hidden" name="action" value="delete"><input type="hidden" name="admin_id" value="<?= (int)$a['id'] ?>">
      <button class="btn btn-danger btn-sm" type="submit"><?= icon('trash', 16) ?>Delete this admin</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div></section>
<?php render_footer();
