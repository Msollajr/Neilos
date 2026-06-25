// ============================================================
// Neilos Portal — Main JS
// ============================================================
document.addEventListener('DOMContentLoaded', () => {

  // Tabs
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.dataset.tabGroup || btn.closest('.tabs')?.dataset.group;
      const target = btn.dataset.tab;
      document.querySelectorAll(`[data-tab-panel][data-tab-group="${group}"]`).forEach(p => p.classList.remove('active'));
      document.querySelectorAll(`[data-tab][data-tab-group="${group}"]`).forEach(b => b.classList.remove('active'));
      document.querySelector(`[data-tab-panel="${target}"][data-tab-group="${group}"]`)?.classList.add('active');
      btn.classList.add('active');
    });
  });

  // Modals
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.modalOpen;
      document.getElementById(id)?.classList.add('open');
    });
  });
  document.querySelectorAll('[data-modal-close],.modal-backdrop').forEach(el => {
    el.addEventListener('click', e => {
      if (e.target === el) {
        el.closest('.modal-backdrop')?.classList.remove('open');
      }
    });
  });
  document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => e.stopPropagation());
  });

  // Auto-dismiss flash alerts
  setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
      a.style.transition = 'opacity .4s';
      a.style.opacity = '0';
      setTimeout(() => a.remove(), 400);
    });
  }, 5000);

  // Sidebar toggle (mobile overlay)
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

  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      if (sidebar?.classList.contains('open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });
  }

  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  // Close sidebar on Escape key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && sidebar?.classList.contains('open')) {
      closeSidebar();
    }
  });

  // Sidebar mini-mode toggle (desktop only)
  // Double-click sidebar logo to toggle collapsed mode on desktop
  const logo = document.querySelector('.sidebar-logo');
  if (logo && window.innerWidth > 1024) {
    logo.addEventListener('dblclick', () => {
      sidebar?.classList.toggle('collapsed');
    });
  }

  // Handle resize: close mobile sidebar on resize to desktop
  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      if (window.innerWidth > 1024) {
        closeSidebar();
      }
    }, 200);
  });
});

// Confirm helper
function confirmAction(msg) {
  return confirm(msg || 'Are you sure?');
}
