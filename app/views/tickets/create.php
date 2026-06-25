<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">New Trouble Ticket</div>
    <div class="page-subtitle">Report a fault or issue for an active service</div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=tickets" class="btn btn-secondary">
      <?= svgIcon('list') ?> Back to Tickets
    </a>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form method="POST" action="<?= APP_URL ?>/?page=tickets&action=create" id="ticketForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-section">
        <div class="form-section-title"><?= svgIcon('server') ?> Service Selection</div>
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Active Service <span class="required">*</span></label>
            <select name="active_service_id" id="activeServiceId" class="form-control" required>
              <option value="">— Select Service —</option>
              <?php foreach ($activeServices as $svc): ?>
              <option value="<?= $svc['id'] ?>" data-partner="<?= e($svc['partner_name']) ?>" data-customer="<?= e($svc['customer_name']) ?>" data-type="<?= e($svc['service_type']) ?>" data-circuit="<?= e($svc['circuit_id']) ?>" data-bandwidth="<?= e($svc['bandwidth_capacity']) ?>" data-location="<?= e($svc['location']) ?>">
                <?= e($svc['service_id']) ?> — <?= e($svc['customer_name']) ?> (<?= e($svc['service_type']) ?>)
              </option>
              <?php endforeach; ?>
            </select>
            <div class="form-hint">Select the affected service to auto-populate details</div>
          </div>

          <div class="form-group">
            <label>Partner</label>
            <input type="text" id="svcPartner" class="form-control" readonly placeholder="Auto-populated">
          </div>
          <div class="form-group">
            <label>Customer Name</label>
            <input type="text" id="svcCustomer" class="form-control" readonly placeholder="Auto-populated">
          </div>
          <div class="form-group">
            <label>Service Type</label>
            <input type="text" id="svcType" class="form-control" readonly placeholder="Auto-populated">
          </div>
          <div class="form-group">
            <label>Circuit ID</label>
            <input type="text" id="svcCircuit" class="form-control" readonly placeholder="Auto-populated">
          </div>
          <div class="form-group">
            <label>Bandwidth / Capacity</label>
            <input type="text" id="svcBandwidth" class="form-control" readonly placeholder="Auto-populated">
          </div>
          <div class="form-group">
            <label>Location</label>
            <input type="text" id="svcLocation" class="form-control" readonly placeholder="Auto-populated">
          </div>
          <div class="form-group">
            <label>Assigned KAM</label>
            <input type="text" id="svcKam" class="form-control" readonly placeholder="Auto-populated">
          </div>
          <div class="form-group">
            <label>Activation Date</label>
            <input type="text" id="svcActivation" class="form-control" readonly placeholder="Auto-populated">
          </div>
        </div>
      </div>

      <div class="form-section">
        <div class="form-section-title"><?= svgIcon('alert') ?> Fault Details</div>
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Fault Category <span class="required">*</span></label>
            <select name="fault_category" class="form-control" required>
              <option value="">— Select Fault Category —</option>
              <option value="Network Outage">Network Outage</option>
              <option value="Power Issue">Power Issue</option>
              <option value="Fiber Cut">Fiber Cut</option>
              <option value="High Latency">High Latency</option>
              <option value="Packet Loss">Packet Loss</option>
              <option value="Bandwidth Degradation">Bandwidth Degradation</option>
              <option value="ONU / ONT Fault">ONU / ONT Fault</option>
              <option value="CPE Fault">CPE Fault</option>
              <option value="Configuration Issue">Configuration Issue</option>
              <option value="NNI Issue">NNI Issue</option>
              <option value="IP Transit Issue">IP Transit Issue</option>
              <option value="Peering Issue">Peering Issue</option>
              <option value="Remote Hands Request">Remote Hands Request</option>
              <option value="Service Activation Issue">Service Activation Issue</option>
              <option value="Billing Related">Billing Related</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Severity <span class="required">*</span></label>
            <select name="severity" id="severitySelect" class="form-control" required>
              <option value="">— Select Severity —</option>
              <optgroup label="DIA / Layer 2 / FTTH / FTTB">
                <option value="Sev 1">Sev 1 — Critical</option>
                <option value="Sev 2">Sev 2 — High</option>
                <option value="Sev 3">Sev 3 — Medium</option>
                <option value="Sev 4">Sev 4 — Low</option>
              </optgroup>
              <optgroup label="Remote Hands">
                <option value="Critical">Critical</option>
                <option value="Standard">Standard</option>
                <option value="Planned">Planned</option>
              </optgroup>
            </select>
            <div class="form-hint">Severity determines SLA response and resolution targets</div>
          </div>
          <div class="form-group form-col-full">
            <label>Description <span class="required">*</span></label>
            <textarea name="description" class="form-control" rows="5" required placeholder="Describe the issue in detail — include error messages, affected time, any troubleshooting already performed..."></textarea>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:12px;justify-content:flex-end;padding-top:16px;border-top:1px solid var(--border)">
        <a href="<?= APP_URL ?>/?page=tickets" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg"><?= svgIcon('ticket') ?> Submit Ticket</button>
      </div>
    </form>
  </div>
</div>
