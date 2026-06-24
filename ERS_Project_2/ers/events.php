<?php
// ============================================================
//  events.php  —  Event Listing & Registration
//  FR-07: Display events with all required fields
//  FR-08: Register for an event
//  FR-09: Seat limit enforcement
//  FR-10: Duplicate registration prevention
//  FR-11: Registration deadline enforcement
// ============================================================

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';

$currentUser = require_login(); // redirects to login if not authenticated
$activePage  = 'events';

// ── Handle registration POST ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event_id'])) {
    $eventId = (int)$_POST['register_event_id'];
    $userId  = (int)$currentUser['user_id'];
    $result  = [];

    $pdo->beginTransaction();
    try {
        // Lock event row for update to prevent race conditions on seat count
        $stmt = $pdo->prepare(
            "SELECT * FROM events WHERE id = ? FOR UPDATE"
        );
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();

        // Check 1: Event exists and is active
        if (!$event || $event['status'] !== 'Open') {
            $result = ['type' => 'danger', 'msg' => 'This event is no longer available.'];
        }
        // Check 2: Deadline not passed (FR-11)
        elseif (strtotime($event['registration_deadline']) < time()) {
            $result = ['type' => 'danger', 'msg' => 'Registration for this event has closed.'];
        }
        // Check 3: Seats available (FR-09)
        elseif ($event['seats_remaining'] <= 0) {
            $result = ['type' => 'danger', 'msg' => 'Event is fully booked.'];
        }
        else {
            // Check 4: Not already registered (FR-10)
            $dup = $pdo->prepare(
                "SELECT id FROM registrations WHERE user_id = ? AND event_id = ?"
            );
            $dup->execute([$userId, $eventId]);
            if ($dup->fetch()) {
                $result = ['type' => 'warning', 'msg' => 'You are already registered for this event.'];
            } else {
                // ── All checks passed → register ──────────────
                $pdo->prepare(
                    "INSERT INTO registrations (user_id, event_id) VALUES (?, ?)"
                )->execute([$userId, $eventId]);

                $pdo->prepare(
                    "UPDATE events SET seats_remaining = seats_remaining - 1 WHERE id = ?"
                )->execute([$eventId]);

                $result = ['type' => 'success', 'msg' => 'Registration successful! Check your registrations.'];
            }
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $result = ['type' => 'danger', 'msg' => 'An error occurred. Please try again.'];
    }

    flash_set($result['type'], $result['msg']);
    header('Location: http://localhost/ers/events.php');
    exit;
}

// ── Load all events ────────────────────────────────────────────
$events = $pdo->query(
    "SELECT e.*,
            (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.user_id = {$currentUser['user_id']}) AS already_registered
     FROM events e
     ORDER BY e.event_date ASC"
)->fetchAll();

// ── Flash messages ─────────────────────────────────────────────
$flash_success = flash_get('success');
$flash_danger  = flash_get('danger');
$flash_warning = flash_get('warning');

$pageTitle = 'Events — ERS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="/ers/assets/css/style.css">
</head>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <main class="content-area">
    <div class="container">

      <div class="page-header">
        <h1>Upcoming Events</h1>
        <p>Browse and register for events below.</p>
      </div>

      <?php if ($flash_success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($flash_success) ?></div>
      <?php endif; ?>
      <?php if ($flash_danger): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($flash_danger) ?></div>
      <?php endif; ?>
      <?php if ($flash_warning): ?>
        <div class="alert alert-warning">ℹ️ <?= htmlspecialchars($flash_warning) ?></div>
      <?php endif; ?>

      <?php if (empty($events)): ?>
        <div class="card">
          <div class="card-body text-center" style="padding:48px">
            <div style="font-size:2.5rem;margin-bottom:12px">📭</div>
            <p class="text-muted">No events available at the moment. Check back soon!</p>
          </div>
        </div>
      <?php else: ?>
        <div class="events-grid">
          <?php foreach ($events as $ev):
            $now         = time();
            $eventTs     = strtotime($ev['event_date']);
            $deadlineTs  = strtotime($ev['registration_deadline']);
            $isPast      = $eventTs < $now;
            $deadlinePast = $deadlineTs < $now;
            $isFull      = (int)$ev['seats_remaining'] <= 0;
            $isClosed    = $ev['status'] === 'Closed';
            $alreadyReg  = (bool)$ev['already_registered'];
            $canRegister = !$isPast && !$deadlinePast && !$isFull && !$isClosed && !$alreadyReg;

            $pct = $ev['capacity'] > 0
                 ? ($ev['seats_remaining'] / $ev['capacity']) * 100
                 : 0;
            $fillClass = $pct > 40 ? '' : ($pct > 10 ? 'low' : 'empty');
          ?>
          <div class="event-card">
            <div class="event-card-accent"></div>
            <div class="event-card-body">
              <div class="flex-between mb-1">
                <span class="badge <?= $isClosed || $deadlinePast ? 'badge-gray' : ($isFull ? 'badge-danger' : 'badge-success') ?>">
                  <?= $isClosed ? 'Closed' : ($deadlinePast ? 'Deadline Passed' : ($isFull ? 'Fully Booked' : 'Open')) ?>
                </span>
                <?php if ($alreadyReg): ?>
                  <span class="badge badge-primary">✓ Registered</span>
                <?php endif; ?>
              </div>

              <h3><?= htmlspecialchars($ev['name']) ?></h3>

              <div class="event-card-meta">
                <div class="event-meta-item">
                  <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                  <?= date('D, d M Y • g:i A', $eventTs) ?>
                </div>
                <div class="event-meta-item">
                  <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                  <?= htmlspecialchars($ev['venue']) ?>
                </div>
                <div class="event-meta-item">
                  <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  Deadline: <?= date('d M Y', $deadlineTs) ?>
                </div>
                <div class="event-meta-item">
                  <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                  <?= $ev['seats_remaining'] ?> / <?= $ev['capacity'] ?> seats left
                </div>
              </div>

              <p style="font-size:.85rem;color:var(--text-2);line-height:1.5;flex:1">
                <?= htmlspecialchars(mb_substr($ev['description'], 0, 120)) ?>
                <?= mb_strlen($ev['description']) > 120 ? '…' : '' ?>
              </p>
            </div>

            <div class="event-card-footer">
              <div style="flex:1">
                <div class="seats-bar">
                  <div class="seats-fill <?= $fillClass ?>" style="width:<?= round($pct) ?>%"></div>
                </div>
                <div style="font-size:.75rem;color:var(--text-3);margin-top:4px">
                  <?= round($pct) ?>% available
                </div>
              </div>

              <?php if ($canRegister): ?>
                <form method="POST" action="/ers/events.php" style="margin:0">
                  <input type="hidden" name="register_event_id" value="<?= $ev['id'] ?>">
                  <button type="submit" class="btn btn-primary btn-sm">Register</button>
                </form>
              <?php elseif ($alreadyReg): ?>
                <span class="btn btn-success btn-sm" style="cursor:default">✓ Registered</span>
              <?php else: ?>
                <span class="btn btn-secondary btn-sm" style="cursor:default">Unavailable</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </main>

</div>
</body>
</html>
