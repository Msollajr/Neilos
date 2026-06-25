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
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/portal.css">
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
    <a href="<?= APP_URL ?>/?page=logout" class="nav-link" style="margin-top:12px;color:rgba(255,255,255,.65);">
      <?= svgIcon('logout') ?>
      <span>Sign Out</span>
    </a>
  </div>
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
