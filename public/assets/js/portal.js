// ============================================================
// Neilos Portal — Main JS v2.0 (Premium Dark Edition)
// ============================================================

// ──────────────────────────────────────────────────────────
// Theme System — apply saved theme immediately (before paint)
// ──────────────────────────────────────────────────────────
(function () {
  document.documentElement.setAttribute('data-theme', 'light');
  try { localStorage.setItem('neilos-theme', 'light'); } catch (e) { }
})();

document.addEventListener('DOMContentLoaded', () => {


  // ──────────────────────────────────────────────────────────
  // Scroll Reveal Animations
  // ──────────────────────────────────────────────────────────
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('revealed');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

  // Auto-apply reveal to key elements
  const revealSelectors = [
    '.stat-card',
    '.card',
    '.pipeline-step',
    '.doc-row',
    '.timeline-item'
  ];
  revealSelectors.forEach(selector => {
    document.querySelectorAll(selector).forEach((el, i) => {
      el.classList.add('reveal');
      // Stagger delay for grid items
      if (i < 8) {
        el.style.transitionDelay = `${i * 0.06}s`;
      }
      revealObserver.observe(el);
    });
  });

  // ──────────────────────────────────────────────────────────
  // Tabs
  // ──────────────────────────────────────────────────────────
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.dataset.tabGroup || btn.closest('.tabs')?.dataset.group;
      const target = btn.dataset.tab;
      document.querySelectorAll(`[data-tab-panel][data-tab-group="${group}"]`)
        .forEach(p => p.classList.remove('active'));
      document.querySelectorAll(`[data-tab][data-tab-group="${group}"]`)
        .forEach(b => b.classList.remove('active'));
      document.querySelector(`[data-tab-panel="${target}"][data-tab-group="${group}"]`)
        ?.classList.add('active');
      btn.classList.add('active');
    });
  });

  // ──────────────────────────────────────────────────────────
  // Modals
  // ──────────────────────────────────────────────────────────
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.modalOpen;
      document.getElementById(id)?.classList.add('open');
      document.body.style.overflow = 'hidden';
    });
  });

  function closeModal(backdrop) {
    backdrop?.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('[data-modal-close], .modal-backdrop').forEach(el => {
    el.addEventListener('click', e => {
      if (e.target === el) {
        closeModal(el.closest('.modal-backdrop') || el);
      }
    });
  });

  document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => e.stopPropagation());
  });

  // Close modals on Escape
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-backdrop.open').forEach(bd => closeModal(bd));
      if (sidebar?.classList.contains('open')) closeSidebar();
    }
  });

  // ──────────────────────────────────────────────────────────
  // Auto-dismiss flash alerts (with elegant fade)
  // ──────────────────────────────────────────────────────────
  // ──────────────────────────────────────────────────────────
  // Live 20-Second Countdown & Progress Bar for Flash Alerts
  // ──────────────────────────────────────────────────────────
  document.querySelectorAll('.alert').forEach(alert => {
    let seconds = 20;
    let badgeText = alert.querySelector('.countdown-timer-text');
    let progressBar = alert.querySelector('.notification-progress-bar');

    if (!badgeText) {
      const badgeWrap = document.createElement('span');
      badgeWrap.className = 'notification-countdown-badge';
      badgeWrap.style.cssText = 'font-size:0.78rem;font-weight:700;padding:3px 10px;border-radius:12px;background:rgba(0,0,0,0.08);color:inherit;white-space:nowrap;margin-left:auto;display:inline-flex;align-items:center;gap:4px;';
      badgeWrap.innerHTML = '⏳ <span class="countdown-timer-text">20s</span>';
      alert.appendChild(badgeWrap);
      badgeText = badgeWrap.querySelector('.countdown-timer-text');
    }
    if (!progressBar) {
      progressBar = document.createElement('div');
      progressBar.className = 'notification-progress-bar';
      progressBar.style.cssText = 'position:absolute;bottom:0;left:0;height:4px;width:100%;background:currentColor;opacity:0.4;transition:width 1s linear;';
      alert.style.position = 'relative';
      alert.style.overflow = 'hidden';
      alert.style.paddingBottom = '14px';
      alert.appendChild(progressBar);
    }

    if (badgeText) badgeText.textContent = `${seconds}s`;
    if (progressBar) progressBar.style.width = '100%';

    const timer = setInterval(() => {
      seconds--;
      if (seconds >= 0) {
        if (badgeText) badgeText.textContent = `${seconds}s`;
        if (progressBar) {
          const pct = Math.max(0, (seconds / 20) * 100);
          progressBar.style.width = `${pct}%`;
        }
      }

      if (seconds <= 0) {
        clearInterval(timer);
        alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease, max-height 0.4s ease, padding 0.4s ease, margin 0.4s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-6px)';
        setTimeout(() => {
          alert.style.maxHeight = '0';
          alert.style.padding = '0';
          alert.style.margin = '0';
          alert.style.overflow = 'hidden';
          setTimeout(() => alert.remove(), 400);
        }, 500);
      }
    }, 1000);
  });

  // ──────────────────────────────────────────────────────────
  // Sidebar Toggle (mobile overlay)
  // ──────────────────────────────────────────────────────────
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const toggleBtn = document.getElementById('sidebarToggle');

  function openSidebar() {
    sidebar?.classList.add('open');
    overlay?.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('open');
    document.body.style.overflow = '';
  }

  function toggleSidebarDesktop() {
    if (!sidebar) return;
    if (sidebar.classList.contains('collapsed')) {
      sidebar.classList.remove('collapsed');
      localStorage.setItem('neilos_sidebar_collapsed', '0');
    } else {
      sidebar.classList.add('collapsed');
      localStorage.setItem('neilos_sidebar_collapsed', '1');
    }
  }

  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      if (window.innerWidth > 1024) {
        toggleSidebarDesktop();
      } else {
        sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
      }
    });
  }

  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  // Auto-close drawer on mobile when a navigation link is clicked
  document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 1024) {
        closeSidebar();
      }
    });
  });

  // Double click logo or press Ctrl+B to toggle sidebar collapse
  const logo = document.querySelector('.sidebar-logo');
  if (logo) {
    logo.addEventListener('click', () => {
      if (window.innerWidth > 1024) toggleSidebarDesktop();
    });
  }

  // Keyboard shortcut Ctrl+B / Cmd+B to show/hide navigation menu
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
      e.preventDefault();
      if (window.innerWidth > 1024) {
        toggleSidebarDesktop();
      } else {
        sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
      }
    }
  });

  // Handle resize: close mobile sidebar on desktop
  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      if (window.innerWidth > 1024) closeSidebar();
    }, 200);
  });

  // ──────────────────────────────────────────────────────────
  // Sidebar Scroll Position Lock & Persistence
  // ──────────────────────────────────────────────────────────
  if (sidebar) {
    const savedPos = sessionStorage.getItem('neilos_sidebar_scroll');
    if (savedPos !== null) {
      sidebar.scrollTop = parseInt(savedPos, 10);
    }

    sidebar.addEventListener('scroll', () => {
      sessionStorage.setItem('neilos_sidebar_scroll', sidebar.scrollTop.toString());
    }, { passive: true });

    sidebar.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        sessionStorage.setItem('neilos_sidebar_scroll', sidebar.scrollTop.toString());
      });
    });
  }

  // ────────────────────────────────────────────────────────
  // Topbar blur effect on scroll
  // ────────────────────────────────────────────────────────
  const topbar = document.querySelector('.topbar');
  if (topbar) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 10) {
        topbar.style.borderBottomColor = 'rgba(0,0,0,0.12)';
      } else {
        topbar.style.borderBottomColor = '';
      }
    }, { passive: true });
  }

  // ──────────────────────────────────────────────────────────
  // Active nav link highlight (client-side safety)
  // ──────────────────────────────────────────────────────────
  const currentPath = window.location.search;
  document.querySelectorAll('.nav-link[href]').forEach(link => {
    if (link.href && link.href.includes(window.location.search) && currentPath.length > 1) {
      // PHP already handles this, but this is a safety net
    }
  });

  // ──────────────────────────────────────────────────────────
  // Form input focus enhancement
  // ──────────────────────────────────────────────────────────
  document.querySelectorAll('.form-control').forEach(input => {
    const group = input.closest('.form-group');
    if (!group) return;
    const label = group.querySelector('label');
    if (!label) return;

    input.addEventListener('focus', () => {
      label.style.color = 'var(--accent-light)';
      label.style.transition = 'color 200ms';
    });
    input.addEventListener('blur', () => {
      label.style.color = '';
    });
  });

  // ──────────────────────────────────────────────────────────
  // Smooth stat-card lift on hover (touch-friendly)
  // ──────────────────────────────────────────────────────────
  document.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
      card.style.willChange = 'transform, box-shadow';
    });
    card.addEventListener('mouseleave', () => {
      card.style.willChange = '';
    });
  });

  // ──────────────────────────────────────────────────────────
  // Image lazy-load fade-in
  // ──────────────────────────────────────────────────────────
  document.querySelectorAll('img[loading="lazy"], img:not([loading])').forEach(img => {
    if (!img.complete) {
      img.style.opacity = '0';
      img.style.transition = 'opacity 0.4s ease';
      img.addEventListener('load', () => { img.style.opacity = '1'; });
    }
  });

  // ──────────────────────────────────────────────────────────
  // Stat counter animation
  // ──────────────────────────────────────────────────────────
  function animateCounter(el) {
    const target = parseInt(el.dataset.count, 10);
    if (isNaN(target) || target === 0) { el.textContent = '0'; return; }
    const duration = 900;
    const startTime = performance.now();
    function step(now) {
      const progress = Math.min((now - startTime) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * target).toLocaleString();
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  document.querySelectorAll('[data-count]').forEach(el => animateCounter(el));

  // ──────────────────────────────────────────────────────────
  // Pipeline fill animation
  // ──────────────────────────────────────────────────────────
  setTimeout(() => {
    document.querySelectorAll('.pipeline-step-fill[data-width]').forEach(fill => {
      fill.style.width = fill.dataset.width + '%';
    });
  }, 400);

});

