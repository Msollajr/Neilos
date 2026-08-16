<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">FTTH Bulk Upload</div>
    <div class="page-subtitle">Batch create multiple FTTH feasibility orders using a CSV spreadsheet</div>
  </div>
  <div class="page-header-right">
    <a href="<?= APP_URL ?>/?page=ftth_bulk&action=template" class="btn btn-secondary">
      <?= svgIcon('download') ?> Download CSV Template
    </a>
  </div>
</div>

<div class="grid-2col" style="margin-bottom:2rem">
  <!-- Upload Box -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= svgIcon('upload') ?> Upload FTTH Spreadsheet</div>
    </div>
    <div class="card-body">
      <form id="ftthBulkForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-group" id="fileSelectGroup">
          <label class="form-label">Select CSV File <span class="text-danger">*</span></label>
          <input type="file" name="csv_file" id="ftthCsvInput" accept=".csv" class="form-control" required style="padding:10px" onchange="handleFtthCsvSelect(this)">
          <div class="form-hint">Must be a comma-separated .csv file. Max 500 rows per batch.</div>
        </div>

        <!-- Staged CSV Review Container -->
        <div id="stagedCsvReview" style="display:none;margin-top:16px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);padding:16px">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;border-bottom:1px solid var(--border);padding-bottom:12px;margin-bottom:12px">
            <div style="display:flex;align-items:center;gap:10px">
              <span style="font-size:1.6rem">📗</span>
              <div>
                <div id="stagedCsvName" style="font-weight:700;font-size:0.95rem;color:var(--text-primary)">filename.csv</div>
                <div id="stagedCsvMeta" style="font-size:0.75rem;color:var(--text-secondary)">0 KB · 0 records · 0 columns</div>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:6px">
              <span class="badge-uploaded">✓ Staged (1)</span>
              <button type="button" class="btn-file-action btn-file-view" onclick="toggleCsvDataPreview()" id="togglePreviewBtn">
                👁 View Data
              </button>
              <button type="button" class="btn-file-action btn-file-replace" onclick="document.getElementById('ftthCsvInput').click()">
                ✎ Replace
              </button>
              <button type="button" class="btn-file-action btn-file-delete" onclick="deleteFtthStagedCsv()">
                🗑 Delete
              </button>
            </div>
          </div>

          <!-- Staged CSV Validation Status -->
          <div id="stagedCsvValidation" style="margin-bottom:12px;font-size:0.85rem"></div>

          <!-- Interactive Table Preview -->
          <div id="stagedCsvTableWrapper" style="display:none;max-height:260px;overflow:auto;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:14px;background:var(--surface-1)">
            <table class="file-preview-csv-table" id="stagedCsvTable" style="width:100%;margin:0">
              <thead id="stagedCsvThead"></thead>
              <tbody id="stagedCsvTbody"></tbody>
            </table>
          </div>
        </div>

        <div style="margin-top:1.5rem">
          <button type="submit" id="ftthSubmitBtn" class="btn btn-primary btn-block">
            <?= svgIcon('check') ?> Upload &amp; Process Orders
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Instructions & Guidelines -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= svgIcon('info') ?> CSV Columns & Guidelines</div>
    </div>
    <div class="card-body" style="font-size:0.875rem">
      <ul style="padding-left:1.2rem;line-height:1.7;margin:0">
        <li><strong>customer_name</strong> (Mandatory): Customer or building name</li>
        <li><strong>customer_contact_name</strong> (Mandatory): Contact person full name</li>
        <li><strong>customer_contact_phone</strong> (Mandatory): Contact phone number (e.g. +255 712 345 678)</li>
        <li><strong>customer_contact_email</strong> (Mandatory): Valid contact email address</li>
        <li><strong>customer_location</strong> (Mandatory): Physical address, street, or area</li>
        <li><strong>partner_id</strong> (Mandatory): Numerical ID of the ordering partner</li>
        <li><strong>capacity_mbps</strong> (Mandatory): Integer bandwidth (e.g. 10, 20, 50, 100)</li>
        <li><strong>gps_coordinates</strong> (Optional): Lat, Long coordinates (e.g. -6.7835, 39.2685)</li>
        <li><strong>site_category</strong> (Optional): Residential, Commercial, Enterprise</li>
        <li><strong>contract_term_months</strong> (Optional): Default is 12</li>
        <li><strong>kam_name</strong> (Optional): Full name of assigned KAM</li>
        <li><strong>nrc_tzs / mrc_tzs</strong> (Optional): Standard or customized initial amounts in TZS</li>
      </ul>
      <div style="margin-top:12px;padding:10px;background:#eef6ff;border-radius:6px;color:#1e40af;font-size:0.82rem">
        <strong>Tip:</strong> Download the template to get a pre-formatted file with sample data. All created orders will start in <strong>Feasibility Review</strong> status.
      </div>
    </div>
  </div>
