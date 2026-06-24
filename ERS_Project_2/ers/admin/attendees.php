<?php
// ============================================================
//  admin/attendees.php  —  View Attendees & Export CSV
//  FR-17: View all attendees: name, email, timestamp
//  FR-20: Export CSV with name, email, registration date
// ============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

$currentUser = require_admin();
$activePage  = 'admin';

$eventId = (int)($_GET['event_id'] ?? 0);
if (!$eventId) {
    header('Location: http://localhost/ers/admin/dashboard.php');
    exit;
}

// Fetch event info
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    flash_set('danger', 'Event not found.');
    header('Location: http://localhost/ers/admin/dashboard.php');
    exit;
}

// ── CSV export ─────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $attendees = $pdo->prepare(
        "SELECT u.full_name, u.email, r.registered_at
         FROM registrations r
         JOIN users u ON u.id = r.user_id
         WHERE r.event_id = ?
         ORDER BY r.registered_at ASC"
    );
    $attendees->execute([$eventId]);
    $rows = $attendees->fetchAll();

    $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $event['name']);
    $date     = date('Y-m-d');
    $filename = "attendees_{$safeName}_{$date}.csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Name', 'Email', 'Registration Date']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['full_name'],
            $row['email'],
            date('Y-m-d H:i:s', strtotime($row['registered_at']))
        ]);
    }
    fclose($out);
    exit;
}

// ── Fetch attendees ────────────────────────────────────────────
$attendees = $pdo->prepare(
    "SELECT u.full_name, u.email, r.registered_at
     FROM registrations r
     JOIN users u ON u.id = r.user_id
     WHERE r.event_id = ?
     ORDER BY r.registered_at ASC"
);
$attendees->execute([$eventId]);
$attendees = $attendees->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendees — ERS Admin</title>
  <link rel="stylesheet" href="/ers/assets/css/style.css">
</head>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="content-area">
    <div class="container">

      <div class="flex-between mb-3">
        <div class="page-header" style="margin-bottom:0">
          <h1>Attendees</h1>
          <p><?= htmlspecialchars($event['name']) ?></p>
        </div>
        <div class="flex gap-2">
          <?php if (!empty($attendees)): ?>
            <a href="/ers/admin/attendees.php?event_id=<?= $eventId ?>&export=csv"
               class="btn btn-success btn-sm">
              ⬇ Export CSV
            </a>
          <?php endif; ?>
          <a href="/ers/admin/dashboard.php" class="btn btn-secondary btn-sm">← Back</a>
        </div>
      </div>

      <!-- Event info strip -->
      <div class="card mb-3">
        <div class="card-body" style="padding:16px 20px">
          <div class="flex gap-3" style="flex-wrap:wrap">
            <div class="event-meta-item">
              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              <?= date('d M Y, g:i A', strtotime($event['event_date'])) ?>
            </div>
            <div class="event-meta-item">
              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
              <?= htmlspecialchars($event['venue']) ?>
            </div>
            <div class="event-meta-item">
              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
              <?= count($attendees) ?> / <?= $event['capacity'] ?> registered
            </div>
            <span class="badge <?= $event['status'] === 'Open' ? 'badge-success' : 'badge-gray' ?>">
              <?= $event['status'] ?>
            </span>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Registered Attendees (<?= count($attendees) ?>)</h2>
        </div>

        <?php if (empty($attendees)): ?>
          <div class="card-body text-center" style="padding:40px">
            <div style="font-size:2rem;margin-bottom:10px">👥</div>
            <p class="text-muted">No registrations found for this event.</p>
          </div>
        <?php else: ?>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Full Name</th>
                  <th>Email</th>
                  <th>Registration Timestamp</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($attendees as $i => $att): ?>
                <tr>
                  <td style="color:var(--text-3);font-family:var(--mono);font-size:.82rem">
                    <?= $i + 1 ?>
                  </td>
                  <td><strong><?= htmlspecialchars($att['full_name']) ?></strong></td>
                  <td style="font-family:var(--mono);font-size:.88rem">
                    <?= htmlspecialchars($att['email']) ?>
                  </td>
                  <td style="font-size:.85rem;color:var(--text-2)">
                    <?= date('d M Y, H:i:s', strtotime($att['registered_at'])) ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>

</div>
</body>
</html>
