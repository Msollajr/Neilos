// ============================================================
// Neilos Portal — Order Form JS
// FTTx, Layer 2 (last mile), BIA, Remote Hands dynamic pricing
// ============================================================

const VAT = 0.18;

const fttxMrcPrices = {
  'FTTx-40': 27500,
  'FTTx-50': 33000,
  'FTTx-60': 39500,
  'FTTx-70': 46000,
  'FTTx-80': 52500,
  'FTTx-90': 59000,
  'FTTx-100': 65500
};

let currentServiceType = '';
let currentBaseMRC     = 0;
let currentBaseNRC     = 0;
let currentRHNRC       = 0;

function fmt(n, curr = '') {
  const s = Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  return curr ? `${curr} ${s}` : s;
}
function setHid(id, val) { const el = document.getElementById(id); if (el) el.value = val; }
function setTxt(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
function show(id) { const el = document.getElementById(id); if (el) el.style.display = ''; }
function hide(id) { const el = document.getElementById(id); if (el) el.style.display = 'none'; }

function handleServiceType(type) {
  currentServiceType = type;

  // Reset all optional rows
  hide('fttxPackageRow'); hide('diaRow'); hide('nniRow'); hide('l2CapRow');
  hide('contractTermRow'); hide('remoteHandsRow'); hide('otherProductNoteRow');

  currentBaseMRC = 0;
  currentBaseNRC = 0;
  currentRHNRC   = 0;

  if (type === 'FTTH' || type === 'FTTB') {
    show('fttxPackageRow');
    show('contractTermRow');
    show('remoteHandsRow');
    currentBaseNRC = 140000;
  } else if (type === 'Layer 2 ( last mile)' || type === 'Dedicated Layer 2') {
    show('nniRow');
    show('l2CapRow');
    show('contractTermRow');
    show('remoteHandsRow');
    currentBaseNRC = 250000;
  } else if (type === 'Remote Hands Only' || type === 'Remote Hands') {
    show('contractTermRow');
    currentBaseNRC = 80000;
  } else if (type === 'BIA (Broadband Internet Access)' || type === 'DIA') {
    show('diaRow');
    show('contractTermRow');
    show('otherProductNoteRow');
    currentBaseNRC = 0; // Prices inserted by BSA and KAM
  } else if (type) {
    show('contractTermRow');
    show('otherProductNoteRow');
    currentBaseNRC = 0; // Prices inserted by BSA and KAM
  }

  const rhSelect = document.querySelector('[name="remote_hands_required"]');
  const rhVal = rhSelect ? rhSelect.value : '0';
  const rhNRC = (rhVal === '1' || rhVal === 1) ? 80000 : 0;

  setHid('hidMRCCurr', 'TZS');
  updateNRC(currentBaseNRC, rhNRC);
  updateMRC(0);
  if (type) {
    show('commercialSection');
  } else {
    hide('commercialSection');
  }
}

function updateNRC(base, rhNRC) {
  currentBaseNRC = base;
  currentRHNRC   = rhNRC;
  const sub  = base + rhNRC;
  const vat  = sub * VAT;
  const tot  = sub + vat;

  setTxt('baseNRCDisplay', base > 0 ? `TZS ${fmt(base)}` : 'Inserted by BSA');
  setTxt('nrcSubtotal',    sub > 0 ? `TZS ${fmt(sub)}` : (rhNRC > 0 ? `TZS ${fmt(rhNRC)}` : 'Inserted by BSA'));
  setTxt('vatNRC',         sub > 0 ? `TZS ${fmt(vat)}` : (rhNRC > 0 ? `TZS ${fmt(vat)}` : '—'));
  setTxt('totalNRC',       sub > 0 ? `TZS ${fmt(tot)}` : (rhNRC > 0 ? `TZS ${fmt(tot)}` : 'Inserted by BSA'));

  setHid('hidBaseNRC', base.toFixed(2));
  setHid('hidRHNRC', rhNRC.toFixed(2));
  setHid('hidNRCSub', sub.toFixed(2));
  setHid('hidVatNRC', vat.toFixed(2));
  setHid('hidTotalNRC', tot.toFixed(2));

  if (rhNRC > 0) { setTxt('rhNRCDisplay', `TZS ${fmt(rhNRC)}`); show('remoteHandsNRCRow'); }
  else           { hide('remoteHandsNRCRow'); }
}

function updateMRC(baseMRC) {
  currentBaseMRC = baseMRC;
  const vatMRC  = baseMRC * VAT;
  const totMRC  = baseMRC + vatMRC;

  setTxt('baseMRCDisplay', baseMRC > 0 ? `TZS ${fmt(baseMRC)}` : 'Inserted by KAM');
  setTxt('vatMRC',         baseMRC > 0 ? `TZS ${fmt(vatMRC)}` : '—');
  setTxt('totalMRC',       baseMRC > 0 ? `TZS ${fmt(totMRC)}` : 'Inserted by KAM');

  setHid('hidBaseMRC', baseMRC.toFixed(2));
  setHid('hidDiscPct', '0.00');
  setHid('hidDiscAmt', '0.00');
  setHid('hidVatMRC',  vatMRC.toFixed(2));
  setHid('hidTotalMRC',totMRC.toFixed(2));
  hide('discountRow');
}

function updateFTTxPrice(pkg) {
  const mrc = fttxMrcPrices[pkg] || 0;
  updateMRC(mrc);
}

function updateL2Price(cap) {
  let val = 0;
  if (/Gbps|Gb/i.test(cap)) {
    val = (parseFloat(cap) || 0) * 1000;
  } else if (/Mbps|Mb/i.test(cap)) {
    val = parseFloat(cap) || 0;
  } else {
    val = parseFloat(cap) || 0;
  }
  const mrc = (val <= 100 && val > 0) ? 110000 : (val > 100 ? 220000 : 0);
  updateMRC(mrc);
}

function updateRemoteHands(val) {
  const rhNRC = (val === '1' || val === 1) ? 80000 : 0;
  updateNRC(currentBaseNRC, rhNRC);
}

function renderSelectedFiles(input) {
  const container = document.getElementById('filePreviewList');
  if (!container) return;
  container.innerHTML = '';

  if (!input || !input.files || input.files.length === 0) return;

  Array.from(input.files).forEach((file) => {
    const item = document.createElement('div');
    item.style.cssText = 'display:flex;align-items:center;justify-space-between;background:var(--surface-hover);border:1px solid var(--border);padding:8px 14px;border-radius:6px;font-size:.875rem;color:var(--text-primary);';
    
    const sizeStr = (file.size >= 1048576) ? (file.size / 1048576).toFixed(1) + ' MB' : Math.round(file.size / 1024) + ' KB';
    
    item.innerHTML = `
      <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:1.1rem">📄</span>
        <strong>${file.name}</strong>
        <span style="color:var(--text-muted);font-size:.75rem">(${sizeStr})</span>
      </div>
      <span style="color:var(--success);font-weight:600;font-size:.75rem;margin-left:auto">Ready to upload</span>
    `;
    container.appendChild(item);
  });
}

document.addEventListener('DOMContentLoaded', function() {
  const dropZone = document.getElementById('dropZone');
  const fileInput = document.getElementById('fileInput');
  if (!dropZone || !fileInput) return;

  ['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropZone.style.borderColor = 'var(--accent)';
      dropZone.style.background = 'rgba(59, 130, 246, 0.08)';
    }, false);
  });

  ['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropZone.style.borderColor = '';
      dropZone.style.background = '';
    }, false);
  });

  dropZone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    if (dt && dt.files && dt.files.length > 0) {
      fileInput.files = dt.files;
      renderSelectedFiles(fileInput);
    }
  }, false);
});
