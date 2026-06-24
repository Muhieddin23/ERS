<?php
// ============================================================
//  login.php  —  User Login
//  FR-03: Authenticate via email + password
//  FR-04: Create session, 30-min inactivity expiry
//  UC-02: Account lock after 5 failed attempts (15 min)
// ============================================================

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';

session_boot();

// Already logged in → redirect
if (session_validate()) {
    header('Location: http://localhost/ers/events.php');
    exit;
}

$errors  = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $oldEmail = $email;

    // ── Empty field checks ────────────────────────────────────
    if ($email === '' || $password === '') {
        $errors['general'] = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        // ── Fetch user ────────────────────────────────────────
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Deliberately vague — never reveal which field failed (UC-02)
            $errors['general'] = 'Invalid email or password.';
        } elseif ($user['status'] === 'deactivated') {
            $errors['general'] = 'Account deactivated. Contact support.';
        } elseif ($user['status'] === 'locked' && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
            $errors['general'] = "Account temporarily locked. Try again in {$remaining} minute(s).";
        } elseif (!password_verify($password, $user['password_hash'])) {
            // Increment failed attempt counter
            $newAttempts = $user['failed_attempts'] + 1;
            if ($newAttempts >= 5) {
                $lockUntil = date('Y-m-d H:i:s', time() + 900); // lock 15 min
                $pdo->prepare(
                    "UPDATE users SET failed_attempts = ?, status = 'locked', locked_until = ? WHERE id = ?"
                )->execute([$newAttempts, $lockUntil, $user['id']]);
                $errors['general'] = 'Account temporarily locked. Try again later.';
            } else {
                $pdo->prepare(
                    "UPDATE users SET failed_attempts = ? WHERE id = ?"
                )->execute([$newAttempts, $user['id']]);
                $errors['general'] = 'Invalid email or password.';
            }
        } else {
            // ── Successful login ──────────────────────────────
            // Reset failed attempts / unlock
            $pdo->prepare(
                "UPDATE users SET failed_attempts = 0, status = 'active', locked_until = NULL WHERE id = ?"
            )->execute([$user['id']]);

            session_create_for($user['id']);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            flash_set('success', 'Welcome back, ' . $user['full_name'] . '!');

            // Role-based redirect
            if ($user['role'] === 'admin') {
                header('Location: http://localhost/ers/admin/dashboard.php');
            } else {
                header('Location: http://localhost/ers/events.php');
            }
            exit;
        }
    }
}

$flash_error = flash_get('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — ERS</title>
  <link rel="stylesheet" href="/ers/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">

    <div class="auth-logo">
      <div class="logo-circle">📅</div>
      <h1>Welcome Back</h1>
      <p>Event Registration System</p>
    </div>

    <?php if ($flash_error): ?>
      <div class="alert alert-danger">⚠️ <?= htmlspecialchars($flash_error) ?></div>
    <?php endif; ?>

    <?php if (isset($errors['general'])): ?>
      <div class="alert alert-danger">
        <span>⚠️</span>
        <span><?= htmlspecialchars($errors['general']) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="/ers/login.php" novalidate>

      <!-- Email -->
      <div class="form-group">
        <label for="email">Email Address <span class="required">*</span></label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-control <?= (isset($errors['email']) || isset($errors['general'])) ? 'is-invalid' : '' ?>"
          value="<?= htmlspecialchars($oldEmail) ?>"
          placeholder="you@example.com"
          autocomplete="email"
          autofocus
        >
        <?php if (isset($errors['email'])): ?>
          <div class="field-error">⚠ <?= htmlspecialchars($errors['email']) ?></div>
        <?php endif; ?>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label for="password">Password <span class="required">*</span></label>
        <input
          type="password"
          id="password"
          name="password"
          class="form-control <?= isset($errors['general']) ? 'is-invalid' : '' ?>"
          placeholder="Your password"
          autocomplete="current-password"
        >
      </div>

      <button type="submit" class="btn btn-primary btn-block mt-2">
        Sign In
      </button>

    </form>

    <p class="text-center mt-3" style="font-size:.88rem;color:var(--text-3)">
      Don't have an account?
      <a href="/ers/register.php" style="font-weight:600">Register here</a>
    </p>

  </div>
</div>
</body>
</html>
