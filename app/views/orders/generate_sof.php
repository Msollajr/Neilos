<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SERVICE ORDER FORM (SOF) — <?= e($order['order_number']) ?></title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; font-size: 9pt; color: #0f172a; background: #f8fafc; }
    .page { width: 210mm; min-height: 297mm; margin: 20px auto; padding: 10mm 12mm; background: #fff; border: 1px solid #cbd5e1; box-shadow: 0 4px 10px rgba(0,0,0,0.08); position: relative; }
    
    .excel-header { display: flex; align-items: stretch; justify-content: space-between; margin-bottom: 14px; border: 1px solid #0F4C81; background: #0F4C81; border-radius: 2px; }
    .excel-title-box { flex: 1; padding: 10px 16px; font-size: 12pt; font-weight: 800; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; }
    .excel-logo-box { background: #ffffff; padding: 6px 16px; display: flex; align-items: center; justify-content: center; border-left: 1px solid #0F4C81; border-radius: 0 1px 1px 0; }
    .excel-logo-img { height: 40px; width: auto; object-fit: contain; }

    /* Section block wrapper for clean 12px vertical separation between tables */
    .sof-sec-block { margin-bottom: 12px; page-break-inside: avoid; break-inside: avoid; }
    .sof-sec-block:last-child { margin-bottom: 0; }

    .sec-banner { background: #0F4C81 !important; color: #ffffff !important; font-size: 8.5pt; font-weight: 700; padding: 5px 10px; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #0F4C81; border-radius: 2px 2px 0 0; }
    
    .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border: 1px solid #0F4C81; border-top: none; background: #fff; border-radius: 0 0 2px 2px; }
    .grid-col { padding: 0; }
    .grid-col:first-child { border-right: 1px solid #0F4C81; }
    .col-header { background: #f1f5f9 !important; color: #0F4C81 !important; font-weight: 700; font-size: 8.5pt; padding: 4px 8px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; }

    table.sof-grid { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    table.sof-grid td { padding: 4px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
    table.sof-grid tr:last-child td { border-bottom: none; }
    table.sof-grid td.lbl { font-weight: 600; color: #334155; width: 44%; background: #f8fafc !important; border-right: 1px solid #e2e8f0; }
    table.sof-grid td.val { font-weight: 600; color: #0f172a; }
    
    .chk { display: inline-block; width: 13px; height: 13px; border: 1px solid #0F4C81; text-align: center; line-height: 11px; font-size: 9px; font-weight: bold; color: #0F4C81; margin-right: 4px; border-radius: 2px; background: #fff; }
    .chk-active { background: #0F4C81 !important; color: #fff !important; }

    .box-content { border: 1px solid #0F4C81; border-top: none; padding: 8px 12px; font-size: 8pt; color: #1e293b; background: #fff; line-height: 1.4; border-radius: 0 0 2px 2px; }
    .box-content p { margin-bottom: 3px; }
    .box-content p:last-child { margin-bottom: 0; }

    .sig-table { width: 100%; border-collapse: collapse; border: 1px solid #0F4C81; border-top: none; background: #fff; border-radius: 0 0 2px 2px; }
    .sig-cell { width: 33.33%; padding: 8px 10px; vertical-align: top; border-right: 1px solid #cbd5e1; background: #fff; }
    .sig-cell:last-child { border-right: none; }
    .sig-role { font-size: 8.5pt; font-weight: 700; color: #0F4C81; text-transform: uppercase; border-bottom: 1px solid #0F4C81; padding-bottom: 3px; margin-bottom: 6px; }
    .sig-field { font-size: 8pt; color: #334155; margin-bottom: 3px; }
    .sig-line { border-bottom: 1px dashed #64748b; margin-top: 24px; margin-bottom: 3px; }
    .sig-sub { font-size: 7.5pt; color: #64748b; }

    .top-bar { position: fixed; top: 0; left: 0; right: 0; z-index: 999; background: #0F4C81; color: #fff; padding: 8px 20px; display: flex; align-items: center; justify-content: space-between; }
    .btn-action { background: #ef4444; color: #fff; text-decoration: none; padding: 6px 14px; border-radius: 4px; font-weight: 700; font-size: 8.5pt; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .btn-excel { background: #10b981; color: #fff; margin-right: 8px; }

    @media print {
      @page {
        size: A4 portrait;
        margin: 8mm 10mm;
      }
      *, *::before, *::after {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      html, body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
      }
      .top-bar, .no-print {
        display: none !important;
      }
      .page {
        margin: 0 auto !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
        width: 100% !important;
        max-width: 100% !important;
        min-height: auto !important;
        background: #ffffff !important;
      }
      .excel-header {
        margin-bottom: 12px !important;
        border: 1px solid #0F4C81 !important;
        background: #0F4C81 !important;
      }
      .excel-title-box {
        color: #ffffff !important;
        background: #0F4C81 !important;
      }
      .excel-logo-box {
        background: #ffffff !important;
        border-left: 1px solid #0F4C81 !important;
      }
      .sof-sec-block {
        margin-bottom: 12px !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }
      .sec-banner {
        background: #0F4C81 !important;
        color: #ffffff !important;
        border: 1px solid #0F4C81 !important;
      }
      .grid-2col {
        border: 1px solid #0F4C81 !important;
        border-top: none !important;
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
      }
      .grid-col:first-child {
        border-right: 1px solid #0F4C81 !important;
      }
      .col-header {
        background: #f1f5f9 !important;
        color: #0F4C81 !important;
        border-bottom: 1px solid #cbd5e1 !important;
      }
      table.sof-grid td.lbl {
        background: #f8fafc !important;
        color: #334155 !important;
        border-right: 1px solid #e2e8f0 !important;
      }
      .chk-active {
        background: #0F4C81 !important;
        color: #ffffff !important;
      }
      .box-content, .sig-table {
        border: 1px solid #0F4C81 !important;
        border-top: none !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }
      tr, td {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }
    }
  </style>
</head>
<body>

<?php
// Compute base64 logo URI from production assets
$logoFile = ROOT_DIR . '/public/assets/img/logo.png';
$logoSrc  = '';
if (file_exists($logoFile)) {
    $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
} else {
    $logoSrc = APP_URL . '/assets/img/logo.png';
}

// Authoritative calculation logic
$hasRevNrc = ($order['revised_nrc'] !== null && $order['revised_nrc'] !== '');
$stdNrc    = (float)($order['standard_nrc'] ?? $order['base_nrc_usd'] ?? 0);
$baseNrc   = $hasRevNrc ? (float)$order['revised_nrc'] : $stdNrc;
$rhNrc     = (float)($order['remote_hands_nrc_usd'] ?? 0);
$nrcSub    = $baseNrc + $rhNrc;
$vatNrc    = round($nrcSub * 0.18, 2);
$totNrc    = round($nrcSub + $vatNrc, 2);

$hasMgmtPrice = ($order['management_approved_price'] !== null && $order['management_approved_price'] !== '');
$hasRevMrc    = ($order['revised_mrc'] !== null && $order['revised_mrc'] !== '');
$stdMrc       = (float)($order['standard_mrc'] ?? $order['base_mrc'] ?? 0);
$mrcVal       = $hasMgmtPrice ? (float)$order['management_approved_price'] : ($hasRevMrc ? (float)$order['revised_mrc'] : $stdMrc);
$vatMrc       = round($mrcVal * 0.18, 2);
$totMrc       = round($mrcVal + $vatMrc, 2);

$st = $order['service_type'] ?? '';
$isL2    = str_contains($st, 'Layer 2');
$isIPT   = str_contains($st, 'IPT') || str_contains($st, 'DIA') || str_contains($st, 'BIA');
$isDF    = str_contains($st, 'Dark Fiber');
$isSDWAN = str_contains($st, 'SDWAN');
$isWiFi  = str_contains($st, 'Wi-Fi');
$isMPLS  = str_contains($st, 'MPLS');

$isRH = $rhNrc > 0 || $st === 'Remote Hands Only' || (float)($order['base_nrc_usd'] ?? 0) == 80000;
$isXC = !empty($order['nni_location']);
$isFeasible = $order['status'] !== 'Not Feasible';
?>

<!-- Print control bar -->
<div class="top-bar">
  <span style="font-weight:700">SERVICE ORDER FORM (SOF) — <?= e($order['order_number']) ?></span>
  <div>
    <a href="<?= APP_URL ?>/?page=orders&action=generate_sof&id=<?= $order['id'] ?>&format=excel" class="btn-action btn-excel">📥 Download Excel SOF (.xlsx)</a>
    <button onclick="window.print()" class="btn-action">🖨 Print / Save PDF</button>
    <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $order['id'] ?>" style="color:#cbd5e1;font-size:.85rem;margin-left:12px">← Back to Order</a>
  </div>
</div>
<div class="no-print" style="height:48px"></div>

<div class="page">

  <!-- Header -->
  <div class="excel-header">
    <div class="excel-title-box">SERVICE ORDER FORM (SOF) - NEILOS NETWORK LIMITED</div>
    <div class="excel-logo-box">
      <img src="<?= $logoSrc ?>" alt="Neilos Logo" class="excel-logo-img">
    </div>
  </div>

  <!-- Section 1: Customer & Neilos Information -->
  <div class="sof-sec-block">
    <div class="sec-banner">PARTY INFORMATION</div>
    <div class="grid-2col">
      <!-- Customer Information -->
      <div class="grid-col">
        <div class="col-header">CUSTOMER INFORMATION</div>
        <table class="sof-grid">
          <tr><td class="lbl">Company Name (KYC)</td><td class="val"><?= e(($order['partner_registered_name'] ?? '') ?: ($order['partner_name'] ?? '')) ?></td></tr>
          <tr><td class="lbl">Technical contact Name</td><td class="val"><?= e(($order['auth_signatory_name'] ?? '') ?: '—') ?></td></tr>
          <tr><td class="lbl">Phone</td><td class="val"><?= e(($order['customer_contact_phone'] ?? '') ?: '—') ?></td></tr>
          <tr><td class="lbl">Email</td><td class="val"><?= e(($order['auth_signatory_email'] ?? '') ?: '—') ?></td></tr>
          <tr><td class="lbl">Billing contact Name</td><td class="val"><?= e(($order['finance_contact_name'] ?? '') ?: '—') ?></td></tr>
          <tr><td class="lbl">Phone</td><td class="val"><?= e(($order['customer_contact_phone'] ?? '') ?: '—') ?></td></tr>
          <tr><td class="lbl">Email</td><td class="val"><?= e(($order['billing_email'] ?? '') ?: '—') ?></td></tr>
        </table>
      </div>

      <!-- Neilos Network Limited Information -->
      <div class="grid-col">
        <div class="col-header">NEILOS NETWORK LIMITED INFORMATION</div>
        <table class="sof-grid">
          <tr><td class="lbl">SOF Number</td><td class="val"><?= e($order['order_number']) ?></td></tr>
          <tr><td class="lbl">Technical contact Name</td><td class="val">Neilos Engineering Support</td></tr>
          <tr><td class="lbl">Phone</td><td class="val">+255 700 000 000</td></tr>
          <tr><td class="lbl">Email</td><td class="val">support@neilosnetwork.co.tz</td></tr>
          <tr><td class="lbl">Billing contact Name</td><td class="val">Neilos Finance Team</td></tr>
          <tr><td class="lbl">Phone</td><td class="val">+255 700 000 000</td></tr>
          <tr><td class="lbl">Email</td><td class="val">billing@neilosnetwork.co.tz</td></tr>
        </table>
      </div>
    </div>
  </div>

  <!-- Section 2: Service Request & Site Details -->
  <div class="sof-sec-block">
    <div class="sec-banner">SERVICE REQUEST &amp; SITE DETAILS</div>
    <div class="grid-2col">
      <!-- Service Request -->
      <div class="grid-col">
        <div class="col-header">SERVICE REQUEST</div>
        <table class="sof-grid">
          <tr>
            <td class="lbl">Bandwidth</td>
            <td class="val"><?= e($order['fttx_package'] ?: ($order['aggregate_capacity'] ? $order['aggregate_capacity'].' Mbps' : ($order['bandwidth'] ? $order['bandwidth'].' Mbps' : '—'))) ?></td>
          </tr>
          <tr>
            <td class="lbl">Service Type</td>
            <td class="val" style="padding:2px 6px">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px 4px;font-size:7.8pt">
                <div><span class="chk <?= $isL2 ? 'chk-active' : '' ?>"><?= $isL2 ? '✓' : '' ?></span>Layer 2 Last mile</div>
                <div><span class="chk <?= $isIPT ? 'chk-active' : '' ?>"><?= $isIPT ? '✓' : '' ?></span>IPT</div>
                <div><span class="chk <?= $isDF ? 'chk-active' : '' ?>"><?= $isDF ? '✓' : '' ?></span>Dark Fiber</div>
                <div><span class="chk <?= $isSDWAN ? 'chk-active' : '' ?>"><?= $isSDWAN ? '✓' : '' ?></span>SDWAN</div>
                <div><span class="chk <?= $isWiFi ? 'chk-active' : '' ?>"><?= $isWiFi ? '✓' : '' ?></span>Managed Wi-Fi</div>
                <div><span class="chk <?= $isMPLS ? 'chk-active' : '' ?>"><?= $isMPLS ? '✓' : '' ?></span>MPLS</div>
              </div>
              <div style="margin-top:2px;font-size:8pt;font-weight:700;color:#0F4C81">Selected: <?= e($st) ?></div>
            </td>
          </tr>
          <tr>
            <td class="lbl">Initial Remote hands required</td>
            <td class="val">
              <span class="chk <?= $isRH ? 'chk-active' : '' ?>"><?= $isRH ? '✓' : '' ?></span>YES &nbsp;&nbsp;
              <span class="chk <?= !$isRH ? 'chk-active' : '' ?>"><?= !$isRH ? '✓' : '' ?></span>NO
            </td>
          </tr>
          <tr>
            <td class="lbl">New Cross Connect Required</td>
            <td class="val">
              <span class="chk <?= $isXC ? 'chk-active' : '' ?>"><?= $isXC ? '✓' : '' ?></span>YES &nbsp;&nbsp;
              <span class="chk <?= !$isXC ? 'chk-active' : '' ?>"><?= !$isXC ? '✓' : '' ?></span>NO
            </td>
          </tr>
        </table>
      </div>

      <!-- Site Details -->
      <div class="grid-col">
        <div class="col-header">SITE DETAILS</div>
        <table class="sof-grid">
          <tr><td class="lbl">NNI Port Size (1G, 10G, 40G)</td><td class="val"><?= e($order['nni_location'] ?: 'Standard 1G') ?></td></tr>
          <tr><td class="lbl">A-End Site GPS Coordinates</td><td class="val"><?= e($order['gps_coordinates'] ?: '—') ?></td></tr>
          <tr><td class="lbl">A-End Street name &amp; City</td><td class="val"><?= e($order['customer_location'] ?: '—') ?></td></tr>
          <tr><td class="lbl">B-End Site GPS Coordinates</td><td class="val">Neilos Core POP (-6.7712, 39.2398)</td></tr>
          <tr><td class="lbl">B-end Street name &amp; City</td><td class="val">Dar es Salaam POP</td></tr>
          <tr>
            <td class="lbl">Savanna's tool Feasibility</td>
            <td class="val">
              <span class="chk <?= $isFeasible ? 'chk-active' : '' ?>"><?= $isFeasible ? '✓' : '' ?></span>Feasible &nbsp;&nbsp;
              <span class="chk <?= !$isFeasible ? 'chk-active' : '' ?>"><?= !$isFeasible ? '✓' : '' ?></span>Not-Feasible
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <!-- Section 3: Commercial -->
  <div class="sof-sec-block">
    <div class="sec-banner">COMMERCIAL</div>
    <div class="grid-2col">
      <!-- Monthly Charges -->
      <div class="grid-col">
        <table class="sof-grid">
          <tr><td class="lbl">Billing Cycle</td><td class="val">Monthly</td></tr>
          <tr><td class="lbl">Service Minimum Term (Months)</td><td class="val"><?= e($order['contract_term'] ? preg_replace('/[^0-9]/', '', $order['contract_term']) : '24') ?></td></tr>
          <tr><td class="lbl">Monthly Charges</td><td class="val">TZS <?= money($mrcVal) ?></td></tr>
          <tr><td class="lbl">VAT 18%</td><td class="val">TZS <?= money($vatMrc) ?></td></tr>
          <tr style="background:#f1f5f9"><td class="lbl" style="font-weight:700;color:#0F4C81">Total Monthly Charge</td><td class="val" style="font-weight:700;color:#0F4C81">TZS <?= money($totMrc) ?></td></tr>
        </table>
      </div>

      <!-- Set-Up Charges -->
      <div class="grid-col">
        <table class="sof-grid">
          <tr><td class="lbl">Currency of Payment</td><td class="val">TZS</td></tr>
          <tr><td class="lbl">USD to TZS RATE</td><td class="val">2,670</td></tr>
          <tr><td class="lbl">Set-Up Charges ( NRC )</td><td class="val">TZS <?= money($nrcSub) ?></td></tr>
          <tr><td class="lbl">VAT 18%</td><td class="val">TZS <?= money($vatNrc) ?></td></tr>
          <tr style="background:#f1f5f9"><td class="lbl" style="font-weight:700;color:#0F4C81">Total Once - Off</td><td class="val" style="font-weight:700;color:#0F4C81">TZS <?= money($totNrc) ?></td></tr>
        </table>
      </div>
    </div>
  </div>

  <!-- Section 4: Service Suspension -->
  <div class="sof-sec-block">
    <div class="sec-banner">SERVICE SUSPENSION</div>
    <div class="box-content">
      Neilos may suspend the Service as per the terms defined in the MSA, including immediate suspension for regulatory, compliance, commercial, or network security reasons.
    </div>
  </div>

  <!-- Section 5: Declaration -->
  <div class="sof-sec-block">
    <div class="sec-banner">DECLARATION</div>
    <div class="box-content">
      <p style="font-weight:700;margin-bottom:4px">By signing this Service Order Form, the duly authorized representatives of both parties confirm that:</p>
      <p>1. The information provided in this SOF is accurate and complete.</p>
      <p>2. The commercial charges stated above shall remain valid for the agreed minimum contract term and are subject to the terms and conditions of the applicable MSA.</p>
      <p>3. That this SOF forms part of and is governed by the applicable Master Service Agreement (MSA).</p>
      <p>4. Neilos Network Limited is authorized to provision, install, and activate the services described in this Service Order Form.</p>
      <p>5. Acknowledge that the Service shall be deemed accepted and billing starts automatically 72 hours after handover unless the Partner formally requests suspension within that period.</p>
    </div>
  </div>

  <!-- Section 6: Authorisation & Signatures -->
  <div class="sof-sec-block">
    <div class="sec-banner">AUTHORISATION &amp; SIGNATURES</div>
    <table class="sig-table">
      <tr>
        <!-- Prepared By -->
        <td class="sig-cell">
          <div class="sig-role">Prepared By</div>
          <div class="sig-field"><strong>Name:</strong> <?= e($order['assigned_kam_name'] ?: 'Key Account Manager') ?></div>
          <div class="sig-field"><strong>Title:</strong> Key Account Manager</div>
          <div class="sig-line"></div>
          <div class="sig-sub">Signature &amp; Date</div>
          <div class="sig-sub" style="margin-top:4px">Stamp: __________________</div>
        </td>

        <!-- Purchaser (Customer) -->
        <td class="sig-cell">
          <div class="sig-role">Purchaser (Customer)</div>
          <div class="sig-field"><strong>Name:</strong> <?= e(($order['auth_signatory_name'] ?? '') ?: (($order['partner_registered_name'] ?? '') ?: ($order['partner_name'] ?? ''))) ?></div>
          <div class="sig-field"><strong>Title:</strong> Authorized Signatory</div>
          <div class="sig-line"></div>
          <div class="sig-sub">Signature &amp; Date</div>
          <div class="sig-sub" style="margin-top:4px">Stamp: __________________</div>
        </td>

        <!-- For Neilos Network Limited -->
        <td class="sig-cell">
          <div class="sig-role">For Neilos Network Limited</div>
          <div class="sig-field"><strong>Name:</strong> Director / Authorized Officer</div>
          <div class="sig-field"><strong>Title:</strong> Director / Authorized Officer</div>
          <div class="sig-line"></div>
          <div class="sig-sub">Signature &amp; Date</div>
          <div class="sig-sub" style="margin-top:4px">Stamp: __________________</div>
        </td>
      </tr>
    </table>
  </div>

</div>
</body>
</html>
