<?php
// ============================================================
//  includes/session.php  —  Session Management
//  FR-04: Sessions expire after 30 minutes of inactivity
//  FR-05: Logout destroys session fully
// ============================================================

require_once __DIR__ . '/db.php';

define('SESSION_LIFETIME', 1800); // 30 minutes in seconds

// -------------------------------------------------------
// Start or resume a secure PHP session
// -------------------------------------------------------
function session_boot(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,   // set true on HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// -------------------------------------------------------
// Validate the current session against DB
// Refreshes expiry on each call (sliding window)
// Returns user row or false
// -------------------------------------------------------
function session_validate(): array|false {
    global $pdo;
    session_boot();

    if (empty($_SESSION['token'])) return false;

    $stmt = $pdo->prepare(
        "SELECT s.*, u.id AS user_id, u.full_name, u.email, u.role, u.status
         FROM sessions s
         JOIN users u ON u.id = s.user_id
         WHERE s.session_token = ? AND s.expires_at > NOW()"
    );
    $stmt->execute([$_SESSION['token']]);
    $row = $stmt->fetch();

    if (!$row || $row['status'] !== 'active') {
        session_destroy_clean();
        return false;
    }

    // Slide the expiry window
    $newExpiry = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    $pdo->prepare("UPDATE sessions SET expires_at = ? WHERE session_token = ?")
        ->execute([$newExpiry, $_SESSION['token']]);

    return $row;
}

// -------------------------------------------------------
// Create a new DB session record and set PHP session var
// -------------------------------------------------------
function session_create_for(int $userId): void {
    global $pdo;
    session_boot();
    session_regenerate_id(true);

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

    $pdo->prepare(
        "INSERT INTO sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)"
    )->execute([$userId, $token, $expires]);

    $_SESSION['token'] = $token;
}

// -------------------------------------------------------
// Destroy session: DB record + PHP session (FR-05)
// -------------------------------------------------------
function session_destroy_clean(): void {
    global $pdo;
    if (!empty($_SESSION['token'])) {
        $pdo->prepare("DELETE FROM sessions WHERE session_token = ?")
            ->execute([$_SESSION['token']]);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}

// -------------------------------------------------------
// Guard helpers — redirect if not authenticated / not admin
// -------------------------------------------------------
function require_login(): array {
    $user = session_validate();
    if (!$user) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        header('Location: http://localhost/ers/login.php');
        exit;
    }
    return $user;
}

function require_admin(): array {
    $user = require_login();
    if ($user['role'] !== 'admin') {
        header('Location: http://localhost/ers/events.php');
        exit;
    }
    return $user;
}

// -------------------------------------------------------
// Flash message helpers
// -------------------------------------------------------
function flash_set(string $type, string $msg): void {
    session_boot();
    $_SESSION['flash_' . $type] = $msg;
}

function flash_get(string $type): string {
    session_boot();
    $msg = $_SESSION['flash_' . $type] ?? '';
    unset($_SESSION['flash_' . $type]);
    return $msg;
}
