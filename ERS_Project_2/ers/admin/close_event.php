<?php
// ============================================================
//  admin/close_event.php  —  Close Event Registration
//  FR-18: Admin can close an Open event before its deadline
// ============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['event_id'])) {
    header('Location: http://localhost/ers/admin/dashboard.php');
    exit;
}

$eventId = (int)$_POST['event_id'];

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    flash_set('danger', 'Event not found.');
} elseif ($event['status'] !== 'Open') {
    flash_set('danger', 'Event is already closed or does not exist.');
} elseif (strtotime($event['registration_deadline']) < time()) {
    flash_set('danger', 'Cannot close event — deadline has already passed.');
} else {
    $pdo->prepare("UPDATE events SET status = 'Closed' WHERE id = ?")
        ->execute([$eventId]);
    flash_set('success', 'Event registration closed successfully.');
}

header('Location: http://localhost/ers/admin/dashboard.php');
exit;