</div>

<?php
  $canDelete = !isPartnerUser() && (isAdmin() || hasRole('Management', 'Admin', 'System Admin'));
?>
<!-- Upload History -->
<div class="card" style="box-shadow:var(--shadow-sm);border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);background:var(--surface-2)">
    <div style="display:flex;align-items:center;gap:10px">
      <div style="width:34px;height:34px;border-radius:8px;background:var(--accent-glow);color:var(--accent);display:flex;align-items:center;justify-content:center">
        <?= svgIcon('clock', 18) ?>
      </div>
      <div>
        <div class="card-title" style="font-size:1.05rem;font-weight:700;margin:0">Recent Bulk Upload History</div>
        <div class="card-subtitle" style="font-size:0.8rem;color:var(--text-secondary);margin-top:2px">Audit log and status of previous batch spreadsheet imports</div>
      </div>
    </div>
    <div>
      <span class="badge badge-secondary" style="font-size:0.8rem;padding:6px 10px">
        <?= count($uploads) ?> Total Batches
      </span>
    </div>
  </div>

  <div class="card-body p-0">
    <div class="table-responsive" style="width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch">
      <table class="table" style="width:100%;border-collapse:collapse;margin:0">
        <thead>
          <tr style="background:var(--surface);border-bottom:1px solid var(--border);font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted)">
            <th style="padding:12px 16px;text-align:left;width:60px">#</th>
            <th style="padding:12px 16px;text-align:left">Upload Date &amp; Time</th>
            <th style="padding:12px 16px;text-align:left">Original File</th>
            <th style="padding:12px 16px;text-align:left">Uploaded By</th>
            <th style="padding:12px 16px;text-align:center">Processed Results</th>
            <th style="padding:12px 16px;text-align:left">Created Orders</th>
            <th style="padding:12px 16px;text-align:center">Batch Status</th>
            <th style="padding:12px 16px;text-align:right;min-width:100px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($uploads)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted" style="padding:32px 16px">
                <div style="font-size:2rem;margin-bottom:8px">📂</div>
                <div style="font-weight:600">No bulk uploads recorded yet.</div>
                <div style="font-size:0.82rem;color:var(--text-muted);margin-top:4px">Upload a CSV spreadsheet above to batch create FTTH orders.</div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($uploads as $u): ?>
              <?php
                $tot = (int)$u['total_rows'];
                $succ = (int)$u['success_rows'];
                $err = (int)$u['error_rows'];
                $pct = $tot > 0 ? round(($succ / $tot) * 100) : 0;
                $errList = !empty($u['errors_json']) ? (json_decode($u['errors_json'], true) ?: []) : [];
                $rawOrderIds = array_values(array_filter(array_map('trim', explode(',', $u['created_orders'] ?? ''))));

                $orderItems = [];
                foreach ($rawOrderIds as $oid) {
                    $oidInt = (int)$oid;
                    $orderNum = $ordersMap[$oidInt] ?? '';
                    if (!$orderNum) {
                        $orderNum = 'FR-' . date('ymd', strtotime($u['created_at'])) . '-' . str_pad($oidInt, 3, '0', STR_PAD_LEFT);
                    }
                    // Extract suffix e.g. #002 from FR-260815-002
                    $shortNum = preg_match('/-(\d+)$/', $orderNum, $m) ? ('#' . $m[1]) : ('#' . $orderNum);
                    $orderItems[] = [
                        'id' => $oidInt,
                        'order_number' => $orderNum,
                        'short_number' => $shortNum
                    ];
                }

                $batchData = [
                  'id' => (int)$u['id'],
                  'created_at' => date('d M Y, H:i', strtotime($u['created_at'])),
                  'uploaded_by' => $u['uploaded_by_name'] ?? 'System',
                  'original_file' => $u['original_file'],
                  'total_rows' => $tot,
                  'success_rows' => $succ,
                  'error_rows' => $err,
                  'success_rate' => $pct,
                  'created_orders' => $orderItems,
                  'errors' => $errList
                ];
                $batchDataAttr = htmlspecialchars(json_encode($batchData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
              ?>
              <tr style="border-bottom:1px solid var(--border);transition:background 0.15s ease">
                <td style="padding:14px 16px;font-weight:700;color:var(--text-muted);font-size:0.85rem">
                  #<?= (int)$u['id'] ?>
                </td>
                <td style="padding:14px 16px;white-space:nowrap">
                  <div style="font-weight:600;font-size:0.88rem;color:var(--text-primary)">
                    <?= date('d M Y', strtotime($u['created_at'])) ?>
                  </div>
                  <div style="font-size:0.75rem;color:var(--text-muted)">
                    <?= date('H:i:s', strtotime($u['created_at'])) ?>
                  </div>
                </td>
                <td style="padding:14px 16px">
                  <div style="display:flex;align-items:center;gap:6px">
                    <span style="color:var(--primary);font-size:0.95rem">📄</span>
                    <span style="font-family:monospace;font-size:0.85rem;font-weight:600;color:var(--text-primary);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e($u['original_file']) ?>">
                      <?= e($u['original_file']) ?>
                    </span>
                  </div>
                </td>
                <td style="padding:14px 16px;font-size:0.85rem;color:var(--text-secondary)">
                  <div style="font-weight:600;color:var(--text-primary)"><?= e($u['uploaded_by_name'] ?? 'System') ?></div>
                </td>
                <td style="padding:14px 16px;text-align:center">
                  <div style="display:inline-flex;gap:6px;align-items:center;margin-bottom:4px">
                    <span class="badge badge-success" title="Successful rows" style="font-size:0.75rem;padding:3px 8px">
                      ✓ <?= $succ ?>
                    </span>
                    <?php if ($err > 0): ?>
                    <span class="badge badge-danger" title="Failed rows" style="font-size:0.75rem;padding:3px 8px">
                      ✗ <?= $err ?>
                    </span>
                    <?php endif; ?>
                    <span style="font-size:0.78rem;color:var(--text-muted)">of <?= $tot ?></span>
                  </div>
                  <div style="background:var(--surface-3);border-radius:4px;height:4px;width:90px;margin:0 auto;overflow:hidden">
                    <div style="height:100%;width:<?= $pct ?>%;background:<?= $pct === 100 ? 'var(--success)' : ($pct > 0 ? 'var(--accent)' : 'var(--danger)') ?>"></div>
                  </div>
                </td>
                <td style="padding:14px 16px;max-width:220px">
                  <?php if (!empty($orderItems)): ?>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;align-items:center">
                      <?php 
                        $shown = array_slice($orderItems, 0, 4);
                        foreach ($shown as $ord):
                      ?>
                        <a href="<?= APP_URL ?>/?page=order_detail&id=<?= (int)$ord['id'] ?>" class="badge badge-primary" style="font-size:0.75rem;padding:3px 7px;text-decoration:none;font-weight:600" title="<?= e($ord['order_number']) ?>"><?= e($ord['short_number']) ?></a>
                      <?php endforeach; ?>
                      <?php if (count($orderItems) > 4): ?>
                        <span style="font-size:0.72rem;color:var(--text-muted);font-weight:600">+<?= count($orderItems) - 4 ?> more</span>
                      <?php endif; ?>
                    </div>
                  <?php else: ?>
                    <span style="color:var(--text-muted);font-size:0.82rem">—</span>
                  <?php endif; ?>
                </td>
                <td style="padding:14px 16px;text-align:center">
                  <?php if ($err === 0 && $succ > 0): ?>
                    <span class="badge badge-success" style="font-size:0.75rem;padding:4px 8px">Completed Clean</span>
                  <?php elseif ($succ > 0 && $err > 0): ?>
                    <span class="badge badge-warning" style="font-size:0.75rem;padding:4px 8px">Partial (<?= $err ?> errors)</span>
                  <?php else: ?>
                    <span class="badge badge-danger" style="font-size:0.75rem;padding:4px 8px">Failed (100% errors)</span>
                  <?php endif; ?>
                </td>
                <td style="padding:14px 16px;text-align:right">
                  <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
                    <!-- View Details Button -->
                    <button type="button" class="btn btn-xs btn-outline-primary btn-view-batch" data-batch="<?= $batchDataAttr ?>" onclick="openUploadDetailModal(this)" style="display:inline-flex;align-items:center;gap:4px;padding:4px 8px;font-size:0.75rem" title="View full batch details, created orders, and error log">
                      <?= svgIcon('eye', 13) ?> View
                    </button>

                    <?php if ($canDelete): ?>
                    <!-- Delete Button (Admin / Management only) -->
                    <form method="POST" action="<?= APP_URL ?>/?page=ftth_bulk&action=delete_upload" style="display:inline;margin:0" data-confirm="Delete bulk upload batch #<?= (int)$u['id'] ?> (<?= e($u['original_file']) ?>) and permanently remove all <?= count($orderItems) ?> order(s) created by this upload? This action cannot be undone." data-confirm-title="Delete Batch &amp; Associated Orders?" data-confirm-btn="Delete Batch &amp; Orders" data-confirm-class="btn-danger" data-confirm-icon="🗑️">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="upload_id" value="<?= (int)$u['id'] ?>">
                      <button type="submit" class="btn btn-xs btn-outline-danger" style="display:inline-flex;align-items:center;gap:4px;padding:4px 8px;font-size:0.75rem" title="Delete this upload record and all associated orders">
                        <?= svgIcon('trash', 13) ?> Delete
                      </button>
                    </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Bulk Upload Detail Modal -->
<div class="modal-backdrop" id="bulkUploadModal" style="z-index:99999">
  <div class="modal" style="max-width:640px;width:95%;padding:0;overflow:hidden;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);border:1px solid var(--border);background:var(--surface)">
    
    <!-- Modal Header -->
    <div style="background:var(--accent-pale);padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:38px;height:38px;border-radius:10px;background:var(--accent-glow);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:1.15rem">
          📊
        </div>
        <div>
          <h3 style="margin:0;font-size:1.1rem;font-weight:700;color:var(--text-primary)" id="modalBatchTitle">Batch Upload Details</h3>
          <div style="font-size:0.8rem;color:var(--text-secondary)" id="modalBatchSub">FTTH Bulk Processing Summary</div>
        </div>
      </div>
      <button type="button" onclick="closeUploadDetailModal()" style="background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer;line-height:1">&times;</button>
    </div>

    <!-- Modal Body -->
    <div style="padding:24px;max-height:75vh;overflow-y:auto">
      
      <!-- Quick Info Grid -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(110px, 1fr));gap:10px;margin-bottom:18px;background:var(--surface-2);padding:14px;border-radius:var(--radius-sm);border:1px solid var(--border)">
        <div>
          <div style="font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);font-weight:700">Total Rows</div>
          <div style="font-size:1.15rem;font-weight:700;color:var(--text-primary)" id="modalTotalRows">0</div>
        </div>
        <div>
          <div style="font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);font-weight:700">Successful</div>
          <div style="font-size:1.15rem;font-weight:700;color:var(--success)" id="modalSuccessRows">0</div>
        </div>
        <div>
          <div style="font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);font-weight:700">Failed / Errors</div>
          <div style="font-size:1.15rem;font-weight:700;color:var(--danger)" id="modalErrorRows">0</div>
        </div>
        <div>
          <div style="font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);font-weight:700">Success Rate</div>
          <div style="font-size:1.15rem;font-weight:700;color:var(--primary)" id="modalSuccessRate">100%</div>
        </div>
      </div>

      <!-- File & User info -->
      <div style="font-size:0.86rem;color:var(--text-secondary);margin-bottom:18px;display:flex;flex-direction:column;gap:6px;background:var(--surface-3);padding:12px 14px;border-radius:var(--radius-sm);border:1px solid var(--border)">
        <div><strong>Original File:</strong> <span id="modalFileName" style="font-family:monospace;color:var(--text-primary)">-</span></div>
        <div><strong>Uploaded By:</strong> <span id="modalUploader" style="color:var(--text-primary)">-</span></div>
        <div><strong>Processed On:</strong> <span id="modalDate" style="color:var(--text-primary)">-</span></div>
      </div>

      <!-- Created Orders Section -->
      <div id="modalOrdersContainer" style="margin-bottom:20px">
        <div style="font-size:0.82rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-primary);margin-bottom:8px">
          Created Orders:
        </div>
        <div id="modalOrdersList" style="display:flex;flex-wrap:wrap;gap:8px;padding:12px;background:var(--surface-2);border-radius:var(--radius-sm);border:1px solid var(--border)">
          <!-- Dynamic order badges -->
        </div>
      </div>

      <!-- Errors Section -->
      <div id="modalErrorsContainer" style="display:none">
        <div style="font-size:0.82rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#991B1B;margin-bottom:8px">
          Validation &amp; Processing Errors:
        </div>
        <div id="modalErrorsList" style="display:flex;flex-direction:column;gap:8px;padding:12px;background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-sm);font-size:0.84rem;color:#991B1B">
          <!-- Dynamic error items -->
        </div>
      </div>

    </div>

    <!-- Modal Footer -->
    <div style="background:var(--surface-2);padding:14px 24px;border-top:1px solid var(--border);display:flex;justify-content:<?= $canDelete ? 'space-between' : 'flex-end' ?>;align-items:center">
      <?php if ($canDelete): ?>
      <form id="modalDeleteForm" method="POST" action="<?= APP_URL ?>/?page=ftth_bulk&action=delete_upload" style="margin:0" data-confirm="Are you sure you want to delete this bulk upload batch and permanently remove all its created orders? This action cannot be undone." data-confirm-title="Delete Batch &amp; Associated Orders?" data-confirm-btn="Delete Batch &amp; Orders" data-confirm-class="btn-danger" data-confirm-icon="🗑️">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="upload_id" id="modalDeleteUploadId" value="">
        <button type="submit" class="btn btn-outline-danger" style="font-size:0.82rem;display:inline-flex;align-items:center;gap:6px;padding:6px 12px">
          <?= svgIcon('trash', 14) ?> Delete Batch &amp; Orders
        </button>
      </form>
      <?php endif; ?>
      <button type="button" class="btn btn-secondary" onclick="closeUploadDetailModal()">Close</button>
    </div>
  </div>
</div>

<script>
function openUploadDetailModal(btn) {
  const modal = document.getElementById('bulkUploadModal');
  if (!modal) return;

  let data;
  try {
    if (typeof btn === 'string') {
      data = JSON.parse(btn);
    } else if (btn && btn.dataset && btn.dataset.batch) {
      data = JSON.parse(btn.dataset.batch);
    } else {
      data = btn;
    }
  } catch (e) {
    console.error('Failed to parse batch data:', e);
    return;
  }

  if (!data) return;

  const deleteUploadInput = document.getElementById('modalDeleteUploadId');
  if (deleteUploadInput) {
    deleteUploadInput.value = data.id;
  }

  const batchTitle = document.getElementById('modalBatchTitle');
  if (batchTitle) batchTitle.textContent = `Batch #${data.id} — ${data.original_file}`;
  
  const batchSub = document.getElementById('modalBatchSub');
  if (batchSub) batchSub.textContent = `Processed on ${data.created_at}`;

  const totalRows = document.getElementById('modalTotalRows');
  if (totalRows) totalRows.textContent = data.total_rows;

  const successRows = document.getElementById('modalSuccessRows');
  if (successRows) successRows.textContent = data.success_rows;

  const errorRows = document.getElementById('modalErrorRows');
  if (errorRows) errorRows.textContent = data.error_rows;

  const successRate = document.getElementById('modalSuccessRate');
  if (successRate) successRate.textContent = `${data.success_rate}%`;

  const fileName = document.getElementById('modalFileName');
  if (fileName) fileName.textContent = data.original_file;

  const uploader = document.getElementById('modalUploader');
  if (uploader) uploader.textContent = data.uploaded_by;

  const dateElem = document.getElementById('modalDate');
  if (dateElem) dateElem.textContent = data.created_at;

  // Render created orders
  const ordersContainer = document.getElementById('modalOrdersContainer');
  const ordersList = document.getElementById('modalOrdersList');
  if (data.created_orders && data.created_orders.length > 0) {
    ordersContainer.style.display = 'block';
    ordersList.innerHTML = '';
    data.created_orders.forEach(ord => {
      const a = document.createElement('a');
      const orderId = typeof ord === 'object' ? ord.id : ord;
      const orderNum = typeof ord === 'object' ? (ord.order_number || ord.short_number) : `Order #${ord}`;
      const shortNum = typeof ord === 'object' && ord.short_number ? ord.short_number : '';
      
      a.href = `<?= APP_URL ?>/?page=order_detail&id=${orderId}`;
      a.className = 'badge badge-primary';
      a.style.cssText = 'padding:6px 12px;font-size:0.84rem;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:6px';
      a.innerHTML = `<span>${orderNum}</span>` + (shortNum && shortNum !== orderNum ? `<span style="opacity:0.8;font-size:0.75rem">(${shortNum})</span>` : '');
      ordersList.appendChild(a);
    });
  } else {
    ordersContainer.style.display = 'none';
  }

  // Render errors
  const errorsContainer = document.getElementById('modalErrorsContainer');
  const errorsList = document.getElementById('modalErrorsList');
  if (data.errors && data.errors.length > 0) {
    errorsContainer.style.display = 'block';
    errorsList.innerHTML = '';
    data.errors.forEach(err => {
      const div = document.createElement('div');
      div.style.cssText = 'padding:8px 12px;background:#fff;border:1px solid #fca5a5;border-radius:4px;font-family:monospace;font-size:0.82rem;word-break:break-word';
      div.textContent = err;
      errorsList.appendChild(div);
    });
  } else {
    errorsContainer.style.display = 'none';
  }

  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeUploadDetailModal() {
  const modal = document.getElementById('bulkUploadModal');
  if (modal) {
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }
}

// Close on backdrop click
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('bulkUploadModal');
  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeUploadDetailModal();
    });
  }
});

