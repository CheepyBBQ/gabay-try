<?php
function nav_items(string $role): array {
    return match ($role) {
        'student' => [
            'dashboard' => ['Dashboard', 'student/dashboard.php', 'dashboard'],
            'appointments' => ['Appointments', 'student/appointments.php', 'calendar'],
            'mood' => ['Mood Check-in', 'student/mood.php', 'smile'],
            'peer' => ['Peer Circle', 'student/peer.php', 'users'],
        ],
        'counselor' => [
            'dashboard' => ['Dashboard', 'counselor/dashboard.php', 'dashboard'],
            'slots' => ['My Slots', 'counselor/slots.php', 'clock'],
            'requests' => ['Requests', 'counselor/requests.php', 'inbox'],
            'flagged' => ['Flagged Posts', 'counselor/flagged.php', 'flag'],
            'community' => ['Peer Circle', 'counselor/community.php', 'users'],
        ],
        'admin' => [
            'dashboard' => ['Dashboard', 'admin/dashboard.php', 'dashboard'],
            'counselors' => ['Counselor Accounts', 'admin/counselors.php', 'userplus'],
            'admins' => ['Admin Accounts', 'admin/admins.php', 'shield'],
            'community' => ['Peer Circle', 'admin/community.php', 'users'],
        ],
        default => [],
    };
}

function render_header(string $title, string $active = '', ?array $user = null, bool $bare = false): void {
    $GLOBALS['gabay_bare'] = $bare;
    $crisisOpen = false;
    foreach (take_flashes() as $f) {
        if ($f[0] === 'support') { $crisisOpen = true; continue; }
        notify($f[0], $f[1], $f[2] ?? null, $f[3] ?? null);   // every notification becomes a pop-up
    }
    $GLOBALS['gabay_role'] = $user['role'] ?? '';
    $forceSplash = !empty($_SESSION['splash']);
    unset($_SESSION['splash']);
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | GABAY</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
<script>document.documentElement.classList.add('js');try{var force=<?= $forceSplash ? 'true' : 'false' ?>;var nav=(performance.getEntriesByType('navigation')[0]||{}).type;var internal=document.referrer.indexOf(location.origin)===0;if((force||nav==='reload'||(nav!=='back_forward'&&!internal))&&!matchMedia('(prefers-reduced-motion: reduce)').matches){document.documentElement.classList.add('splash-on')}}catch(e){}</script>
</head>
<body<?= $bare ? ' class="auth-page"' : '' ?><?= $crisisOpen ? ' data-crisis-open' : '' ?>>
<div class="splash" id="splash" role="dialog" aria-label="GABAY is loading">
  <div class="splash-stage">
    <span class="ring ring-1"></span><span class="ring ring-2"></span>
    <div class="splash-art"><img src="<?= e(url('assets/img/hands.png')) ?>" alt="" width="320" height="320"></div>
  </div>
  <p class="splash-name">GABAY</p>
  <p class="splash-line">You do not have to carry this alone.</p>
</div>
<a class="skip" href="#main">Skip to the main content</a>
<?php if (!$bare): ?>
<header class="topbar">
  <div class="topbar-in">
    <a class="brand" href="<?= e($user ? url(home_for($user['role'])) : url('index.php')) ?>"><?= icon('shield', 22) ?><span>GABAY</span></a>
    <?php if ($user): ?>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav"><?= icon('menu', 22) ?><span>Menu</span></button>
    <nav id="site-nav" class="nav" aria-label="Main">
      <?php foreach (nav_items($user['role']) as $key => [$label, $path, $ic]): ?>
        <a href="<?= e(url($path)) ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span></a>
      <?php endforeach; ?>
      <form method="post" action="<?= e(url('logout.php')) ?>" class="nav-out">
        <a class="who-link" href="<?= e(url($user['role'] . '/account.php')) ?>"<?= $active === 'account' ? ' aria-current="page"' : '' ?>><?= icon('user') ?><span><?= e($user['role'] === 'student' ? $user['alias'] : $user['full_name']) ?></span></a>
        <button type="submit"><?= icon('logout') ?><span>Log out</span></button>
      </form>
    </nav>
    <?php endif; ?>
  </div>
</header>
<?php endif; ?>
<main id="main"<?= $bare ? ' class="auth-main"' : ' class="wrap"' ?>>
<?php if ($crisisOpen): ?><noscript><div class="notice notice-support"><?= support_message_html() ?></div></noscript><?php endif; ?>
<?php
}

function render_footer(array $scripts = []): void { ?>
</main>
<?= notice_dialog_html() ?>
<?php if (($GLOBALS['gabay_role'] ?? '') === 'student') { echo crisis_dialog_html(true); $scripts[] = 'assets/js/crisis.js'; } ?>
<?php if (empty($GLOBALS['gabay_bare'])): ?>
<footer class="foot wrap"><p class="muted small">GABAY supports students but does not replace emergency care. If you are in danger, call your local emergency number.</p></footer>
<?php endif; ?>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
<script src="<?= e(asset('assets/js/splash.js')) ?>"></script>
<script src="<?= e(asset('assets/js/notice.js')) ?>"></script>
<?php foreach ($scripts as $s): ?><script src="<?= e(asset($s)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
<?php }

// Pop-up for every notification (same look as the crisis hotlines pop-up).
function notice_dialog_html(): string {
    $n = $GLOBALS['gabay_notices'] ?? [];
    if (!$n) return '';
    $defaults = ['ok' => 'All done', 'error' => 'Please check this', 'info' => 'A note for you'];
    $title = $n[0]['title'] ?? ($defaults[$n[0]['type']] ?? 'Notice');
    $action = null;
    foreach ($n as $x) if ($x['action']) { $action = $x['action']; break; }
    $h = '<dialog id="notice-dialog" class="notice-dialog" aria-labelledby="notice-h" data-auto><h2 id="notice-h">' . e($title) . '</h2>';
    if (count($n) === 1) $h .= '<p>' . e($n[0]['message']) . '</p>';
    else { $h .= '<ul class="notice-list">'; foreach ($n as $x) $h .= '<li>' . e($x['message']) . '</li>'; $h .= '</ul>'; }
    $h .= '<div class="dialog-actions">';
    if ($action) $h .= '<a class="btn" href="' . e($action[1]) . '">' . e($action[0]) . '</a>';
    $h .= '<button type="button" class="btn' . ($action ? ' btn-quiet' : '') . '" id="notice-close">Close</button></div></dialog>';
    // Without JavaScript the pop-up cannot open, so show the messages as plain text.
    $h .= '<noscript><div class="notice">';
    foreach ($n as $x) $h .= '<p>' . e($x['message']) . '</p>';
    return $h . '</div></noscript>';
}
