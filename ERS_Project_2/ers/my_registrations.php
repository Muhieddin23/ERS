<?php
// ============================================================
//  my_registrations.php  —  View & Cancel Registrations
//  FR-12: View all registrations with status (Upcoming/Past)
//  FR-13: Cancel registration if >= 24 hrs before event
// ============================================================

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';

$currentUser = require_login();
$activePage  = 'my-reg';
$userId      = (int)$currentUser['user_id'];

// ── Handle cancellation POST ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reg_id'])) {
    $regId = (int)$_POST['cancel_reg_id'];

    $pdo->beginTransaction();
    try {
        // Fetch registration + event (lock for update)
        $stmt = $pdo->prepare(
            "SELECT r.*, e.event_date, e.status AS event_status, e.id AS event_id
             FROM registrations r
             JOIN events e ON e.id = r.event_id
             WHERE r.id = ? AND r.user_id = ?
             FOR UPDATE"
        );
        $stmt->execute([$regId, $userId]);
        $reg = $stmt->fetch();

        if (!$reg) {
            flash_set('danger', 'Registration not found.');
        } elseif (strtotime($reg['event_date']) < time()) {
            flash_set('danger', 'Cannot cancel a past event registration.');
        } elseif (strtotime($reg['event_date']) - time() < 86400) {
            // Less than 24 hours (FR-13)
            flash_set('danger', 'Cancellation is only allowed at least 24 hours before the event.');
        } else {
            $pdo->prepare("DELETE FROM registrations WHERE id = ?")
                ->execute([$regId]);
            $pdo->prepare("UPDATE events SET seats_remaining = seats_remaining + 1 WHERE id = ?")
                ->execute([$reg['event_id']]);
            flash_set('success', 'Registration cancelled successfully.');
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        flash_set('danger', 'An error occurred. Please try again.');
    }

    header('Location: http://localhost/ers/my_registrations.php');
    exit;
}

// ── Fetch all registrations for this user ─────────────────────
$registrations = $pdo->prepare(
    "SELECT r.id AS reg_id, r.registered_at,
            e.id AS event_id, e.name, e.event_date, e.venue, e.status AS event_status
     FROM registrations r
     JOIN events e ON e.id = r.event_id
     WHERE r.user_id = ?
     ORDER BY e.event_date ASC"
);
$registrations->execute([$userId]);
$registrations = $registrations->fetchAll();

$now = time();
$flash_success = flash_get('success');
$flash_danger  = flash_get('danger');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Registrations — ERS</title>
  <link rel="stylesheet" href="/ers/assets/css/style.css">
</head>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <main class="content-area">
    <div class="container">

      <div class="page-header">
        <h1>My Registrations</h1>
        <p>All events you have registered for.</p>
      </div>

      <?php if ($flash_success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($flash_success) ?></div>
      <?php endif; ?>
      <?php if ($flash_danger): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($flash_danger) ?></div>
      <?php endif; ?>

      <?php if (empty($registrations)): ?>
        <div class="card">
          <div class="card-body text-center" style="padding:48px">
            <div style="font-size:2.5rem;margin-bottom:12px">📋</div>
            <p class="text-muted">You have no current registrations.</p>
            <a href="/ers/events.php" class="btn btn-primary mt-2">Browse Events</a>
          </div>
        </div>
      <?php else: ?>
        <div class="card">
          <div class="card-header">
            <h2>Registered Events (<?= count($registrations) ?>)</h2>
          </div>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Event</th>
                  <th>Date</th>
                  <th>Venue</th>
                  <th>Status</th>
                  <th>Registered On</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($registrations as $reg):
                  $eventTs   = strtotime($reg['event_date']);
                  $isUpcoming = $eventTs >= $now;
                  $canCancel  = $isUpcoming && ($eventTs - $now) >= 86400;
                  $hoursLeft  = ($eventTs - $now) / 3600;
                ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($reg['name']) ?></strong>
                  </td>
                  <td style="white-space:nowrap">
                    <?= date('d M Y', $eventTs) ?><br>
                    <span style="font-size:.8rem;color:var(--text-3)"><?= date('g:i A', $eventTs) ?></span>
                  </td>
                  <td><?= htmlspecialchars($reg['venue']) ?></td>
                  <td>
                    <?php if ($isUpcoming): ?>
                      <span class="badge badge-success">Upcoming</span>
                    <?php else: ?>
                      <span class="badge badge-gray">Past</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.85rem;color:var(--text-2);white-space:nowrap">
                    <?= date('d M Y', strtotime($reg['registered_at'])) ?>
                  </td>
                  <td>
                    <?php if ($canCancel): ?>
                      <form method="POST" action="/ers/my_registrations.php" style="margin:0"
                            onsubmit="return confirm('Are you sure you want to cancel your registration for \'<?= addslashes(htmlspecialchars($reg['name'])) ?>\'?')">
                        <input type="hidden" name="cancel_reg_id" value="<?= $reg['reg_id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                      </form>
                    <?php elseif ($isUpcoming && $hoursLeft < 24): ?>
                      <span class="text-muted" style="font-size:.82rem">Within 24h</span>
                    <?php else: ?>
                      <span class="text-muted" style="font-size:.82rem">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </main>

</div>
</body>
</html>