// ============================================================
// Staged CSV Upload Review & Validation
// ============================================================
let stagedCsvRows = [];
let stagedCsvHeaders = [];

function handleFtthCsvSelect(input) {
  const stagedBox = document.getElementById('stagedCsvReview');
  const nameEl = document.getElementById('stagedCsvName');
  const metaEl = document.getElementById('stagedCsvMeta');
  const valEl = document.getElementById('stagedCsvValidation');
  const submitBtn = document.getElementById('ftthSubmitBtn');
  const tableWrapper = document.getElementById('stagedCsvTableWrapper');

  if (!input.files || !input.files[0]) {
    if (stagedBox) stagedBox.style.display = 'none';
    if (tableWrapper) tableWrapper.style.display = 'none';
    return;
  }

  const file = input.files[0];
  if (nameEl) nameEl.textContent = file.name;
  
  const sizeStr = (file.size >= 1048576) ? (file.size / 1048576).toFixed(1) + ' MB' : Math.round(file.size / 1024) + ' KB';

  const reader = new FileReader();
  reader.onload = function(e) {
    const text = e.target.result;
    const lines = text.split(/\r\n|\n/).filter(l => l.trim().length > 0);
    if (lines.length < 2) {
      if (metaEl) metaEl.textContent = `${sizeStr} · 0 records`;
      if (valEl) valEl.innerHTML = '<div class="alert alert-danger" style="padding:8px 12px;margin:0;font-size:0.8rem">⚠️ CSV is empty or has no data rows.</div>';
      if (submitBtn) submitBtn.disabled = true;
      if (stagedBox) stagedBox.style.display = 'block';
      return;
    }

    const rows = lines.map(line => {
      const row = [];
      let inQuotes = false;
      let curVal = '';
      for (let i = 0; i < line.length; i++) {
        const c = line[i];
        if (c === '"') { inQuotes = !inQuotes; }
        else if (c === ',' && !inQuotes) { row.push(curVal.trim()); curVal = ''; }
        else { curVal += c; }
      }
      row.push(curVal.trim());
      return row;
    });

    stagedCsvHeaders = rows[0].map(h => h.toLowerCase().trim().replace(/^[\uFEFF\xEF\xBB\xBF]+/, ''));
    stagedCsvRows = rows.slice(1);

    if (metaEl) metaEl.textContent = `${sizeStr} · ${stagedCsvRows.length} records · ${stagedCsvHeaders.length} columns`;

    // Validate mandatory headers
    const requiredCols = ['customer_name', 'customer_contact_name', 'customer_contact_phone', 'customer_contact_email', 'customer_location', 'partner_id', 'capacity_mbps'];
    const missingCols = requiredCols.filter(c => !stagedCsvHeaders.includes(c));

    if (missingCols.length > 0) {
      if (valEl) valEl.innerHTML = `<div class="alert alert-danger" style="padding:8px 12px;margin:0;font-size:0.8rem"><strong>Missing Required Columns:</strong> ${missingCols.join(', ')}</div>`;
      if (submitBtn) submitBtn.disabled = true;
    } else {
      if (valEl) valEl.innerHTML = `<div class="alert alert-success" style="padding:8px 12px;margin:0;font-size:0.8rem">✓ All required headers verified · <strong>${stagedCsvRows.length}</strong> orders ready to import.</div>`;
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span>Upload &amp; Process ${stagedCsvRows.length} Orders</span>`;
      }
    }

    // Render Preview Table
    const thead = document.getElementById('stagedCsvThead');
    const tbody = document.getElementById('stagedCsvTbody');
    if (thead) thead.innerHTML = `<tr>${rows[0].map(h => `<th>${h}</th>`).join('')}</tr>`;
    if (tbody) tbody.innerHTML = stagedCsvRows.slice(0, 25).map(r => `<tr>${r.map(c => `<td>${c || '<span style="color:var(--text-muted)">—</span>'}</td>`).join('')}</tr>`).join('');

    if (stagedBox) stagedBox.style.display = 'block';
  };
  reader.readAsText(file);
}

function toggleCsvDataPreview() {
  const tableWrapper = document.getElementById('stagedCsvTableWrapper');
  const btn = document.getElementById('togglePreviewBtn');
  if (!tableWrapper) return;
  if (tableWrapper.style.display === 'none' || !tableWrapper.style.display) {
    tableWrapper.style.display = 'block';
    if (btn) btn.textContent = 'Hide Data';
  } else {
    tableWrapper.style.display = 'none';
    if (btn) btn.textContent = '👁️ View Data';
  }
}

function deleteFtthStagedCsv() {
  const input = document.getElementById('ftthCsvInput');
  if (input) {
    input.value = '';
    input.dispatchEvent(new Event('change'));
  }
  const stagedBox = document.getElementById('stagedCsvReview');
  if (stagedBox) stagedBox.style.display = 'none';
  const tableWrapper = document.getElementById('stagedCsvTableWrapper');
  if (tableWrapper) tableWrapper.style.display = 'none';
  const submitBtn = document.getElementById('ftthSubmitBtn');
  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.innerHTML = `Upload &amp; Process Orders`;
  }
}
</script>
