// ============================================================
// Neilos Portal — Ticket Form JS
// Service auto-populate and severity guidance
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

  const svcSelect = document.getElementById('activeServiceId');
  if (!svcSelect) return;

  function populateServiceFields() {
    const opt = svcSelect.options[svcSelect.selectedIndex];
    const fields = {
      svcPartner:    opt?.dataset?.partner,
      svcCustomer:   opt?.dataset?.customer,
      svcType:       opt?.dataset?.type,
      svcCircuit:    opt?.dataset?.circuit,
      svcBandwidth:  opt?.dataset?.bandwidth,
      svcLocation:   opt?.dataset?.location,
      svcKam:        opt?.dataset?.kam,
      svcActivation: opt?.dataset?.activation,
    };

    for (const [id, val] of Object.entries(fields)) {
      const el = document.getElementById(id);
      if (el) el.value = val || '';
    }

    // Update severity guidance based on service type
    const type = opt?.dataset?.type || '';
    updateSeverityOptions(type);
  }

  function updateSeverityOptions(type) {
    const sevSelect = document.getElementById('severitySelect');
    if (!sevSelect) return;

    // Keep the same value if possible
    const currentVal = sevSelect.value;

    // Show/hide optgroups based on service type
    const groups = sevSelect.querySelectorAll('optgroup');
    groups.forEach(g => {
      if (type === 'Remote Hands Only') {
        g.style.display = g.label.includes('Remote Hands') ? '' : 'none';
      } else {
        g.style.display = g.label.includes('Remote Hands') ? 'none' : '';
      }
    });

    // If current value is not in visible options, reset
    const visibleOptions = Array.from(sevSelect.options).filter(o => o.style.display !== 'none' && !o.disabled);
    const stillValid = visibleOptions.some(o => o.value === currentVal);
    if (!stillValid) {
      sevSelect.value = '';
    }
  }

  // Initial setup
  updateSeverityOptions('');

  svcSelect.addEventListener('change', populateServiceFields);

  // Fetch KAM and activation date from server
  svcSelect.addEventListener('change', function() {
    const svcId = this.value;
    if (!svcId) return;

    fetch(APP_URL + '/?page=tickets&action=get_service&service_id=' + svcId)
      .then(r => r.json())
      .then(data => {
        if (data.error) return;
        const kamEl = document.getElementById('svcKam');
        if (kamEl && data.kam_name) kamEl.value = data.kam_name;
        const actEl = document.getElementById('svcActivation');
        if (actEl && data.activation_date) actEl.value = data.activation_date;
      })
      .catch(() => {});
  });
});

// Make APP_URL available for fetch (from the base URL)
if (typeof APP_URL === 'undefined') {
  var APP_URL = window.location.origin + '/Neilos/public';
}
