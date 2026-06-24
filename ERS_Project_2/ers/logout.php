<?php
// ============================================================
//  logout.php  —  User Logout (FR-05, UC-02b)
//  Only accepts POST to prevent CSRF via GET link
//  Destroys DB session + PHP session fully
// ============================================================

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';

session_boot();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_destroy_clean();
    flash_set('success', 'You have been logged out successfully.');
}

header('Location: http://localhost/ers/login.php');
exit;