// ──────────────────────────────────────────────────────────
// Global helpers
// ──────────────────────────────────────────────────────────

// ──────────────────────────────────────────────────────────
// Global System Modal Components (Replaces native browser popups)
// ──────────────────────────────────────────────────────────

// Custom Confirmation Modal
function neilosConfirm(message, title = 'Confirm Action', btnText = 'Proceed', btnClass = 'btn-danger', icon = '⚠️') {
  return new Promise((resolve) => {
    const modal = document.getElementById('neilosConfirmModal');
    if (!modal) {
      resolve(true);
      return;
    }

    const titleEl   = document.getElementById('neilosConfirmTitle');
    const msgEl     = document.getElementById('neilosConfirmMessage');
    const iconEl    = document.getElementById('neilosConfirmIcon');
    const closeBtn  = document.getElementById('neilosConfirmClose');
    const cancelBtn = document.getElementById('neilosConfirmCancelBtn');
    const proceedBtn = document.getElementById('neilosConfirmProceedBtn');

    if (titleEl) titleEl.textContent = title;
    if (msgEl) msgEl.textContent = message;
    if (iconEl) iconEl.textContent = icon;
    if (proceedBtn) {
      proceedBtn.textContent = btnText;
      proceedBtn.className = `btn ${btnClass}`;
    }

    function cleanup(result) {
      modal.classList.remove('open');
      document.body.style.overflow = '';
      closeBtn?.removeEventListener('click', onCancel);
      cancelBtn?.removeEventListener('click', onCancel);
      proceedBtn?.removeEventListener('click', onProceed);
      modal.removeEventListener('click', onBackdrop);
      document.removeEventListener('keydown', onKey);
      resolve(result);
    }

    function onCancel() { cleanup(false); }
    function onProceed() { cleanup(true); }
    function onBackdrop(e) { if (e.target === modal) cleanup(false); }
    function onKey(e) { if (e.key === 'Escape') cleanup(false); }

    closeBtn?.addEventListener('click', onCancel);
    cancelBtn?.addEventListener('click', onCancel);
    proceedBtn?.addEventListener('click', onProceed);
    modal.addEventListener('click', onBackdrop);
    document.addEventListener('keydown', onKey);

    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  });
}

