<?php
require __DIR__ . '/includes/bootstrap.php';
if ($u = current_user()) redirect(home_for($u['role']));

$errors = []; $old = ['email' => '', 'full_name' => '', 'reg_email' => ''];
if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? ''); $old['email'] = $email;
        $row = q('SELECT id, role, password_hash, is_active FROM users WHERE email = ?', [$email])->fetch();
        if ($row && password_verify($_POST['password'] ?? '', $row['password_hash'])) {
            if (!$row['is_active']) {   // only said after the right password, so strangers cannot probe which emails exist
                $errors['login'] = 'This account is deactivated. Please contact the OSDS office if you think this is a mistake.';
            } else {
                log_in((int)$row['id']);
                redirect(home_for($row['role']));
            }
        } else {
            $errors['login'] = 'That email and password do not match. Check both and try again.';
        }
    } elseif ($action === 'register') {
        $name = trim($_POST['full_name'] ?? ''); $email = strtolower(trim($_POST['reg_email'] ?? ''));
        $pw = $_POST['reg_password'] ?? ''; $pw2 = $_POST['reg_password2'] ?? '';
        $old['full_name'] = $name; $old['reg_email'] = $email;
        if ($name === '' || mb_strlen($name) > 120) $errors['register'][] = 'Enter your name (up to 120 characters).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['register'][] = 'Enter a valid school email address.';
        elseif (SCHOOL_EMAIL_DOMAIN !== '' && !str_ends_with($email, '@' . SCHOOL_EMAIL_DOMAIN))
            $errors['register'][] = 'Use your school email, which ends in @' . SCHOOL_EMAIL_DOMAIN . '.';
        if (strlen($pw) < 8) $errors['register'][] = 'Choose a password with at least 8 characters.';
        elseif ($pw !== $pw2) $errors['register'][] = 'The two passwords are different. Type the same password in both boxes.';
        if (empty($_POST['consent'])) $errors['register'][] = 'Tick the box to confirm you understand that staff can read the Peer Circle.';
        if (empty($errors['register'])) {
            if (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
                $errors['register'][] = 'An account with that email already exists. Try logging in instead.';
            } else {
                $alias = random_alias();
                q('INSERT INTO users (role, email, password_hash, full_name, alias, consented_at) VALUES (?,?,?,?,?,?)',
                  ['student', $email, password_hash($pw, PASSWORD_DEFAULT), $name, $alias, now()]);
                log_in((int)db()->lastInsertId());
                flash('ok', 'Your account is ready. In the Peer Circle, other students will only see your anonymous name: ' . $alias . '.', 'Welcome to GABAY');
                redirect('student/dashboard.php');
            }
        }
    }
}
// Which tab opens: the one that was just submitted, or ?tab=register, otherwise log in.
$tab = (($_POST['action'] ?? '') === 'register' || ($_GET['tab'] ?? '') === 'register') ? 'register' : 'login';

if (!empty($errors['login'])) notify('error', $errors['login'], 'We could not log you in');
foreach ($errors['register'] ?? [] as $m) notify('error', $m, 'Please fix this to create your account');

