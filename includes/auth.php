<?php
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

function current_user(): ?array {
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $row = q('SELECT id, role, email, full_name, alias, title, must_change_password FROM users WHERE id = ? AND is_active = 1', [$_SESSION['uid']])->fetch();
            $user = $row ?: null;
        }
    }
    return $user;
}
function home_for(string $role): string { return $role . '/dashboard.php'; }

// Guests go to the login page; a signed-in user on the wrong role's page goes to their own dashboard.
function require_role(string $role): array {
    $u = current_user();
    if (!$u) redirect('index.php');
    if ($u['role'] !== $role) redirect(home_for($u['role']));
    // An admin set this password, so the person must choose their own before using anything else.
    if ($u['must_change_password'] && basename($_SERVER['SCRIPT_NAME']) !== 'account.php') redirect($role . '/account.php');
    return $u;
}
function log_in(int $id): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    $_SESSION['splash'] = true;   // replay the splash screen on the next page after logging in or signing up
}
function log_out(): void {
    $_SESSION = [];
    session_destroy();
}