// Custom Alert Modal
function neilosAlert(message, title = 'Notification', icon = 'ℹ️') {
  return new Promise((resolve) => {
    const modal = document.getElementById('neilosAlertModal');
    if (!modal) {
      showToast(message, 'info');
      resolve(true);
      return;
    }

    const titleEl  = document.getElementById('neilosAlertTitle');
    const msgEl    = document.getElementById('neilosAlertMessage');
    const iconEl   = document.getElementById('neilosAlertIcon');
    const closeBtn = document.getElementById('neilosAlertClose');
    const okBtn    = document.getElementById('neilosAlertOkBtn');

    if (titleEl) titleEl.textContent = title;
    if (msgEl) msgEl.textContent = message;
    if (iconEl) iconEl.textContent = icon;

    function cleanup() {
      modal.classList.remove('open');
      document.body.style.overflow = '';
      closeBtn?.removeEventListener('click', cleanup);
      okBtn?.removeEventListener('click', cleanup);
      modal.removeEventListener('click', onBackdrop);
      document.removeEventListener('keydown', onKey);
      resolve(true);
    }

    function onBackdrop(e) { if (e.target === modal) cleanup(); }
    function onKey(e) { if (e.key === 'Escape' || e.key === 'Enter') cleanup(); }

    closeBtn?.addEventListener('click', cleanup);
    okBtn?.addEventListener('click', cleanup);
    modal.addEventListener('click', onBackdrop);
    document.addEventListener('keydown', onKey);

    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => okBtn?.focus(), 50);
  });
}

