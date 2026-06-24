<?php
// ============================================================
//  admin/delete_event.php  —  Delete Event
//  FR-19: Only allowed when event has zero registrations
// ============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['event_id'])) {
    header('Location: http://localhost/ers/admin/dashboard.php');
    exit;
}

$eventId = (int)$_POST['event_id'];

// Check event exists
$stmt = $pdo->prepare("SELECT id FROM events WHERE id = ?");
$stmt->execute([$eventId]);
if (!$stmt->fetch()) {
    flash_set('danger', 'Event not found.');
    header('Location: http://localhost/ers/admin/dashboard.php');
    exit;
}

// Check zero registrations (FR-19)
$regCount = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ?");
$regCount->execute([$eventId]);
$count = (int)$regCount->fetchColumn();

if ($count > 0) {
    flash_set('danger', 'Cannot delete event — registrations exist. Close the event instead.');
} else {
    $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$eventId]);
    flash_set('success', 'Event deleted successfully.');
}

header('Location: http://localhost/ers/admin/dashboard.php');
exit;
