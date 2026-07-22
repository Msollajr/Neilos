<?php
// Layout header — included at the top of every authenticated page
$flash = getFlash();
$user  = currentUser();
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Neilos Partner Portal — Order Management & Service Delivery">
  <title><?= e($pageTitle ?? 'Dashboard') ?> — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/portal.css?v=<?= time() ?>">
  <link rel="icon" type="image/png" href="<?= APP_URL ?>/favicon.ico?v=2">
  <link rel="shortcut icon" href="<?= APP_URL ?>/favicon.ico?v=2">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
  <script>var APP_URL = '<?= APP_URL ?>';</script>
</head>
<body>
<div class="portal-wrapper">

<!-- Mobile sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <script>
  (function() {
    var sb = document.getElementById('sidebar');
    if (!sb) return;
    try {
      if (localStorage.getItem('neilos_sidebar_collapsed') === '1' && window.innerWidth > 1024) {
        sb.classList.add('collapsed');
      }
      var pos = sessionStorage.getItem('neilos_sidebar_scroll');
      if (pos !== null) {
        sb.scrollTop = parseInt(pos, 10);
      }
    } catch(e) {}
  })();
  </script>
  <div class="sidebar-logo">
    <img src="<?= APP_URL ?>/assets/img/logo.png?v=2" alt="Neilos" class="sidebar-logo-img">
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>

    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=dashboard" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
        <?= svgIcon('dashboard') ?>
        <span>Dashboard</span>
      </a>
    </div>

    <div class="nav-section-label">Orders</div>

    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=coverage" class="nav-link <?= $currentPage === 'coverage' ? 'active' : '' ?>">
        <?= svgIcon('map') ?>
        <span>Coverage Check</span>
      </a>
    </div>

    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=new_order" class="nav-link <?= $currentPage === 'new_order' ? 'active' : '' ?>">
        <?= svgIcon('plus-circle') ?>
        <span>New Service Order</span>
      </a>
    </div>

    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=bulk_upload" class="nav-link <?= $currentPage === 'bulk_upload' ? 'active' : '' ?>">
        <?= svgIcon('upload') ?>
        <span>Bulk FTTH Upload</span>
      </a>
    </div>

    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=orders" class="nav-link <?= $currentPage === 'orders' ? 'active' : '' ?>">
        <?= svgIcon('list') ?>
        <span>Order Tracking</span>
      </a>
    </div>

    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=sla_tracking" class="nav-link <?= $currentPage === 'sla_tracking' ? 'active' : '' ?>">
        <?= svgIcon('clock') ?>
        <span>SLA Tracking</span>
      </a>
    </div>

    <div class="nav-section-label">Services & Support</div>

    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=active_services" class="nav-link <?= $currentPage === 'active_services' ? 'active' : '' ?>">
        <?= svgIcon('server') ?>
        <span>Active Services</span>
      </a>
    </div>

    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=tickets" class="nav-link <?= $currentPage === 'tickets' || $currentPage === 'ticket_detail' ? 'active' : '' ?>">
        <?= svgIcon('ticket') ?>
        <span>Trouble Tickets</span>
        <?php
        // Show open ticket count badge
        try {
          $db = getDB();
          $pw = partnerWhere('tt');
          $sql = "SELECT COUNT(*) FROM trouble_tickets tt WHERE tt.status NOT IN ('Closed') AND {$pw['condition']}";
          $st = $db->prepare($sql);
          $st->execute($pw['params']);
          $cnt = (int)$st->fetchColumn();
          if ($cnt > 0) echo "<span class='nav-badge'>$cnt</span>";
        } catch(Exception $e) {}
        ?>
      </a>
    </div>

    <?php if (hasRole('BSA', 'Project Team', 'Engineering Coordinator')): ?>
    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=projects" class="nav-link <?= $currentPage === 'projects' ? 'active' : '' ?>">
        <?= svgIcon('project') ?>
        <span>Project Delivery</span>
      </a>
    </div>
    <?php endif; ?>

    <div class="nav-section-label">Compliance</div>

    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=kyc" class="nav-link <?= $currentPage === 'kyc' ? 'active' : '' ?>">
        <?= svgIcon('document') ?>
        <span>KYC Application</span>
      </a>
    </div>

    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=assets" class="nav-link <?= $currentPage === 'assets' ? 'active' : '' ?>">
        <?= svgIcon('server') ?>
        <span>Asset Inventory</span>
      </a>
    </div>

    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=reports" class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>">
        <?= svgIcon('chart') ?>
        <span>Reports</span>
      </a>
    </div>

    <?php if (!isPartnerUser()): ?>
    <div class="nav-section-label">Administration</div>

    <?php if (isAdmin()): ?>
    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=partners" class="nav-link <?= $currentPage === 'partners' ? 'active' : '' ?>">
        <?= svgIcon('building') ?>
        <span>Partner Management</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=users" class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>">
        <?= svgIcon('users') ?>
        <span>User Management</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= APP_URL ?>/?page=activity_logs" class="nav-link <?= $currentPage === 'activity_logs' ? 'active' : '' ?>">
        <?= svgIcon('activity') ?>
        <span>Activity Logs</span>
      </a>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= APP_URL ?>/?page=profile" class="sidebar-user" style="text-decoration:none;color:inherit">
      <div class="sidebar-avatar">
        <?php $spPic = profilePictureUrl($user['profile_picture'] ?? null); ?>
        <?php if ($spPic): ?>
        <img src="<?= e($spPic) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
        <?php else: ?>
        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
        <?php endif; ?>
      </div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= e($user['full_name']) ?></div>
        <div class="sidebar-user-role"><?= e($user['role']) ?></div>
      </div>
    </a>
  </div>
  <script>
  (function() {
    var sb = document.getElementById('sidebar');
    if (!sb) return;
    try {
      var pos = sessionStorage.getItem('neilos_sidebar_scroll');
      if (pos !== null) {
        sb.scrollTop = parseInt(pos, 10);
      }
    } catch(e) {}
  })();
  </script>
