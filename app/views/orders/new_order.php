<?php
// New Service Order Form
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">New Service Order</h1>
    <div class="page-subtitle">Complete all required fields for the service order request.</div>
  </div>
</div>

<form method="POST" action="<?= APP_URL ?>/?page=orders&action=create" enctype="multipart/form-data" id="orderForm">
<input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title">Service Order Details</div></div>
  <div class="card-body">

    <!-- Section 1: Partner & KAM -->
    <div class="form-section">
      <div class="form-section-title"><?= svgIcon('building', 16) ?> Partner & KAM</div>
      <div class="form-grid">
        <?php if (!isPartnerUser() && !empty($partners)): ?>
        <div class="form-group">
          <label>Partner <span class="required">*</span></label>
          <select name="partner_id" class="form-control" required>
            <option value="">— Select Partner —</option>
            <?php foreach($partners as $p): ?>
            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="form-group">
          <label>Assigned KAM <span class="required">*</span></label>
          <select name="assigned_kam" class="form-control" required>
            <option value="">— Select KAM —</option>
            <?php foreach($kamList as $k): ?>
            <option value="<?= e($k['full_name']) ?>"><?= e($k['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Section 2: Customer Info -->
    <div class="form-section">
      <div class="form-section-title"><?= svgIcon('users', 16) ?> Customer Information</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Customer Name <span class="required">*</span></label>
          <input type="text" name="customer_name" class="form-control" required placeholder="Full customer name">
        </div>
        <div class="form-group">
          <label>Customer Location <span class="required">*</span></label>
          <input type="text" name="customer_location" class="form-control" required placeholder="Address / area">
        </div>
        <div class="form-group">
          <label>GPS Coordinates</label>
          <input type="text" name="gps_coordinates" class="form-control" placeholder="-6.7924, 39.2083">
        </div>
        <div class="form-group">
          <label>Building Name</label>
          <input type="text" name="building_name" class="form-control" placeholder="Building name">
        </div>
        <div class="form-group">
          <label>Floor Number</label>
          <input type="text" name="floor_number" class="form-control" placeholder="e.g. 3rd Floor">
        </div>
        <div class="form-group">
          <label>Apartment / Unit Number</label>
          <input type="text" name="apartment_number" class="form-control" placeholder="e.g. Apt 12B">
        </div>
        <div class="form-group">
          <label>Contact Name</label>
          <input type="text" name="customer_contact_name" class="form-control" placeholder="Contact person">
        </div>
        <div class="form-group">
          <label>Contact Phone</label>
          <input type="tel" name="customer_contact_phone" class="form-control" placeholder="+255 7xx xxx xxx">
        </div>
        <div class="form-group">
          <label>Contact Email</label>
          <input type="email" name="customer_contact_email" class="form-control" placeholder="customer@example.com">
        </div>
      </div>
    </div>

    <!-- Section 3: Service Type -->
    <div class="form-section">
      <div class="form-section-title"><?= svgIcon('server', 16) ?> Service Details</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Service Type <span class="required">*</span></label>
          <select name="service_type" id="serviceType" class="form-control" required onchange="handleServiceType(this.value)">
            <option value="">— Select Service Type —</option>
            <option value="FTTH">FTTH</option>
            <option value="FTTB">FTTB</option>
            <option value="DIA">DIA (Dedicated Internet Access)</option>
            <option value="Dedicated Layer 2">Dedicated Layer 2</option>
            <option value="Remote Hands Only">Remote Hands Only</option>
          </select>
        </div>

        <!-- FTTH/FTTB package -->
        <div class="form-group" id="fttxPackageRow" style="display:none">
          <label>FTTx Package <span class="required">*</span></label>
          <select name="fttx_package" id="fttxPackage" class="form-control" onchange="updateFTTxPrice(this.value)">
            <option value="">— Select Package —</option>
            <option value="20 Mbps">20 Mbps</option>
            <option value="30 Mbps">30 Mbps</option>
            <option value="40 Mbps">40 Mbps</option>
            <option value="50 Mbps">50 Mbps</option>
            <option value="60 Mbps">60 Mbps</option>
            <option value="80 Mbps">80 Mbps</option>
            <option value="100 Mbps">100 Mbps</option>
          </select>
        </div>

        <!-- DIA bandwidth -->
        <div class="form-group" id="diaRow" style="display:none">
          <label>Bandwidth <span class="required">*</span></label>
          <div class="input-group">
            <input type="number" name="bandwidth" id="bandwidth" class="form-control" placeholder="e.g. 100" min="1">
            <span class="input-addon">Mbps</span>
          </div>
        </div>
        <div class="form-group" id="diaMrcRow" style="display:none">
          <label>DIA MRC (USD) <span class="required">*</span></label>
          <div class="input-group">
            <span class="input-addon">$</span>
            <input type="number" name="dia_mrc" id="diaMrc" class="form-control" placeholder="e.g. 500" min="0" step="0.01" onchange="updateDIA(this.value)">
          </div>
        </div>

        <!-- L2 NNI -->
        <div class="form-group" id="nniRow" style="display:none">
          <label>NNI / Handoff Point <span class="required">*</span></label>
          <input type="text" name="nni_location" class="form-control" placeholder="e.g. Neilos Dar es Salaam POP">
        </div>

        <!-- L2 Aggregate Capacity -->
        <div class="form-group" id="l2CapRow" style="display:none">
          <label>Aggregate Capacity <span class="required">*</span></label>
          <select name="aggregate_capacity" id="aggregateCapacity" class="form-control" onchange="updateL2Price(this.value)">
            <option value="">— Select Capacity —</option>
            <option value="1 Gbps">1 Gbps</option>
            <option value="1.5 Gbps">1.5 Gbps</option>
            <option value="2 Gbps">2 Gbps</option>
            <option value="3 Gbps">3 Gbps</option>
            <option value="4 Gbps">4 Gbps</option>
            <option value="5 Gbps">5 Gbps</option>
            <option value="6 Gbps">6 Gbps</option>
            <option value="7 Gbps">7 Gbps</option>
            <option value="8 Gbps">8 Gbps</option>
            <option value="9 Gbps">9 Gbps</option>
            <option value="10 Gbps">10 Gbps</option>
          </select>
        </div>

        <!-- Contract term -->
        <div class="form-group" id="contractTermRow" style="display:none">
          <label>Committed Minimum Service Term</label>
          <select name="contract_term" id="contractTerm" class="form-control" onchange="updateDiscount(this.value)">
            <option value="">— Select Term —</option>
            <option value="Month to Month">Month to Month (0% discount)</option>
            <option value="12 Months">12 Months (5% discount)</option>
            <option value="24 Months">24 Months (10% discount)</option>
            <option value="36 Months">36 Months (15% discount)</option>
          </select>
        </div>

        <!-- Remote hands check -->
        <div class="form-group" id="remoteHandsRow" style="display:none">
          <label>Remote Hands Required?</label>
          <select name="remote_hands_required" class="form-control" onchange="updateRemoteHands(this.value)">
            <option value="0">No</option>
            <option value="1">Yes (+$30 NRC)</option>
          </select>
        </div>

        <div class="form-group form-col-full">
          <label>Special Requirements</label>
          <textarea name="special_requirements" class="form-control" rows="3" placeholder="Any special requirements, conditions, or notes..."></textarea>
        </div>
      </div>
    </div>

    <!-- Section 4: Commercial Summary -->
    <div class="form-section" id="commercialSection" style="display:none">
      <div class="form-section-title"><?= svgIcon('chart', 16) ?> Commercial Summary</div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px">
        <!-- NRC -->
        <div class="commercial-box">
          <div class="commercial-row section-header">One-Time Charges (NRC)</div>
          <div class="commercial-row">
            <span>Base NRC</span>
            <span class="commercial-value" id="baseNRCDisplay">$60.00</span>
          </div>
          <div class="commercial-row" id="remoteHandsNRCRow" style="display:none">
            <span>Remote Hands NRC</span>
            <span class="commercial-value" id="rhNRCDisplay">$30.00</span>
          </div>
          <div class="commercial-row divider">
            <span>NRC Subtotal</span>
            <span class="commercial-value" id="nrcSubtotal">$60.00</span>
          </div>
          <div class="commercial-row">
            <span>VAT (18%)</span>
            <span class="commercial-value" id="vatNRC">$10.80</span>
          </div>
          <div class="commercial-row total">
            <span>Total NRC incl. VAT</span>
            <span class="commercial-value highlight" id="totalNRC">$70.80</span>
          </div>
          <input type="hidden" name="base_nrc_usd" id="hidBaseNRC" value="60">
          <input type="hidden" name="remote_hands_nrc_usd" id="hidRHNRC" value="0">
          <input type="hidden" name="nrc_subtotal_usd" id="hidNRCSub" value="60">
          <input type="hidden" name="vat_on_nrc" id="hidVatNRC" value="10.80">
          <input type="hidden" name="total_nrc_incl_vat" id="hidTotalNRC" value="70.80">
        </div>

        <!-- MRC -->
        <div class="commercial-box" id="mrcBox">
          <div class="commercial-row section-header">Monthly Charges (MRC)</div>
          <div class="commercial-row">
            <span>USD→TZS Rate</span>
            <span class="commercial-value">2,585 (Fixed)</span>
          </div>
          <div class="commercial-row" id="mrcRow">
            <span id="mrcLabel">Base MRC</span>
            <span class="commercial-value" id="baseMRCDisplay">—</span>
          </div>
          <div class="commercial-row" id="discountRow" style="display:none">
            <span>Discount (<span id="discPct">0</span>%)</span>
            <span class="commercial-value" id="discountDisplay">—</span>
          </div>
          <div class="commercial-row">
            <span>VAT (18%)</span>
            <span class="commercial-value" id="vatMRC">—</span>
          </div>
          <div class="commercial-row total">
            <span>Total MRC incl. VAT</span>
            <span class="commercial-value highlight" id="totalMRC">—</span>
          </div>
          <input type="hidden" name="base_mrc" id="hidBaseMRC" value="0">
          <input type="hidden" name="mrc_currency" id="hidMRCCurr" value="TZS">
          <input type="hidden" name="discount_pct" id="hidDiscPct" value="0">
          <input type="hidden" name="discount_amount" id="hidDiscAmt" value="0">
          <input type="hidden" name="vat_on_mrc" id="hidVatMRC" value="0">
          <input type="hidden" name="total_mrc_incl_vat" id="hidTotalMRC" value="0">
        </div>
      </div>
    </div>

    <!-- Section 5: Documents -->
    <div class="form-section">
      <div class="form-section-title"><?= svgIcon('document', 16) ?> Supporting Documents (Optional)</div>
      <div class="file-input-wrapper" onclick="document.getElementById('fileInput').click()">
        <input type="file" name="documents[]" id="fileInput" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        <div class="file-input-icon"><?= svgIcon('upload', 32) ?></div>
        <div class="file-input-text"><strong>Click to upload</strong> or drag and drop</div>
        <div class="file-input-text" style="font-size:.75rem;margin-top:4px">PDF, DOCX, JPG, PNG — max 10 MB each</div>
      </div>
    </div>

  </div><!-- /.card-body -->
</div><!-- /.card -->

<div style="display:flex;gap:12px;justify-content:flex-end">
  <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary">Cancel</a>
  <button type="submit" class="btn btn-primary">
    <?= svgIcon('check') ?> Submit Service Order
  </button>
</div>

</form>