// Custom Prompt Input Modal
function neilosPrompt(message, defaultValue = '', title = 'Input Required') {
  return new Promise((resolve) => {
    const modal = document.getElementById('neilosPromptModal');
    if (!modal) {
      resolve(null);
      return;
    }

    const titleEl   = document.getElementById('neilosPromptTitle');
    const msgEl     = document.getElementById('neilosPromptMessage');
    const inputEl   = document.getElementById('neilosPromptInput');
    const closeBtn  = document.getElementById('neilosPromptClose');
    const cancelBtn = document.getElementById('neilosPromptCancelBtn');
    const submitBtn = document.getElementById('neilosPromptSubmitBtn');

    if (titleEl) titleEl.textContent = title;
    if (msgEl) msgEl.textContent = message;
    if (inputEl) inputEl.value = defaultValue;

    function cleanup(result) {
      modal.classList.remove('open');
      document.body.style.overflow = '';
      closeBtn?.removeEventListener('click', onCancel);
      cancelBtn?.removeEventListener('click', onCancel);
      submitBtn?.removeEventListener('click', onSubmit);
      modal.removeEventListener('click', onBackdrop);
      document.removeEventListener('keydown', onKey);
      resolve(result);
    }

    function onCancel() { cleanup(null); }
    function onSubmit() { cleanup(inputEl ? inputEl.value : ''); }
    function onBackdrop(e) { if (e.target === modal) cleanup(null); }
    function onKey(e) {
      if (e.key === 'Escape') cleanup(null);
      if (e.key === 'Enter') onSubmit();
    }

    closeBtn?.addEventListener('click', onCancel);
    cancelBtn?.addEventListener('click', onCancel);
    submitBtn?.addEventListener('click', onSubmit);
    modal.addEventListener('click', onBackdrop);
    document.addEventListener('keydown', onKey);

    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => inputEl?.focus(), 50);
  });
}

// Override browser native popups globally so "localhost says" dialogs never appear
window.alert = function (msg, title = 'Notification') {
  neilosAlert(msg, title);
};

window.confirm = function (msg) {
  neilosConfirm(msg);
  return false;
};

window.prompt = function (msg, defaultValue = '', title = 'Input Required') {
  neilosPrompt(msg, defaultValue, title);
  return null;
};

function confirmAction(msg) {
  neilosConfirm(msg || 'Are you sure you want to proceed?');
  return false;
}