render_header('Log in', '', null, true);
?>
<div class="auth-grid">
  <aside class="brand-panel">
    <div class="brand-row"><?= icon('shield', 28) ?><span>GABAY</span></div>
    <div class="brand-copy">
      <h1>Streamlined counseling, zero friction.</h1>
      <p>The guidance appointment platform for URS Giants. Quick scheduling and direct access to your campus OSDS counselors.</p>
      <ul class="brand-points">
        <li><?= icon('check', 20) ?><span><strong>Private by design.</strong> Book anonymously with just a reference code.</span></li>
        <li><?= icon('check', 20) ?><span><strong>Every counselor, one place.</strong> See open times and book in a minute.</span></li>
      </ul>
    </div>
  </aside>

  <section class="form-panel">
    <div class="auth-card">
      <div class="tabs" role="tablist" aria-label="Log in or create an account">
        <button type="button" role="tab" id="tab-login" aria-controls="panel-login" aria-selected="<?= $tab === 'login' ? 'true' : 'false' ?>" data-tab="login">Log in</button>
        <button type="button" role="tab" id="tab-register" aria-controls="panel-register" aria-selected="<?= $tab === 'register' ? 'true' : 'false' ?>" data-tab="register">Create account</button>
      </div>

      <div class="tab-panel" id="panel-login" role="tabpanel" aria-labelledby="tab-login"<?= $tab === 'login' ? '' : ' hidden' ?>>
        <h2>Welcome back</h2>
        <p class="sub">Log in to book a session or check in.</p>
        <form method="post" novalidate>
          <input type="hidden" name="action" value="login">
          <div class="field"><label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="username" placeholder="name@urs.edu.ph" required value="<?= e($old['email']) ?>"></div>
          <div class="field"><label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Your password" required></div>
          <button class="btn btn-block" type="submit">Log in</button>
        </form>
      </div>

      <div class="tab-panel" id="panel-register" role="tabpanel" aria-labelledby="tab-register"<?= $tab === 'register' ? '' : ' hidden' ?>>
        <h2>Create your student account</h2>
        <p class="sub">Takes about a minute. You get an anonymous name for the Peer Circle.</p>
        <form method="post" novalidate id="register-form">
          <input type="hidden" name="action" value="register">
          <div class="field"><label for="full_name">Your name</label>
            <input id="full_name" name="full_name" type="text" autocomplete="name" placeholder="Juan Dela Cruz" required value="<?= e($old['full_name']) ?>">
            <p class="hint">Only you can see this. You can still book anonymously.</p></div>
          <div class="field"><label for="reg_email">School email</label>
            <input id="reg_email" name="reg_email" type="email" autocomplete="email" placeholder="name@urs.edu.ph" required value="<?= e($old['reg_email']) ?>"></div>
          <div class="field two">
            <div><label for="reg_password">Password</label>
              <input id="reg_password" name="reg_password" type="password" autocomplete="new-password" placeholder="8+ characters" required minlength="8"></div>
            <div><label for="reg_password2">Confirm password</label>
              <input id="reg_password2" name="reg_password2" type="password" autocomplete="new-password" placeholder="Type it again" required></div>
          </div>
          <input type="hidden" name="consent" id="consent-field" value="">
          <noscript><label class="consent"><input type="checkbox" name="consent" value="1" required>
            <span>I have read the privacy notice and I agree. Counselors and OSDS staff can read the Peer Circle, where they only see my anonymous name.</span></label></noscript>
          <button class="btn btn-block" type="submit">Create my account</button>
        </form>
      </div>
    </div>

    <p class="crisis-link">Need immediate support? <button type="button" id="crisis-open">View crisis hotlines</button></p>
  </section>
</div>

<?= crisis_dialog_html() ?>

<dialog id="consent-dialog" class="consent-dialog" aria-labelledby="consent-h">
  <h2 id="consent-h">Before you create your account</h2>
  <p>Please read how GABAY uses your information.</p>
  <ul class="consent-points">
    <li><strong>What we collect:</strong> your name, school email, mood check-ins, appointment requests, what you post in the Peer Circle, and notes your counselor writes after sessions.</li>
    <li><strong>Why:</strong> only to run counseling appointments and keep students safe.</li>
    <li><strong>Who can see it:</strong> your counselor sees your name and email only if you do not book anonymously. Counselors and OSDS staff can read the Peer Circle, where they only see your anonymous name. Only you can see your own mood check-ins, and OSDS sees combined totals only.</li>
    <li><strong>Your rights:</strong> you can ask <?= e(PRIVACY_CONTACT) ?> to see, correct, or delete your information.</li>
  </ul>
  <p class="small muted">This notice is given under the Data Privacy Act of 2012 (Republic Act No. 10173).</p>
  <label class="consent"><input type="checkbox" id="consent-check"><span>I have read this notice and I agree to how GABAY uses my information.</span></label>
  <div class="dialog-actions">
    <button type="button" class="btn" id="consent-go" disabled aria-describedby="consent-hint">Continue</button>
    <button type="button" class="btn btn-quiet" id="consent-cancel">Go back</button>
  </div>
  <p class="hint" id="consent-hint">Tick the box to turn on the Continue button.</p>
</dialog>
<noscript>
  <div class="auth-noscript"><strong>Need immediate support?</strong>
    <ul class="hotline-list"><?php foreach (hotlines() as $l): ?><li><span><?= e($l['name']) ?></span> <strong><?= e($l['number']) ?></strong></li><?php endforeach; ?></ul></div>
</noscript>
<?php render_footer(['assets/js/crisis.js', 'assets/js/login.js']);
