<?php
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $path): string { return BASE_URL . '/' . ltrim($path, '/'); }
// URL for a static file with a version stamp, so browsers always load the newest CSS/JS after an update.
function asset(string $path): string {
    $f = __DIR__ . '/../' . ltrim($path, '/');
    return url($path) . (is_file($f) ? '?v=' . filemtime($f) : '');
}
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function now(): string { return date('Y-m-d H:i:s'); }
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

// flash() shows a pop-up on the NEXT page load (use before a redirect).
function flash(string $type, string $msg, ?string $title = null, ?array $action = null): void { $_SESSION['flash'][] = [$type, $msg, $title, $action]; }
// notify() shows a pop-up on THIS page load. $action = ['Button label', 'url'].
function notify(string $type, string $message, ?string $title = null, ?array $action = null): void {
    $GLOBALS['gabay_notices'][] = ['type' => $type, 'title' => $title, 'message' => $message, 'action' => $action];
}
function take_flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function fmt_slot(string $start, string $end): string {
    return date('D, M j', strtotime($start)) . ', ' . date('g:i A', strtotime($start)) . ' to ' . date('g:i A', strtotime($end));
}
const MODES = ['in_person' => 'In person', 'chat' => 'Chat', 'video' => 'Video call'];   // all modes, for showing older bookings
const BOOKABLE_MODES = ['in_person' => 'In person', 'video' => 'Video call'];            // what students can choose now
const STATUSES = ['pending' => 'Waiting for the counselor', 'confirmed' => 'Confirmed', 'declined' => 'Declined',
                  'done' => 'Completed', 'cancelled' => 'Cancelled'];
function status_pill(string $s): string {
    return '<span class="pill pill-' . e($s) . '">' . e(STATUSES[$s] ?? $s) . '</span>';
}

function mask_email(string $email): string {
    [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
    return substr($local, 0, 1) . str_repeat('*', max(3, strlen($local) - 1)) . '@' . $domain;
}
function temp_password(): string {
    $c = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $p = ''; for ($i = 0; $i < 12; $i++) $p .= $c[random_int(0, strlen($c) - 1)];
    return $p;
}

function random_alias(): string {
    $adj = ['Quiet','Gentle','Calm','Steady','Kind','Bright','Soft','Patient','Brave','Warm','Still','Mellow','Hopeful','Cozy','Honest','Sunny'];
    $ani = ['Owl','Heron','Otter','Fox','Deer','Sparrow','Panda','Koala','Dolphin','Swan','Rabbit','Lark','Turtle','Finch','Whale','Robin'];
    for ($i = 0; $i < 25; $i++) {
        $a = $adj[array_rand($adj)] . ' ' . $ani[array_rand($ani)];
        if (!q('SELECT 1 FROM users WHERE alias = ?', [$a])->fetch()) return $a;
    }
    return $a . ' ' . random_int(100, 999);
}
function ref_code(): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $c = 'GB-';
        for ($i = 0; $i < 5; $i++) $c .= $chars[random_int(0, strlen($chars) - 1)];
    } while (q('SELECT 1 FROM appointments WHERE ref_code = ?', [$c])->fetch());
    return $c;
}

// Line chart on a fixed 1 to 5 scale. $pts = [[label, value], ...]. Pure SVG, no libraries.
function line_chart(array $pts, string $description): string {
    $W = 600; $H = 220; $l = 34; $r = 16; $t = 14; $b = 34;
    $n = count($pts); $iw = $W - $l - $r; $ih = $H - $t - $b;
    $x = fn($i) => round($l + ($n > 1 ? $i * $iw / ($n - 1) : $iw / 2), 1);
    $y = fn($v) => round($t + (5 - $v) * $ih / 4, 1);
    $s = '<svg class="chart" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . e($description) . '">';
    for ($v = 1; $v <= 5; $v++) {
        $yy = $y($v);
        $s .= '<line class="grid" x1="' . $l . '" x2="' . ($W - $r) . '" y1="' . $yy . '" y2="' . $yy . '"/>'
            . '<text class="axis" x="' . ($l - 8) . '" y="' . ($yy + 4) . '" text-anchor="end">' . $v . '</text>';
    }
    $line = [];
    foreach (array_values($pts) as $i => [$lab, $val]) {
        $line[] = $x($i) . ',' . $y($val);
        $s .= '<text class="axis" x="' . $x($i) . '" y="' . ($H - 10) . '" text-anchor="middle">' . e($lab) . '</text>';
    }
    if ($n > 1) $s .= '<polyline class="line" points="' . implode(' ', $line) . '"/>';
    foreach (array_values($pts) as $i => [$lab, $val]) {
        $s .= '<circle class="dot" cx="' . $x($i) . '" cy="' . $y($val) . '" r="5"><title>' . e($lab . ': ' . round($val, 1)) . '</title></circle>';
    }
    return $s . '</svg>';
}

// Lucide icons (ISC licence), inline so no network is needed.
function icon(string $name, int $size = 18): string {
    static $p = [
        'dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'smile' => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'lifebuoy' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><path d="m4.93 4.93 4.24 4.24M14.83 9.17l4.24-4.24M14.83 14.83l4.24 4.24M9.17 14.83l-4.24 4.24"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'flag' => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'userplus' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'wind' => '<path d="M17.7 7.7a2.5 2.5 0 1 1 1.8 4.3H2M9.6 4.6A2 2 0 1 1 11 8H2M12.6 19.4A2 2 0 1 0 14 16H2"/>',
        'shield' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
        'message' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'video' => '<path d="m22 8-6 4 6 4V8z"/><rect width="14" height="12" x="2" y="6" rx="2"/>',
        'pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'plus' => '<path d="M5 12h14M12 5v14"/>',
        'trash' => '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'eyeoff' => '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61M2 2l20 20"/>',
        'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'eye' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
    ];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($p[$name] ?? '') . '</svg>';
}
