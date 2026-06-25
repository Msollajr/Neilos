// ============================================================
// Neilos Portal — Order Form JS
// FTTx, DIA, L2, Remote Hands dynamic pricing
// ============================================================

const USD_TZS = 2585;
const VAT     = 0.18;
const BASE_NRC = 60;
const RH_NRC   = 30;

const fttxPrices = {
  '20 Mbps': 10.40, '30 Mbps': 12.48, '40 Mbps': 13.52,
  '50 Mbps': 16.22, '60 Mbps': 19.47, '80 Mbps': 23.36, '100 Mbps': 28.04
};

const l2Prices = {
  '1 Gbps': 3000, '1.5 Gbps': 4000, '2 Gbps': 5500, '3 Gbps': 8000,
  '4 Gbps': 10500, '5 Gbps': 13000, '6 Gbps': 15500, '7 Gbps': 18000,
  '8 Gbps': 21000, '9 Gbps': 24000, '10 Gbps': 27000
};

const termDiscounts = {
  'Month to Month': 0, '12 Months': 5, '24 Months': 10, '36 Months': 15
};

let currentServiceType = '';
let currentBaseMRC     = 0;
let currentMRCCurr     = 'TZS';
let currentDiscPct     = 0;

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
  currentDiscPct = 0;

  // Reset all optional rows
  hide('fttxPackageRow'); hide('diaRow'); hide('diaMrcRow'); hide('nniRow'); hide('l2CapRow');
  hide('contractTermRow'); hide('remoteHandsRow');

  const isRemoteHands = type === 'Remote Hands Only';

  if (type === 'FTTH' || type === 'FTTB') {
    show('fttxPackageRow');
    show('remoteHandsRow');
    currentMRCCurr = 'TZS';
  } else if (type === 'DIA') {
    show('diaRow');
    show('diaMrcRow');
    show('contractTermRow');
    show('remoteHandsRow');
    currentMRCCurr = 'USD';
  } else if (type === 'Dedicated Layer 2') {
    show('nniRow');
    show('l2CapRow');
    show('contractTermRow');
    show('remoteHandsRow');
    currentMRCCurr = 'USD';
  }
  // Remote Hands: only NRC

  setHid('hidMRCCurr', currentMRCCurr);
  updateNRC(isRemoteHands ? RH_NRC : BASE_NRC, 0);
  updateMRC(0);
  show('commercialSection');
}

function updateNRC(base, rhNRC) {
  const sub  = base + rhNRC;
  const vat  = sub * VAT;
  const tot  = sub + vat;
  setTxt('baseNRCDisplay', `$${fmt(base)}`);
  setTxt('nrcSubtotal',    `$${fmt(sub)}`);
  setTxt('vatNRC',         `$${fmt(vat)}`);
  setTxt('totalNRC',       `$${fmt(tot)}`);
  setHid('hidBaseNRC', base.toFixed(2));
  setHid('hidRHNRC', rhNRC.toFixed(2));
  setHid('hidNRCSub', sub.toFixed(2));
  setHid('hidVatNRC', vat.toFixed(2));
  setHid('hidTotalNRC', tot.toFixed(2));
  if (rhNRC > 0) { setTxt('rhNRCDisplay', `$${fmt(rhNRC)}`); show('remoteHandsNRCRow'); }
  else           { hide('remoteHandsNRCRow'); }
}

function updateMRC(baseMRC) {
  currentBaseMRC = baseMRC;
  const curr = currentMRCCurr;
  const label = curr === 'TZS' ? 'Base MRC (TZS)' : 'Base MRC (USD)';
  setTxt('mrcLabel', label);

  const discAmt = baseMRC * (currentDiscPct / 100);
  const after   = baseMRC - discAmt;
  const vatMRC  = after * VAT;
  const totMRC  = after + vatMRC;

  setTxt('baseMRCDisplay', baseMRC > 0 ? `${curr} ${fmt(baseMRC)}` : '—');
  setTxt('discountDisplay', discAmt > 0 ? `-${curr} ${fmt(discAmt)}` : '—');
  setTxt('vatMRC',         baseMRC > 0 ? `${curr} ${fmt(vatMRC)}` : '—');
  setTxt('totalMRC',       baseMRC > 0 ? `${curr} ${fmt(totMRC)}` : '—');

  setHid('hidBaseMRC', baseMRC.toFixed(2));
  setHid('hidDiscPct', currentDiscPct.toFixed(2));
  setHid('hidDiscAmt', discAmt.toFixed(2));
  setHid('hidVatMRC',  vatMRC.toFixed(2));
  setHid('hidTotalMRC',totMRC.toFixed(2));

  const discPctEl = document.getElementById('discPct');
  if (discPctEl) discPctEl.textContent = currentDiscPct;

  if (currentDiscPct > 0 && baseMRC > 0) show('discountRow');
  else hide('discountRow');
}

function updateFTTxPrice(pkg) {
  const usd   = fttxPrices[pkg] || 0;
  const tzsMRC = usd * USD_TZS;
  updateMRC(tzsMRC);
}

function updateL2Price(cap) {
  const baseMRC = l2Prices[cap] || 0;
  updateMRC(baseMRC);
}

function updateDiscount(term) {
  const discEl = document.getElementById('discPct');
  currentDiscPct = termDiscounts[term] || 0;
  updateMRC(currentBaseMRC);
}

function updateRemoteHands(val) {
  const isRH    = currentServiceType === 'Remote Hands Only';
  const baseNRC = isRH ? RH_NRC : BASE_NRC;
  const rhNRC   = (!isRH && val === '1') ? RH_NRC : 0;
  updateNRC(baseNRC, rhNRC);
}

function updateDIA(val) {
  const mrc = parseFloat(val) || 0;
  updateMRC(mrc);
}

// Hide DIA MRC row when service type changes away from DIA
function hideDiaMrc() { hide('diaMrcRow'); }
