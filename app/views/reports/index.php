<?php // Reports Index View ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Reports</div>
    <div class="page-subtitle">Download CSV reports for analysis</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px">
  <!-- Financial & Billing Commercials Report -->
  <div class="card">
    <div class="card-body" style="padding:24px;text-align:center">
      <div style="font-size:2.5rem;margin-bottom:12px;color:var(--accent)"><?= svgIcon('chart', 36) ?></div>
      <div class="card-title" style="margin-bottom:4px">Financial &amp; Billing Report</div>
      <p class="text-secondary font-sm" style="margin-bottom:16px">Monthly Recurring Revenue (MRC), Non-Recurring Charges (NRC), VAT, discounts &amp; currency metrics.</p>
      <a href="?page=reports&action=financial" class="btn btn-primary"><?= svgIcon('download') ?> Download CSV</a>
    </div>
  </div>

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

</div>