// Global submit listener for forms with data-confirm attributes
document.addEventListener('submit', function (e) {
  const form = e.target;
  if (!form || !form.dataset) return;

  const confirmMsg = form.dataset.confirm;
  if (confirmMsg && !form.dataset.confirmed) {
    e.preventDefault();
    const title = form.dataset.confirmTitle || 'Confirm Action';
    const btnText = form.dataset.confirmBtn || 'Proceed';
    const btnClass = form.dataset.confirmClass || 'btn-danger';
    const icon = form.dataset.confirmIcon || '⚠️';

    neilosConfirm(confirmMsg, title, btnText, btnClass, icon).then(confirmed => {
      if (confirmed) {
        form.dataset.confirmed = 'true';
        form.submit();
      }
    });
  }
});

// Toast notification (utility for future use — 20-second live countdown default)
function showToast(message, type = 'info', duration = 20000) {
  const container = document.getElementById('toast-container') || (() => {
    const c = document.createElement('div');
    c.id = 'toast-container';
    c.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
    document.body.appendChild(c);
    return c;
  })();

  const colors = {
    success: 'var(--success-light, #d1fae5)',
    danger:  'var(--danger-light, #fee2e2)',
    warning: 'var(--warning-light, #fef3c7)',
    info:    'var(--info-light, #dbeafe)'
  };
  const textColors = {
    success: 'var(--success-text, #065f46)',
    danger:  'var(--danger-text, #991b1b)',
    warning: 'var(--warning-text, #92400e)',
    info:    'var(--info-text, #1e40af)'
  };

  const toast = document.createElement('div');
  toast.style.cssText = `
    background: ${colors[type] || colors.info};
    color: ${textColors[type] || textColors.info};
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: var(--radius-sm, 8px);
    padding: 12px 18px 16px;
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: var(--shadow-md, 0 4px 12px rgba(0,0,0,0.1));
    max-width: 380px;
    opacity: 0;
    transform: translateY(10px) scale(0.97);
    transition: opacity 0.3s ease, transform 0.3s ease;
    backdrop-filter: blur(10px);
    pointer-events: auto;
    position: relative;
    overflow: hidden;
  `;

  // Safe DOM parsing: convert rich-text strings into real DOM nodes without exposing raw HTML tags or innerHTML vulnerability
  const parser = new DOMParser();
  const doc = parser.parseFromString(`<div>${message}</div>`, 'text/html');
  const allowedTags = ['STRONG', 'B', 'EM', 'I', 'SPAN', 'BR'];

  function sanitizeNode(node) {
    if (node.nodeType === Node.TEXT_NODE) {
      return document.createTextNode(node.textContent);
    }
    if (node.nodeType === Node.ELEMENT_NODE) {
      const tagName = node.tagName.toUpperCase();
      if (allowedTags.includes(tagName)) {
        const cleanEl = document.createElement(tagName.toLowerCase());
        node.childNodes.forEach(child => {
          const cleanChild = sanitizeNode(child);
          if (cleanChild) cleanEl.appendChild(cleanChild);
        });
        return cleanEl;
      } else {
        const frag = document.createDocumentFragment();
        node.childNodes.forEach(child => {
          const cleanChild = sanitizeNode(child);
          if (cleanChild) frag.appendChild(cleanChild);
        });
        return frag;
      }
    }
    return null;
  }

  const flexWrapper = document.createElement('div');
  flexWrapper.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;';

  const textWrap = document.createElement('div');
  textWrap.style.cssText = 'flex:1;';
  const cleanContent = sanitizeNode(doc.body.firstChild || doc.body);
  if (cleanContent) {
    textWrap.appendChild(cleanContent);
  } else {
    textWrap.textContent = message;
  }
  flexWrapper.appendChild(textWrap);

  let seconds = Math.round(duration / 1000) || 20;
  const badge = document.createElement('span');
  badge.style.cssText = 'font-size:0.75rem;font-weight:700;padding:3px 8px;border-radius:12px;background:rgba(0,0,0,0.08);white-space:nowrap;flex-shrink:0;display:inline-flex;align-items:center;gap:4px;';
  badge.innerHTML = `⏳ <span>${seconds}s</span>`;
  const timerText = badge.querySelector('span');
  flexWrapper.appendChild(badge);

  toast.appendChild(flexWrapper);

  const progressBar = document.createElement('div');
  progressBar.style.cssText = 'position:absolute;bottom:0;left:0;height:4px;width:100%;background:currentColor;opacity:0.4;transition:width 1s linear;';
  toast.appendChild(progressBar);

  container.appendChild(toast);

  // Animate in
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      toast.style.opacity = '1';
      toast.style.transform = 'translateY(0) scale(1)';
    });
  });

  const timer = setInterval(() => {
    seconds--;
    if (seconds >= 0) {
      if (timerText) timerText.textContent = `${seconds}s`;
      const pct = (seconds / (duration / 1000)) * 100;
      progressBar.style.width = `${pct}%`;
    }

    if (seconds <= 0) {
      clearInterval(timer);
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px) scale(0.97)';
      setTimeout(() => toast.remove(), 300);
    }
  }, 1000);
}

