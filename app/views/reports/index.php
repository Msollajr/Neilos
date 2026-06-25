<?php // Reports Index View ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Reports</div>
    <div class="page-subtitle">Download CSV reports for analysis</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px">
  <!-- Orders Report -->
  <div class="card">
    <div class="card-body" style="padding:24px;text-align:center">
      <div style="font-size:2.5rem;margin-bottom:12px;color:var(--primary)"><?= svgIcon('list', 36) ?></div>
      <div class="card-title" style="margin-bottom:4px">Orders Report</div>
      <p class="text-secondary font-sm" style="margin-bottom:16px">All orders with partner, customer, service type, KAM, status, and dates.</p>
      <a href="?page=reports&action=orders" class="btn btn-primary"><?= svgIcon('download') ?> Download CSV</a>
    </div>
  </div>

  <!-- Order SLA Report -->
  <div class="card">
    <div class="card-body" style="padding:24px;text-align:center">
      <div style="font-size:2.5rem;margin-bottom:12px;color:var(--warning)"><?= svgIcon('clock', 36) ?></div>
      <div class="card-title" style="margin-bottom:4px">Order SLA Report</div>
      <p class="text-secondary font-sm" style="margin-bottom:16px">Order stage timestamps and durations for SLA analysis.</p>
      <a href="?page=reports&action=order_sla" class="btn btn-primary"><?= svgIcon('download') ?> Download CSV</a>
    </div>
  </div>

  <!-- KYC Report -->
  <div class="card">
    <div class="card-body" style="padding:24px;text-align:center">
      <div style="font-size:2.5rem;margin-bottom:12px;color:var(--info)"><?= svgIcon('document', 36) ?></div>
      <div class="card-title" style="margin-bottom:4px">KYC Report</div>
      <p class="text-secondary font-sm" style="margin-bottom:16px">KYC applications with partner info, status, and review details.</p>
      <a href="?page=reports&action=kyc" class="btn btn-primary"><?= svgIcon('download') ?> Download CSV</a>
    </div>
  </div>

  <!-- Tickets Report -->
  <div class="card">
    <div class="card-body" style="padding:24px;text-align:center">
      <div style="font-size:2.5rem;margin-bottom:12px;color:var(--danger)"><?= svgIcon('ticket', 36) ?></div>
      <div class="card-title" style="margin-bottom:4px">Tickets Report</div>
      <p class="text-secondary font-sm" style="margin-bottom:16px">All tickets with partner, service, queue, severity, SLA, and status.</p>
      <a href="?page=reports&action=tickets" class="btn btn-primary"><?= svgIcon('download') ?> Download CSV</a>
    </div>
  </div>

  <!-- Ticket SLA Report -->
  <div class="card">
    <div class="card-body" style="padding:24px;text-align:center">
      <div style="font-size:2.5rem;margin-bottom:12px;color:var(--accent)"><?= svgIcon('chart', 36) ?></div>
      <div class="card-title" style="margin-bottom:4px">Ticket SLA Report</div>
      <p class="text-secondary font-sm" style="margin-bottom:16px">Ticket response/resolution times and SLA compliance data.</p>
      <a href="?page=reports&action=ticket_sla" class="btn btn-primary"><?= svgIcon('download') ?> Download CSV</a>
    </div>
  </div>
</div>
