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

  // Handle dropdown change event ONLY for explicitly designated account lookup elements
  document.addEventListener('change', (e) => {
    const target = e.target;
    if (!target || target.tagName !== 'SELECT') return;

    // Explicitly exclude assignment, order workflow, filter, or marked opt-out selects
    if (target.dataset.noLookup === 'true' || target.closest('[data-no-lookup="true"]') || target.closest('form[action*="assign_contractor"]') || target.closest('form[action*="orders"]')) {
      return;
    }

    // Must be explicitly marked for account lookup modal
    const hasExplicitLookup = target.dataset.accountLookup === 'true' || !!target.dataset.lookupType || target.classList.contains('account-lookup-trigger');
    if (!hasExplicitLookup) return;

    const lookupType = target.dataset.lookupType || (target.name.includes('contractor') ? 'contractor' : 'partner');
    const val = target.value;
    if (!val || val === '0' || val === '') return;

    showToast('Loading account information...', 'info', 4000);

    const baseUrl = window.APP_URL || '';
    const apiUrl = `${baseUrl}/?page=api_account_info&id=${encodeURIComponent(val)}&type=${lookupType}`;

    fetch(apiUrl)
      .then(res => res.json())
      .then(res => {
        if (!res.success || !res.data) {
          showToast('Unable to load account information. Please try again.', 'danger', 4000);
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
        showToast('Unable to load account information. Please try again.', 'danger', 4000);
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

  const baseUrl = (typeof APP_URL !== 'undefined' ? APP_URL : window.location.origin + '/Neilos/public');
  let resolvedViewUrl = fileUrl || downloadUrl || '';
  
  if (resolvedViewUrl && !resolvedViewUrl.startsWith('http://') && !resolvedViewUrl.startsWith('https://') && !resolvedViewUrl.startsWith('blob:') && !resolvedViewUrl.startsWith('data:')) {
    if (resolvedViewUrl.startsWith('/')) {
      resolvedViewUrl = baseUrl + resolvedViewUrl;
    } else {
      resolvedViewUrl = baseUrl + '/' + resolvedViewUrl.replace(/^(\.\/|\/)/, '');
    }
  }

  // If downloadUrl is provided and points to download controller, prefer inline download controller URL
  if (downloadUrl && (downloadUrl.includes('page=download') || downloadUrl.includes('page=orders&action=generate_sof'))) {
    resolvedViewUrl = downloadUrl + (downloadUrl.includes('inline=1') ? '' : '&inline=1');
  } else if (resolvedViewUrl.includes('page=download') && !resolvedViewUrl.includes('inline=1')) {
    resolvedViewUrl += '&inline=1';
  }

  if (title) title.textContent = fileName || 'Document Preview';
  if (subtitle) subtitle.textContent = metadata.subtitle || 'In-App Document Preview';
  if (downloadBtn) {
    downloadBtn.href = downloadUrl || fileUrl;
    downloadBtn.setAttribute('download', fileName || '');
  }

  const ext = (fileName || fileUrl || '').split('?')[0].split('.').pop().toLowerCase();
  const isImg = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);
  const isPdf = (ext === 'pdf');

  let previewHtml = '';
  if (isImg) {
    previewHtml = `<div style="text-align:center;background:var(--surface-1);border:1px solid var(--border);border-radius:8px;padding:16px;max-height:500px;overflow:auto;margin-bottom:16px">
      <img src="${resolvedViewUrl}" alt="${fileName}" style="max-height:460px;max-width:100%;object-fit:contain;border-radius:4px;box-shadow:0 2px 10px rgba(0,0,0,0.1)">
    </div>`;
  } else if (isPdf) {
    previewHtml = `<div style="margin-bottom:16px">
      <div style="display:flex;justify-content:flex-end;margin-bottom:8px">
        <a href="${resolvedViewUrl}" target="_blank" class="btn btn-xs btn-outline-primary" style="display:inline-flex;align-items:center;gap:4px">
          ↗ Open in New Window
        </a>
      </div>
      <iframe src="${resolvedViewUrl}" style="width:100%;height:500px;border:1px solid var(--border);border-radius:8px;background:#fff"></iframe>
    </div>`;
  } else {
    previewHtml = `<div style="display:flex;align-items:center;gap:16px;padding:24px;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;margin-bottom:16px">
      <div style="font-size:3rem">📄</div>
      <div>
        <div style="font-weight:700;font-size:1.05rem;color:var(--text-primary);margin-bottom:4px">${fileName || 'Document File'}</div>
        <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:10px">This document type (.${ext.toUpperCase()}) cannot be embedded directly in the browser. Click below to download and inspect it.</div>
        <a href="${downloadUrl || resolvedViewUrl}" class="btn btn-primary btn-sm" download="${fileName}">
          💾 Download ${fileName}
        </a>
      </div>
    </div>`;
  }

  let metaHtml = '';
  const metaKeys = Object.keys(metadata).filter(k => k !== 'subtitle');
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
    const body = document.getElementById('globalFileModalBody');
    if (body) body.innerHTML = '';
  }
}

function ensureUniversalFilePreviewModal() {
  let modal = document.getElementById('universalFilePreviewModal');
  if (modal) return modal;

  modal = document.createElement('div');
  modal.className = 'modal-backdrop';
  modal.id = 'universalFilePreviewModal';
  modal.style.zIndex = '99999';
  modal.innerHTML = `
    <div class="modal" style="max-width:850px;width:95%;max-height:92vh;display:flex;flex-direction:column;border-radius:var(--radius);overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,0.3)">
      <div class="modal-header" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);background:var(--surface-1)">
        <div style="display:flex;align-items:center;gap:10px;overflow:hidden">
          <span id="ufpModalIcon" style="font-size:1.4rem">📄</span>
          <div style="overflow:hidden">
            <div class="modal-title" id="ufpModalTitle" style="font-size:1rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">File Preview</div>
            <div id="ufpModalSubtitle" style="font-size:0.75rem;color:var(--text-muted)">Staged file · Not submitted yet</div>
          </div>
        </div>
        <button type="button" class="btn btn-icon btn-secondary" onclick="closeUniversalFilePreview()" style="font-size:1.1rem;border:none;background:transparent;cursor:pointer;padding:4px 8px">✕</button>
      </div>
      <div class="modal-body file-preview-modal-body" id="ufpModalBody" style="padding:20px;flex:1;overflow:auto;background:var(--surface-2)">
        <!-- Dynamic Preview Content -->
      </div>
      <div class="modal-footer" style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-top:1px solid var(--border);background:var(--surface-1)">
        <a id="ufpModalDownload" href="#" download="" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px">
          💾 Download / Open File
        </a>
        <button type="button" class="btn btn-primary btn-sm" onclick="closeUniversalFilePreview()">Done Reviewing</button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeUniversalFilePreview();
  });

  return modal;
}

function closeUniversalFilePreview() {
  const modal = document.getElementById('universalFilePreviewModal');
  if (modal) {
    modal.classList.remove('open');
    document.body.style.overflow = '';
    const body = document.getElementById('ufpModalBody');
    if (body) body.innerHTML = '';
  }
}

function openUniversalFilePreview(file) {
  if (!file) return;
  const modal = ensureUniversalFilePreviewModal();
  const titleEl = document.getElementById('ufpModalTitle');
  const subEl = document.getElementById('ufpModalSubtitle');
  const iconEl = document.getElementById('ufpModalIcon');
  const bodyEl = document.getElementById('ufpModalBody');
  const dlEl = document.getElementById('ufpModalDownload');

  const ext = (file.name || '').split('.').pop().toLowerCase();
  const isImg = file.type.startsWith('image/') || ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'].includes(ext);
  const isPdf = file.type === 'application/pdf' || ext === 'pdf';
  const isCsv = ext === 'csv' || file.type === 'text/csv';
  const isText = ['txt', 'log', 'json', 'xml', 'sql', 'md'].includes(ext) || file.type.startsWith('text/');

  titleEl.textContent = file.name;
  subEl.textContent = `${formatUploadedFileSize(file.size)} · ${file.type || ext.toUpperCase()} · Staged file (review before submit)`;
  iconEl.textContent = getUploadedFileIcon(file.name);

  const fileUrl = URL.createObjectURL(file);
  dlEl.href = fileUrl;
  dlEl.download = file.name;
  bodyEl.innerHTML = '';

  if (isImg) {
    const img = document.createElement('img');
    img.src = fileUrl;
    img.className = 'file-preview-image';
    img.alt = file.name;
    bodyEl.appendChild(img);
  } else if (isPdf) {
    const iframe = document.createElement('iframe');
    iframe.src = fileUrl;
    iframe.className = 'file-preview-pdf-frame';
    bodyEl.appendChild(iframe);
  } else if (isCsv) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const text = e.target.result;
      const lines = text.split(/\r\n|\n/).filter(line => line.trim().length > 0);
      if (lines.length === 0) {
        bodyEl.innerHTML = '<div style="padding:30px;text-align:center;color:var(--text-muted)">The CSV file is empty.</div>';
        return;
      }
      
      const rows = lines.slice(0, 100).map(line => {
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

      const headers = rows[0] || [];
      const dataRows = rows.slice(1);

      let html = `
        <div style="width:100%;display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
          <span style="font-weight:600;font-size:0.85rem">Previewing ${dataRows.length} of ${lines.length - 1} records (${headers.length} columns)</span>
          <span class="badge badge-primary" style="font-size:0.75rem">${lines.length - 1} Total Rows</span>
        </div>
        <div class="table-responsive" style="width:100%;max-height:55vh;overflow:auto;border:1px solid var(--border);border-radius:var(--radius-sm)">
          <table class="file-preview-csv-table">
            <thead>
              <tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>
            </thead>
            <tbody>
              ${dataRows.map(r => `<tr>${r.map(c => `<td>${c || '<span style="color:var(--text-muted)">—</span>'}</td>`).join('')}</tr>`).join('')}
            </tbody>
          </table>
        </div>
      `;
      bodyEl.innerHTML = html;
    };
    reader.readAsText(file);
  } else if (isText) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const pre = document.createElement('pre');
      pre.style.cssText = 'width:100%;max-height:55vh;overflow:auto;padding:16px;background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-sm);font-family:monospace;font-size:0.82rem;white-space:pre-wrap';
      pre.textContent = e.target.result;
      bodyEl.appendChild(pre);
    };
    reader.readAsText(file);
  } else {
    bodyEl.innerHTML = `
      <div class="file-preview-doc-box">
        <div class="file-preview-doc-icon">${getUploadedFileIcon(file.name)}</div>
        <div style="font-weight:700;font-size:1.1rem;margin-bottom:6px">${file.name}</div>
        <div class="file-preview-doc-meta">${formatUploadedFileSize(file.size)} · ${file.type || 'Document'}</div>
        <div style="color:var(--text-muted);font-size:0.82rem;max-width:400px;margin:0 auto 20px">
          This file format is ready for submission. Click below to inspect or open it locally with your device application.
        </div>
        <a href="${fileUrl}" download="${file.name}" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px">
          💾 Open / Download File
        </a>
      </div>
    `;
  }

  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
}

window.viewSystemFile = viewSystemFile;
window.openUniversalFilePreview = openUniversalFilePreview;
window.closeGlobalFileViewModal = closeGlobalFileViewModal;
window.closeUniversalFilePreview = closeUniversalFilePreview;

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

// ──────────────────────────────────────────────────────────
// Controlled Currency Input Component (TZS Formatting & Precision)
// ──────────────────────────────────────────────────────────
function initCurrencyInputs() {
  document.querySelectorAll('input.currency-input, input[data-currency-input]').forEach(attachCurrencyBehavior);
}

function attachCurrencyBehavior(input) {
  if (input.dataset.currencyAttached) return;
  input.dataset.currencyAttached = 'true';
  input.setAttribute('inputmode', 'numeric');
  input.setAttribute('autocomplete', 'off');

  // Format initial value if any
  if (input.value && input.value.trim() !== '') {
    formatCurrencyElement(input);
  }

  input.addEventListener('input', function () {
    formatCurrencyElement(input);
  });

  input.addEventListener('paste', function (e) {
    e.preventDefault();
    const pasteText = (e.clipboardData || window.clipboardData).getData('text');
    if (!pasteText) return;
    
    // Extract digits
    const digits = pasteText.replace(/[^\d]/g, '');
    if (!digits) return;
    
    const start = input.selectionStart || 0;
    const end = input.selectionEnd || 0;
    const currentVal = input.value;
    const newVal = currentVal.substring(0, start) + digits + currentVal.substring(end);
    input.value = newVal;
    formatCurrencyElement(input);
  });

  input.addEventListener('keydown', function (e) {
    // Block minus signs, letters, and unwanted special characters
    if (e.key === '-' || e.key === 'e' || e.key === 'E' || e.key === '+') {
      e.preventDefault();
    }
  });

  // Ensure form submit cleans or syncs properly
  const form = input.closest('form');
  if (form && !form.dataset.currencyFormAttached) {
    form.dataset.currencyFormAttached = 'true';
    form.addEventListener('submit', function () {
      form.querySelectorAll('input.currency-input, input[data-currency-input]').forEach(el => {
        if (el.value) {
          el.value = el.value.trim();
        }
      });
    });
  }
}

function formatCurrencyElement(input) {
  const rawVal = input.value;
  if (!rawVal || rawVal.trim() === '') {
    input.value = '';
    return;
  }

  const cursorPosition = input.selectionStart || 0;
  
  // Count how many digits are before the cursor in rawVal
  const textBeforeCursor = rawVal.substring(0, cursorPosition);
  const digitsBeforeCursor = (textBeforeCursor.match(/\d/g) || []).length;
  
  // Extract all digits (rejects negative sign and non-numeric chars)
  let cleanDigits = rawVal.replace(/\D/g, '');
  
  // Remove leading zeros if multi-digit
  if (cleanDigits.length > 1 && cleanDigits.startsWith('0')) {
    cleanDigits = cleanDigits.replace(/^0+/, '');
    if (cleanDigits === '') cleanDigits = '0';
  }
  
  if (cleanDigits === '') {
    input.value = '';
    return;
  }
  
  // Format with thousands separator
  const formatted = cleanDigits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  input.value = formatted;
  
  // Restore cursor position based on digit count
  let newCursorPos = 0;
  let digitsCounted = 0;
  for (let i = 0; i < formatted.length; i++) {
    if (/\d/.test(formatted[i])) {
      digitsCounted++;
    }
    if (digitsCounted === digitsBeforeCursor) {
      newCursorPos = i + 1;
      break;
    }
  }
  if (digitsBeforeCursor === 0) {
    newCursorPos = 0;
  } else if (digitsCounted < digitsBeforeCursor) {
    newCursorPos = formatted.length;
  }
  
  input.setSelectionRange(newCursorPos, newCursorPos);
}

window.initCurrencyInputs = initCurrencyInputs;
window.formatCurrencyElement = formatCurrencyElement;

// ============================================================
// Universal File Input Management (Preview, Replace, Delete)
// ============================================================
function formatUploadedFileSize(bytes) {
  if (!bytes || bytes === 0) return '0 B';
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
  return Math.round(bytes / 1024) + ' KB';
}

function getUploadedFileIcon(fileName) {
  const ext = (fileName || '').split('.').pop().toLowerCase();
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) return '🖼️';
  if (['pdf'].includes(ext)) return '📕';
  if (['doc', 'docx'].includes(ext)) return '📘';
  if (['xls', 'xlsx'].includes(ext)) return '📊';
  if (['csv'].includes(ext)) return '📗';
  if (['zip', 'rar', 'tar', 'gz', '7z'].includes(ext)) return '🗄️';
  if (['txt', 'log', 'json', 'xml'].includes(ext)) return '📄';
  return '📎';
}



// Helper to deduplicate File objects by name, size, and timestamp
function deduplicateFiles(fileList) {
  if (!fileList || fileList.length <= 1) return Array.from(fileList || []);
  const seen = new Set();
  const unique = [];
  for (let i = 0; i < fileList.length; i++) {
    const file = fileList[i];
    const key = `${file.name}_${file.size}_${file.lastModified || 0}`;
    if (!seen.has(key)) {
      seen.add(key);
      unique.push(file);
    }
  }
  return unique;
}

window.deduplicateFiles = deduplicateFiles;

function renderFileInputPreview(input) {
  if (!input || !input.parentElement) return;

  // Deduplicate files in input.files using DataTransfer
  if (input.files && input.files.length > 1) {
    const uniqueList = deduplicateFiles(input.files);
    if (uniqueList.length !== input.files.length) {
      try {
        const dt = new DataTransfer();
        uniqueList.forEach(f => dt.items.add(f));
        input.files = dt.files;
      } catch (err) {}
    }
  }

  // Find or resolve single preview container
  let previewContainer = null;
  if (input.dataset.customPreviewContainer) {
    previewContainer = document.querySelector(input.dataset.customPreviewContainer);
  }
  if (!previewContainer) {
    previewContainer = input.parentElement.querySelector(':scope > .file-selection-preview');
  }

  if (!input.files || input.files.length === 0) {
    if (previewContainer) {
      if (input.dataset.customPreviewContainer) {
        previewContainer.innerHTML = '';
      } else {
        previewContainer.remove();
      }
    }
    return;
  }

  if (!previewContainer) {
    previewContainer = document.createElement('div');
    previewContainer.className = 'file-selection-preview';
    if (input.nextSibling) {
      input.parentNode.insertBefore(previewContainer, input.nextSibling);
    } else {
      input.parentNode.appendChild(previewContainer);
    }
  }

  previewContainer.innerHTML = '';
  const files = deduplicateFiles(input.files);

  files.forEach((file, index) => {
    const item = document.createElement('div');
    item.className = 'file-selection-item';

    const isImage = file.type.startsWith('image/');
    let mediaHtml = `<span class="file-selection-icon">${getUploadedFileIcon(file.name)}</span>`;
    if (isImage) {
      const imgUrl = URL.createObjectURL(file);
      mediaHtml = `<img src="${imgUrl}" class="file-selection-thumb" alt="Preview" style="cursor:pointer">`;
    }

    item.innerHTML = `
      <div class="file-selection-info">
        ${mediaHtml}
        <span class="file-selection-name" title="${file.name}">${file.name}</span>
        <span class="file-selection-size">(${formatUploadedFileSize(file.size)})</span>
      </div>
      <div class="file-selection-actions">
        <span class="badge-uploaded">✓ Uploaded (1)</span>
        <button type="button" class="btn-file-action btn-file-view" title="View / Inspect file">
          👁 View
        </button>
        <button type="button" class="btn-file-action btn-file-replace" title="Select a different file">
          ✎ Replace
        </button>
        <button type="button" class="btn-file-action btn-file-delete" title="Remove this file">
          🗑 Delete
        </button>
      </div>
    `;

    // View button
    const viewBtn = item.querySelector('.btn-file-view');
    viewBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      openUniversalFilePreview(file);
    });

    const thumbImg = item.querySelector('.file-selection-thumb');
    if (thumbImg) {
      thumbImg.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        openUniversalFilePreview(file);
      });
    }

    // Replace button
    const replaceBtn = item.querySelector('.btn-file-replace');
    replaceBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (input.multiple) {
        const tempPicker = document.createElement('input');
        tempPicker.type = 'file';
        tempPicker.accept = input.accept || '*/*';
        tempPicker.onchange = () => {
          if (tempPicker.files && tempPicker.files[0]) {
            try {
              const dt = new DataTransfer();
              for (let i = 0; i < input.files.length; i++) {
                if (i === index) {
                  dt.items.add(tempPicker.files[0]);
                } else {
                  dt.items.add(input.files[i]);
                }
              }
              input.files = dt.files;
            } catch (err) {}
            input.dispatchEvent(new Event('change', { bubbles: true }));
          }
        };
        tempPicker.click();
      } else {
        input.click();
      }
    });

    // Delete button
    const deleteBtn = item.querySelector('.btn-file-delete');
    deleteBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();

      if (input.multiple && files.length > 1) {
        try {
          const dt = new DataTransfer();
          for (let i = 0; i < input.files.length; i++) {
            if (i !== index) dt.items.add(input.files[i]);
          }
          input.files = dt.files;
        } catch (err) {
          input.value = '';
        }
      } else {
        input.value = '';
      }

      input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    previewContainer.appendChild(item);
  });
}

function initGlobalFileInputs(root = document) {
  root.querySelectorAll('input[type="file"]').forEach((input) => {
    if (input.dataset.filePreviewInit) return;
    input.dataset.filePreviewInit = 'true';

    input.addEventListener('change', () => {
      renderFileInputPreview(input);
    });

    if (input.files && input.files.length > 0) {
      renderFileInputPreview(input);
    }
  });
}

window.initGlobalFileInputs = initGlobalFileInputs;
window.renderFileInputPreview = renderFileInputPreview;
window.openUniversalFilePreview = openUniversalFilePreview;
window.closeUniversalFilePreview = closeUniversalFilePreview;

// Auto-observe dynamic DOM for new file inputs
const fileInputObserver = new MutationObserver((mutations) => {
  mutations.forEach((mutation) => {
    mutation.addedNodes.forEach((node) => {
      if (node.nodeType === Node.ELEMENT_NODE) {
        if (node.tagName === 'INPUT' && node.type === 'file') {
          initGlobalFileInputs(node.parentElement || document);
        } else if (node.querySelectorAll) {
          initGlobalFileInputs(node);
        }
      }
    });
  });
});
fileInputObserver.observe(document.body, { childList: true, subtree: true });

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    initRealtimeSync();
    initCurrencyInputs();
    initGlobalFileInputs();
  });
} else {
  initRealtimeSync();
  initCurrencyInputs();
  initGlobalFileInputs();
}