// ──────────────────────────────────────────────────────────
// Account Information Lookup & System Modal Engine
// ──────────────────────────────────────────────────────────
let pendingAccountData = null;

function initAccountLookupSystem() {
  const modal = document.getElementById('neilosAccountInfoModal');
  if (!modal) return;

  const closeBtn = document.getElementById('accountInfoModalClose');
  const cancelBtn = document.getElementById('accountInfoCancelBtn');
  const loadBtn = document.getElementById('accountInfoLoadBtn');

  function closeModal() {
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

  if (loadBtn) {
    loadBtn.addEventListener('click', () => {
      closeModal();
      if (!pendingAccountData) return;

      const data = pendingAccountData;
      let populatedCount = 0;

      for (const [key, val] of Object.entries(data)) {
        if (val === null || val === undefined) continue;

        // Try searching inputs by name or id
        const targets = document.querySelectorAll(`[name="${key}"], [name="kyc[${key}]"], #${key}, [data-field="${key}"]`);
        targets.forEach(input => {
          if (input.type === 'checkbox') {
            input.checked = !!val;
            populatedCount++;
          } else if (input.type === 'radio') {
            if (input.value == val) { input.checked = true; populatedCount++; }
          } else if (input.tagName === 'SELECT') {
            input.value = val;
            populatedCount++;
          } else {
            input.value = val;
            populatedCount++;
          }
        });
      }

      showToast('Account information loaded successfully.', 'success', 20000);
      pendingAccountData = null;
    });
  }

  // Handle dropdown change event on any page for account lookup
  document.addEventListener('change', (e) => {
    const target = e.target;
    if (!target || target.tagName !== 'SELECT') return;

    const name = target.name || '';
    const id = target.id || '';
    const isPartner = name.includes('partner_id') || id.includes('partner_id') || target.dataset.lookupType === 'partner';
    const isContractor = name.includes('contractor') || id.includes('contractor') || target.dataset.lookupType === 'contractor';

    if (!isPartner && !isContractor) return;
    const val = target.value;
    if (!val || val === '0' || val === '') return;

    const lookupType = isContractor ? 'contractor' : 'partner';
    showToast('Loading account information...', 'info', 20000);

    const baseUrl = window.APP_URL || '';
    const apiUrl = `${baseUrl}/?page=api_account_info&id=${encodeURIComponent(val)}&type=${lookupType}`;

    fetch(apiUrl)
      .then(res => res.json())
      .then(res => {
        if (!res.success || !res.data) {
          showToast('Unable to load account information. Please try again.', 'danger', 20000);
          return;
        }

        pendingAccountData = res.data;

        // Render System Modal Content
        const nameEl = document.getElementById('accountInfoName');
        const subTitleEl = document.getElementById('accountInfoSubTitle');
        const descEl = document.getElementById('accountInfoDesc');
        const checklistEl = document.getElementById('accountInfoChecklist');

        if (nameEl) nameEl.textContent = res.account_name || 'Account Information Found';
        if (subTitleEl) subTitleEl.textContent = `Neilos System — ${res.type} Record`;
        if (descEl) descEl.textContent = `The system found existing information for this ${res.type}.`;

        if (checklistEl) {
          checklistEl.innerHTML = '';
          const items = res.checklist || ['Company Details', 'Contact Information', 'Registration Information'];
          items.forEach(item => {
            const row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:8px';
            row.innerHTML = `<span style="color:var(--success);font-weight:700">✓</span> ${item}`;
            checklistEl.appendChild(row);
          });
        }

        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
      })
      .catch(err => {
        showToast('Unable to load account information. Please try again.', 'danger', 20000);
      });
  });
}

// Intercept form submit confirmations using Neilos In-Application System Modal
document.addEventListener('submit', function (e) {
  const form = e.target;
  const confirmMsg = form.dataset.confirm;

  if (confirmMsg && !form.dataset.confirmed) {
    e.preventDefault();
    neilosConfirm(
      confirmMsg,
      form.dataset.confirmTitle || 'Confirm Action',
      form.dataset.confirmBtn || 'Proceed',
      form.dataset.confirmClass || 'btn-danger'
    ).then(confirmed => {
      if (confirmed) {
        form.dataset.confirmed = 'true';
        form.submit();
      }
    });
  }
});

// Auto init on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAccountLookupSystem);
} else {
  initAccountLookupSystem();
}

