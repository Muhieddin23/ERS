<?php
// ============================================================
//  register.php  —  User Registration
//  FR-01: Full name, email, password (min 8 chars), age (>=18)
//  FR-02: Inline validation, valid fields retained
//  FR-06: Duplicate email prevention
// ============================================================

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';

session_boot();

// Already logged in → redirect
if (session_validate()) {
    header('Location: http://localhost/ers/events.php');
    exit;
}

$errors = [];
$old    = ['full_name' => '', 'email' => '', 'age' => ''];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']       ?? '';
    $confirm   = $_POST['confirm']        ?? '';
    $age       = $_POST['age']            ?? '';

    // Retain valid field values
    $old['full_name'] = $full_name;
    $old['email']     = $email;
    $old['age']       = $age;

    // ── Validation ──────────────────────────────────────────
    if ($full_name === '') {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($full_name) > 100) {
        $errors['full_name'] = 'Full name must not exceed 100 characters.';
    }

    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif (strlen($email) > 150) {
        $errors['email'] = 'Email address is too long.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($confirm === '') {
        $errors['confirm'] = 'Please confirm your password.';
    } elseif ($password !== $confirm) {
        $errors['confirm'] = 'Passwords do not match.';
    }

    if ($age === '') {
        $errors['age'] = 'Age is required.';
    } elseif (!ctype_digit((string)$age) || (int)$age < 18 || (int)$age > 120) {
        $errors['age'] = 'Age must be between 18 and 120.';
    }

    // ── Duplicate email check (FR-06) ────────────────────────
    if (empty($errors['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email already registered.';
            $old['email']    = ''; // clear the email field per spec
        }
    }

    // ── Save if clean ────────────────────────────────────────
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare(
            "INSERT INTO users (full_name, email, password_hash, age) VALUES (?, ?, ?, ?)"
        )->execute([$full_name, $email, $hash, (int)$age]);

        $success = 'Registration successful! Please log in.';
        $old     = ['full_name' => '', 'email' => '', 'age' => ''];
    }
}

$pageTitle = 'Create Account — ERS';
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
<div class="auth-wrapper">
  <div class="auth-card">

    <div class="auth-logo">
      <div class="logo-circle">📅</div>
      <h1>Create an Account</h1>
      <p>Event Registration System</p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <span>✅</span>
        <div>
          <?= htmlspecialchars($success) ?>
          <br><a href="/ers/login.php" style="font-weight:600">Go to Login →</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors) && !$success): ?>
      <div class="alert alert-danger">
        <span>⚠️</span>
        <span>Please fix the errors below.</span>
      </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="/ers/register.php" novalidate>

      <!-- Full Name -->
      <div class="form-group">
        <label for="full_name">Full Name <span class="required">*</span></label>
        <input
          type="text"
          id="full_name"
          name="full_name"
          class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
          value="<?= htmlspecialchars($old['full_name']) ?>"
          placeholder="e.g. Ahmad Muhieddin"
          maxlength="100"
          autocomplete="name"
        >
        <?php if (isset($errors['full_name'])): ?>
          <div class="field-error">⚠ <?= htmlspecialchars($errors['full_name']) ?></div>
        <?php endif; ?>
      </div>

      <!-- Email -->
      <div class="form-group">
        <label for="email">Email Address <span class="required">*</span></label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
          value="<?= htmlspecialchars($old['email']) ?>"
          placeholder="you@example.com"
          maxlength="150"
          autocomplete="email"
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
          class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
          placeholder="Minimum 8 characters"
          autocomplete="new-password"
        >
        <?php if (isset($errors['password'])): ?>
          <div class="field-error">⚠ <?= htmlspecialchars($errors['password']) ?></div>
        <?php else: ?>
          <div class="form-hint">At least 8 characters.</div>
        <?php endif; ?>
      </div>

      <!-- Confirm Password -->
      <div class="form-group">
        <label for="confirm">Confirm Password <span class="required">*</span></label>
        <input
          type="password"
          id="confirm"
          name="confirm"
          class="form-control <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
          placeholder="Repeat your password"
          autocomplete="new-password"
        >
        <?php if (isset($errors['confirm'])): ?>
          <div class="field-error">⚠ <?= htmlspecialchars($errors['confirm']) ?></div>
        <?php endif; ?>
      </div>

      <!-- Age -->
      <div class="form-group">
        <label for="age">Age <span class="required">*</span></label>
        <input
          type="number"
          id="age"
          name="age"
          class="form-control <?= isset($errors['age']) ? 'is-invalid' : '' ?>"
          value="<?= htmlspecialchars($old['age']) ?>"
          placeholder="18–120"
          min="18"
          max="120"
        >
        <?php if (isset($errors['age'])): ?>
          <div class="field-error">⚠ <?= htmlspecialchars($errors['age']) ?></div>
        <?php else: ?>
          <div class="form-hint">Must be 18 or older.</div>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary btn-block mt-2">
        Create Account
      </button>

    </form>
    <?php endif; ?>

    <p class="text-center mt-3" style="font-size:.88rem;color:var(--text-3)">
      Already have an account?
      <a href="/ers/login.php" style="font-weight:600">Sign in</a>
    </p>

  </div>
</div>
</body>
</html>