</aside>

<!-- Main -->
<div class="main-content">
  <!-- Topbar -->
  <header class="topbar">
    <button class="hamburger" id="sidebarToggle" aria-label="Toggle navigation menu">
      <span></span><span></span><span></span>
    </button>
    <div class="topbar-title"><?= e($pageTitle ?? 'Dashboard') ?></div>
    <div class="topbar-actions">
      <span style="font-size:.8rem;color:var(--text-secondary)"><?= e($user['full_name']) ?></span>
      <!-- Theme toggle -->
      <button id="themeToggle" class="topbar-btn" title="Toggle light / dark mode" aria-label="Toggle theme">
        <!-- Moon icon (shown in dark mode) -->
        <svg class="theme-toggle-icon--dark" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z"/></svg>
        <!-- Sun icon (shown in light mode) -->
        <svg class="theme-toggle-icon--light" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      </button>
      <a href="<?= APP_URL ?>/?page=profile" class="topbar-btn" title="Profile" style="position:relative">
        <?php $tpPic = profilePictureUrl($user['profile_picture'] ?? ''); ?>
        <?php if ($tpPic): ?>
        <img src="<?= e($tpPic) ?>" alt="Profile" style="width:28px;height:28px;border-radius:50%;object-fit:cover">
        <?php else: ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        <?php endif; ?>
      </a>
      <a href="<?= APP_URL ?>/?page=logout" class="topbar-btn" title="Sign Out">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
  </header>

  <!-- Page Content -->
  <div class="page-content">
    <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
      <?= svgIcon($flash['type'] === 'success' ? 'check' : ($flash['type'] === 'danger' ? 'x' : 'info')) ?>
      <span><?= e($flash['message']) ?></span>
    </div>
    <?php endif; ?>