// System-wide Global File Viewer Modal Handler
function viewSystemFile(fileUrl, fileName, downloadUrl, metadata = {}) {
  const modal = document.getElementById('neilosGlobalFileViewModal');
  const title = document.getElementById('globalFileModalTitle');
  const subtitle = document.getElementById('globalFileModalSubtitle');
  const body = document.getElementById('globalFileModalBody');
  const downloadBtn = document.getElementById('globalFileModalDownloadBtn');

  if (!modal || !body) return;

  if (title) title.textContent = fileName || 'Document Preview';
  if (subtitle) subtitle.textContent = metadata.subtitle || 'In-App Document Preview';
  if (downloadBtn) {
    downloadBtn.href = downloadUrl || fileUrl;
  }

  const ext = (fileName || fileUrl).split('.').pop().toLowerCase();
  const isImg = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);
  const isPdf = (ext === 'pdf');

  let previewHtml = '';
  if (isImg) {
    previewHtml = `<div style="text-align:center;background:#000;border-radius:8px;padding:12px;max-height:450px;overflow:hidden;margin-bottom:16px">
      <img src="${fileUrl}" alt="${fileName}" style="max-height:420px;max-width:100%;object-fit:contain;border-radius:4px">
    </div>`;
  } else if (isPdf) {
    previewHtml = `<div style="margin-bottom:16px">
      <iframe src="${fileUrl}" style="width:100%;height:450px;border:1px solid var(--border);border-radius:8px"></iframe>
    </div>`;
  } else {
    previewHtml = `<div style="display:flex;align-items:center;gap:16px;padding:20px;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;margin-bottom:16px">
      <div style="font-size:2.8rem">📄</div>
      <div>
        <div style="font-weight:700;font-size:1.05rem;color:var(--text-primary);margin-bottom:4px">${fileName}</div>
        <div style="font-size:0.85rem;color:var(--text-muted)">Preview is unavailable for .${ext} files. Click Download below to save the file to your computer.</div>
      </div>
    </div>`;
  }

  let metaHtml = '';
  const metaKeys = Object.keys(metadata);
  if (metaKeys.length > 0) {
    metaHtml = '<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:10px;font-size:0.82rem;background:var(--surface-1);padding:12px 16px;border-radius:6px;border:1px solid var(--border)">';
    if (metadata.uploaded_by) metaHtml += `<div><strong style="color:var(--text-muted)">Uploaded By:</strong> ${metadata.uploaded_by}</div>`;
    if (metadata.uploaded_at) metaHtml += `<div><strong style="color:var(--text-muted)">Upload Date:</strong> ${metadata.uploaded_at}</div>`;
    if (metadata.file_size) metaHtml += `<div><strong style="color:var(--text-muted)">File Size:</strong> ${metadata.file_size}</div>`;
    if (metadata.doc_type) metaHtml += `<div><strong style="color:var(--text-muted)">Document Type:</strong> ${metadata.doc_type}</div>`;
    metaHtml += '</div>';
  }

  body.innerHTML = previewHtml + metaHtml;
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeGlobalFileViewModal() {
  const modal = document.getElementById('neilosGlobalFileViewModal');
  if (modal) {
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }
}

