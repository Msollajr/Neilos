<?php
requireLogin();

$pageTitle = 'Coverage Check';

switch ($_GET['action'] ?? '') {
    case 'proceed':
        // Store coverage result and redirect to new order
        $_SESSION['coverage_result'] = $_GET;
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;

    default:
        include APP_DIR . '/views/layout/header.php';
        ?>
        <div class="page-header">
          <div class="page-header-left">
            <div class="page-title">Coverage & Feasibility Check</div>
            <div class="page-subtitle">Verify service availability at customer location</div>
          </div>
          <div class="page-header-actions">
            <a href="<?= APP_URL ?>/?page=new_order" class="btn btn-primary"><?= svgIcon('plus') ?> New Service Order</a>
          </div>
        </div>

        <div class="card">
          <div class="card-body" style="text-align:center;padding:40px">
            <div style="font-size:3rem;color:var(--primary);margin-bottom:16px"><?= svgIcon('map', 48) ?></div>
            <h3 style="margin-bottom:8px">Neilos Coverage Portal</h3>
            <p style="color:var(--text-secondary);margin-bottom:24px;max-width:500px;margin-left:auto;margin-right:auto">
              Check service availability by address or GPS coordinates. This will open the Neilos Coverage Checker in a new tab.
            </p>
            <a href="https://coverage.neilosnetwork.co.tz" target="_blank" class="btn btn-primary btn-lg">
              <?= svgIcon('link') ?> Open Coverage Checker
            </a>
          </div>
        </div>
        <?php
        include APP_DIR . '/views/layout/footer.php';
}
