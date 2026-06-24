<?php
// ============================================================
//  admin/dashboard.php  —  Admin Dashboard
//  FR-14: Restrict to admin role only
// ============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

$currentUser = require_admin(); // redirects non-admins to /events.php
$activePage  = 'admin';

// ── Stats ────────────────────────────────────────────────────
$stats = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM events)                         AS total_events,
      (SELECT COUNT(*) FROM events WHERE status='Open')     AS open_events,
      (SELECT COUNT(*) FROM users WHERE role='user')        AS total_users,
      (SELECT COUNT(*) FROM registrations)                  AS total_registrations
")->fetch();

// ── Events with registration counts ──────────────────────────
$events = $pdo->query("
    SELECT e.*,
           (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS reg_count
    FROM events e
    ORDER BY e.created_at DESC
")->fetchAll();

// ── Flash messages ────────────────────────────────────────────
$flash_success = flash_get('success');
$flash_danger  = flash_get('danger');
$flash_warning = flash_get('warning');
$now = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — ERS</title>
  <link rel="stylesheet" href="/ers/assets/css/style.css">
</head>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="content-area">
    <div class="container">

      <div class="flex-between mb-3">
        <div class="page-header" style="margin-bottom:0">
          <h1>Admin Dashboard</h1>
          <p>Manage events and attendees.</p>
        </div>
        <a href="/ers/admin/create_event.php" class="btn btn-primary">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
          Create Event
        </a>
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

      <!-- Stats Row -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">Total Events</div>
          <div class="stat-value"><?= $stats['total_events'] ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Open Events</div>
          <div class="stat-value text-success"><?= $stats['open_events'] ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Registered Users</div>
          <div class="stat-value"><?= $stats['total_users'] ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Registrations</div>
          <div class="stat-value text-primary"><?= $stats['total_registrations'] ?></div>
        </div>
      </div>

      <!-- Events Table -->
      <div class="card">
        <div class="card-header">
          <h2>All Events</h2>
        </div>
        <div class="table-wrapper">
          <?php if (empty($events)): ?>
            <div class="card-body text-center" style="padding:40px">
              <p class="text-muted">No events yet. <a href="/ers/admin/create_event.php">Create one</a>.</p>
            </div>
          <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Event</th>
                <th>Date</th>
                <th>Deadline</th>
                <th>Seats</th>
                <th>Registrations</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($events as $ev):
                $eventTs    = strtotime($ev['event_date']);
                $deadlineTs = strtotime($ev['registration_deadline']);
                $isOpen     = $ev['status'] === 'Open';
                $deadlinePast = $deadlineTs < $now;
                $canClose   = $isOpen && !$deadlinePast;
                $canDelete  = (int)$ev['reg_count'] === 0;
              ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($ev['name']) ?></strong>
                  <div style="font-size:.8rem;color:var(--text-3)"><?= htmlspecialchars(mb_substr($ev['venue'], 0, 40)) ?></div>
                </td>
                <td style="white-space:nowrap;font-size:.88rem"><?= date('d M Y', $eventTs) ?></td>
                <td style="white-space:nowrap;font-size:.88rem">
                  <?= date('d M Y', $deadlineTs) ?>
                  <?php if ($deadlinePast): ?><br><span style="font-size:.75rem;color:var(--danger)">Passed</span><?php endif; ?>
                </td>
                <td><?= $ev['seats_remaining'] ?>/<?= $ev['capacity'] ?></td>
                <td>
                  <?php if ((int)$ev['reg_count'] > 0): ?>
                    <a href="/ers/admin/attendees.php?event_id=<?= $ev['id'] ?>" class="text-primary" style="font-weight:600">
                      <?= $ev['reg_count'] ?> →
                    </a>
                  <?php else: ?>
                    <span class="text-muted">0</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= $isOpen ? 'badge-success' : 'badge-gray' ?>">
                    <?= $ev['status'] ?>
                  </span>
                </td>
                <td>
                  <div class="flex gap-2">
                    <!-- Close -->
                    <?php if ($canClose): ?>
                      <form method="POST" action="/ers/admin/close_event.php" style="margin:0"
                            onsubmit="return confirm('Close registration for \'<?= addslashes(htmlspecialchars($ev['name'])) ?>\'?')">
                        <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">Close</button>
                      </form>
                    <?php endif; ?>

                    <!-- Delete -->
                    <?php if ($canDelete): ?>
                      <form method="POST" action="/ers/admin/delete_event.php" style="margin:0"
                            onsubmit="return confirm('Permanently delete \'<?= addslashes(htmlspecialchars($ev['name'])) ?>\'? This cannot be undone.')">
                        <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                      </form>
                    <?php else: ?>
                      <span class="text-muted" style="font-size:.8rem" title="Cannot delete — has registrations">–</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>

</div>
</body>
</html>
