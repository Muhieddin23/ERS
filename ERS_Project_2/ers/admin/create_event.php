<?php
// ============================================================
//  admin/create_event.php  —  Admin Create Event
//  FR-15: Create event with all 6 required fields
//  FR-16: Validate date (future), capacity (>= 1), deadline < date
//  FR-21: Duplicate event name prevention
// ============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

$currentUser = require_admin();
$activePage  = 'admin';

$errors = [];
$old    = ['name' => '', 'description' => '', 'venue' => '',
           'capacity' => '', 'event_date' => '', 'registration_deadline' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name']                  ?? '');
    $description  = trim($_POST['description']           ?? '');
    $venue        = trim($_POST['venue']                 ?? '');
    $capacity     = trim($_POST['capacity']              ?? '');
    $event_date   = trim($_POST['event_date']            ?? '');
    $deadline     = trim($_POST['registration_deadline'] ?? '');

    $old = compact('name','description','venue','capacity','event_date','deadline');
    $old['registration_deadline'] = $deadline;

    // ── Validation ──────────────────────────────────────────────
    if ($name === '') {
        $errors['name'] = 'Event name is required.';
    } elseif (strlen($name) > 150) {
        $errors['name'] = 'Event name must not exceed 150 characters.';
    }

    if ($description === '') {
        $errors['description'] = 'Description is required.';
    }

    if ($venue === '') {
        $errors['venue'] = 'Venue is required.';
    } elseif (strlen($venue) > 150) {
        $errors['venue'] = 'Venue must not exceed 150 characters.';
    }

    if ($capacity === '') {
        $errors['capacity'] = 'Capacity is required.';
    } elseif (!ctype_digit($capacity) || (int)$capacity < 1) {
        $errors['capacity'] = 'Capacity must be a positive number.';
    }

    if ($event_date === '') {
        $errors['event_date'] = 'Event date is required.';
    } elseif (strtotime($event_date) === false) {
        $errors['event_date'] = 'Enter a valid date and time.';
    } elseif (strtotime($event_date) <= time()) {
        $errors['event_date'] = 'Event date must be in the future.';
    }

    if ($deadline === '') {
        $errors['registration_deadline'] = 'Registration deadline is required.';
    } elseif (strtotime($deadline) === false) {
        $errors['registration_deadline'] = 'Enter a valid date and time.';
    } elseif (!isset($errors['event_date']) && strtotime($deadline) >= strtotime($event_date)) {
        $errors['registration_deadline'] = 'Registration deadline must be before the event date.';
    }

    // ── Duplicate name check (FR-21) ─────────────────────────────
    if (empty($errors['name'])) {
        $stmt = $pdo->prepare("SELECT id FROM events WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $errors['name'] = 'An event with this name already exists.';
        }
    }

    // ── Save ──────────────────────────────────────────────────────
    if (empty($errors)) {
        $cap = (int)$capacity;
        $pdo->prepare(
            "INSERT INTO events
             (name, description, venue, capacity, seats_remaining, event_date, registration_deadline, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Open')"
        )->execute([
            $name, $description, $venue, $cap, $cap,
            date('Y-m-d H:i:s', strtotime($event_date)),
            date('Y-m-d H:i:s', strtotime($deadline))
        ]);

        flash_set('success', 'Event created successfully! Event is now live.');
        header('Location: http://localhost/ers/admin/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Event — ERS Admin</title>
  <link rel="stylesheet" href="/ers/assets/css/style.css">
</head>
<body>
<div class="page-wrapper">

  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <main class="content-area">
    <div class="container" style="max-width:680px">

      <div class="flex-between mb-3">
        <div class="page-header" style="margin-bottom:0">
          <h1>Create New Event</h1>
          <p>All fields are required.</p>
        </div>
        <a href="/ers/admin/dashboard.php" class="btn btn-secondary btn-sm">← Back</a>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">⚠️ Please fix the errors below.</div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body">
          <form method="POST" action="/ers/admin/create_event.php" novalidate>

            <!-- Event Name -->
            <div class="form-group">
              <label for="name">Event Name <span class="required">*</span></label>
              <input type="text" id="name" name="name"
                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['name']) ?>"
                placeholder="e.g. AI Summit 2026" maxlength="150">
              <?php if (isset($errors['name'])): ?>
                <div class="field-error">⚠ <?= htmlspecialchars($errors['name']) ?></div>
              <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="form-group">
              <label for="description">Description <span class="required">*</span></label>
              <textarea id="description" name="description"
                class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                placeholder="Describe the event, topics, and what attendees can expect…"
                rows="4"><?= htmlspecialchars($old['description']) ?></textarea>
              <?php if (isset($errors['description'])): ?>
                <div class="field-error">⚠ <?= htmlspecialchars($errors['description']) ?></div>
              <?php endif; ?>
            </div>

            <!-- Venue -->
            <div class="form-group">
              <label for="venue">Venue <span class="required">*</span></label>
              <input type="text" id="venue" name="venue"
                class="form-control <?= isset($errors['venue']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['venue']) ?>"
                placeholder="e.g. Main Auditorium, UTM KL" maxlength="150">
              <?php if (isset($errors['venue'])): ?>
                <div class="field-error">⚠ <?= htmlspecialchars($errors['venue']) ?></div>
              <?php endif; ?>
            </div>

            <!-- Capacity -->
            <div class="form-group">
              <label for="capacity">Capacity <span class="required">*</span></label>
              <input type="number" id="capacity" name="capacity"
                class="form-control <?= isset($errors['capacity']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['capacity']) ?>"
                placeholder="e.g. 100" min="1">
              <?php if (isset($errors['capacity'])): ?>
                <div class="field-error">⚠ <?= htmlspecialchars($errors['capacity']) ?></div>
              <?php else: ?>
                <div class="form-hint">Minimum 1 seat.</div>
              <?php endif; ?>
            </div>

            <!-- Event Date -->
            <div class="form-group">
              <label for="event_date">Event Date & Time <span class="required">*</span></label>
              <input type="datetime-local" id="event_date" name="event_date"
                class="form-control <?= isset($errors['event_date']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['event_date']) ?>">
              <?php if (isset($errors['event_date'])): ?>
                <div class="field-error">⚠ <?= htmlspecialchars($errors['event_date']) ?></div>
              <?php else: ?>
                <div class="form-hint">Must be a future date.</div>
              <?php endif; ?>
            </div>

            <!-- Registration Deadline -->
            <div class="form-group">
              <label for="registration_deadline">Registration Deadline <span class="required">*</span></label>
              <input type="datetime-local" id="registration_deadline" name="registration_deadline"
                class="form-control <?= isset($errors['registration_deadline']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['registration_deadline']) ?>">
              <?php if (isset($errors['registration_deadline'])): ?>
                <div class="field-error">⚠ <?= htmlspecialchars($errors['registration_deadline']) ?></div>
              <?php else: ?>
                <div class="form-hint">Must be before the event date.</div>
              <?php endif; ?>
            </div>

            <div class="flex gap-2 mt-3">
              <button type="submit" class="btn btn-primary">Create Event</button>
              <a href="/ers/admin/dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>

          </form>
        </div>
      </div>

    </div>
  </main>

</div>
</body>
</html>