// ============================================================
// Real-Time System Synchronization via Server-Sent Events (SSE)
// ============================================================
function initRealtimeSync() {
  if (!window.EventSource) return;

  const sseUrl = (typeof APP_URL !== 'undefined' ? APP_URL : '') + '/?page=sse';
  let lastEventId = 0;
  
  function connectSSE() {
    const evtSource = new EventSource(sseUrl + (lastEventId ? '?last_id=' + lastEventId : ''));

    evtSource.onmessage = function (e) {
      if (!e.data || e.data.trim() === ': ping') return;
      try {
        const payload = JSON.parse(e.data);
        if (e.lastEventId) lastEventId = e.lastEventId;
        handleSystemRealtimeUpdate(payload);
      } catch (err) {}
    };

    evtSource.addEventListener('system_update', function (e) {
      if (!e.data) return;
      try {
        const payload = JSON.parse(e.data);
        if (e.lastEventId) lastEventId = e.lastEventId;
        handleSystemRealtimeUpdate(payload);
      } catch (err) {}
    });

    evtSource.onerror = function () {
      evtSource.close();
      setTimeout(connectSSE, 4000);
    };
  }

  connectSSE();
}

function handleSystemRealtimeUpdate(payload) {
  if (!payload || !payload.order_id) return;

  // 1. Order Detail Page Updates
  const detailContainer = document.querySelector('[data-order-detail-id]');
  if (detailContainer) {
    const currentOrderId = parseInt(detailContainer.dataset.orderDetailId, 10);
    if (currentOrderId === parseInt(payload.order_id, 10)) {
      const statusBadges = document.querySelectorAll('.order-status-badge, .status-badge-current');
      statusBadges.forEach(badge => {
        if (payload.status) {
          badge.textContent = payload.status;
          badge.className = 'badge order-status-badge ' + getStatusBadgeClass(payload.status);
        }
      });

      if (payload.data && payload.data.status_changed) {
        neilosAlert(`Order #${payload.order_number} status updated to "${payload.status}" automatically.`, 'Real-Time Workflow Update');
      }
    }
  }

  // 2. Dashboard Page Updates
  const dashboardMarker = document.querySelector('.metric-card, .dashboard-grid, #revenueComparisonChart');
  if (dashboardMarker) {
    refreshDashboardMetricsRealtime();
  }
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'Closed': case 'Job Completed': case 'Completed': return 'badge-success';
    case 'Installation': case 'Testing': case 'UAT': return 'badge-info';
    case 'Pending SOF': case 'Management Approval': return 'badge-warning';
    case 'Cancelled': case 'Technically Not Feasible': return 'badge-danger';
    default: return 'badge-secondary';
  }
}

function refreshDashboardMetricsRealtime() {
  fetch((typeof APP_URL !== 'undefined' ? APP_URL : '') + '/?page=api_account_info&action=dashboard_metrics')
    .then(r => r.json())
    .then(res => {
      if (res && res.success && res.metrics) {
        const m = res.metrics;
        const nrcEl = document.getElementById('dashNrcRevenue');
        const mrcEl = document.getElementById('dashMrcRevenue');
        const totEl = document.getElementById('dashTotalRevenue');
        if (nrcEl) nrcEl.textContent = 'TZS ' + formatMoneyNumber(m.total_nrc);
        if (mrcEl) mrcEl.textContent = 'TZS ' + formatMoneyNumber(m.total_mrc);
        if (totEl) totEl.textContent = 'TZS ' + formatMoneyNumber(m.total_revenue);

        Object.keys(m.pipeline || {}).forEach(k => {
          const el = document.getElementById('pipeline_cnt_' + k);
          if (el) el.textContent = m.pipeline[k];
        });
      }
    })
    .catch(() => {});
}

function formatMoneyNumber(num) {
  return parseFloat(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initRealtimeSync);
} else {
  initRealtimeSync();
}
