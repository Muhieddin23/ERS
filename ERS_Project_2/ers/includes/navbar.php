<?php
// ============================================================
//  includes/navbar.php  —  Shared Navigation Bar
//  Call after require_once session.php and validating the user
//  Usage: include __DIR__ . '/includes/navbar.php';
//  Expects: $currentUser (user row from session_validate)
//           $activePage  (string: 'events' | 'my-reg' | 'admin')
// ============================================================

$activePage = $activePage ?? '';
$isAdmin    = ($currentUser['role'] ?? '') === 'admin';
?>
<nav class="navbar">
  <div class="container">
    <div class="navbar-inner">

      <a href="/ers/events.php" class="navbar-brand">
        <span class="brand-icon">📅</span>
        ERS
      </a>

      <ul class="navbar-nav">
        <li>
          <a href="/ers/events.php" class="<?= $activePage === 'events' ? 'active' : '' ?>">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <span>Events</span>
          </a>
        </li>
        <li>
          <a href="/ers/my_registrations.php" class="<?= $activePage === 'my-reg' ? 'active' : '' ?>">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
            <span>My Registrations</span>
          </a>
        </li>
        <?php if ($isAdmin): ?>
        <li>
          <a href="/ers/admin/dashboard.php" class="<?= $activePage === 'admin' ? 'active' : '' ?>">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span>Admin</span>
          </a>
        </li>
        <?php endif; ?>
        <li>
          <span style="font-size:.85rem;color:var(--text-3);padding:0 6px">
            <?= htmlspecialchars($currentUser['full_name']) ?>
          </span>
        </li>
        <li>
          <form method="POST" action="/ers/logout.php" style="margin:0">
            <button type="submit" class="btn-logout">
              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
              Logout
            </button>
          </form>
        </li>
      </ul>

    </div>
  </div>
</nav>
