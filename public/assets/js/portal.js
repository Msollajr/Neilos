// ============================================================
// Neilos Portal — Main JS v2.0 (Premium Dark Edition)
// ============================================================

// ──────────────────────────────────────────────────────────
// Theme System — apply saved theme immediately (before paint)
// ──────────────────────────────────────────────────────────
(function() {
  const saved = localStorage.getItem('neilos-theme') || 'light';
  document.documentElement.setAttribute('data-theme', saved);
})();

document.addEventListener('DOMContentLoaded', () => {

  // ──────────────────────────────────────────────────────────
  // Theme Toggle
  // ──────────────────────────────────────────────────────────
  const themeToggleBtn = document.getElementById('themeToggle');
  const root = document.documentElement;

  function getCurrentTheme() {
    return root.getAttribute('data-theme') || 'dark';
  }

  function applyTheme(theme) {
    // Smooth colour transition
    root.style.transition = 'background-color 300ms ease, color 300ms ease';
    root.setAttribute('data-theme', theme);
    localStorage.setItem('neilos-theme', theme);
    root.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    // Remove transition after it completes so it doesn't interfere with hover states
    setTimeout(() => { root.style.transition = ''; }, 350);
  }

  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      const next = getCurrentTheme() === 'dark' ? 'light' : 'dark';
      applyTheme(next);
    });
  }


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
  setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
      a.style.transition = 'opacity 0.5s ease, transform 0.5s ease, max-height 0.4s ease, padding 0.4s ease, margin 0.4s ease';
      a.style.opacity = '0';
      a.style.transform = 'translateY(-6px)';
      setTimeout(() => {
        a.style.maxHeight = '0';
        a.style.padding = '0';
        a.style.margin = '0';
        a.style.overflow = 'hidden';
        setTimeout(() => a.remove(), 400);
      }, 500);
    });
  }, 5000);

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
      const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
      if (window.scrollY > 10) {
        topbar.style.borderBottomColor = isDark ? 'rgba(255,255,255,0.12)' : 'rgba(0,0,0,0.12)';
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

// Confirm helper (preserves existing calls)
function confirmAction(msg) {
  return confirm(msg || 'Are you sure?');
}

// Toast notification (utility for future use)
function showToast(message, type = 'info', duration = 4000) {
  const container = document.getElementById('toast-container') || (() => {
    const c = document.createElement('div');
    c.id = 'toast-container';
    c.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
    document.body.appendChild(c);
    return c;
  })();

  const colors = {
    success: 'var(--success-light)',
    danger:  'var(--danger-light)',
    warning: 'var(--warning-light)',
    info:    'var(--info-light)'
  };
  const textColors = {
    success: 'var(--success-text)',
    danger:  'var(--danger-text)',
    warning: 'var(--warning-text)',
    info:    'var(--info-text)'
  };

  const toast = document.createElement('div');
  toast.style.cssText = `
    background: ${colors[type] || colors.info};
    color: ${textColors[type] || textColors.info};
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: var(--radius-sm);
    padding: 12px 18px;
    font-size: 0.875rem;
    font-weight: 500;
    box-shadow: var(--shadow-md);
    max-width: 320px;
    opacity: 0;
    transform: translateY(10px) scale(0.97);
    transition: opacity 0.3s ease, transform 0.3s ease;
    backdrop-filter: blur(10px);
  `;
  toast.textContent = message;
  container.appendChild(toast);

  // Animate in
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      toast.style.opacity = '1';
      toast.style.transform = 'translateY(0) scale(1)';
    });
  });

  // Auto-remove
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px) scale(0.97)';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}
