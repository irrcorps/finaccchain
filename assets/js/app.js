(function () {
  // Mobile sidebar toggle
  var toggleBtn = document.getElementById('facSidebarToggle');
  var sidebar = document.getElementById('facSidebar');
  var backdrop = document.getElementById('facSidebarBackdrop');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
      if (backdrop) backdrop.classList.toggle('show');
    });
  }
  if (backdrop) {
    backdrop.addEventListener('click', function () {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    });
  }

  // Confirmation dialogs for destructive/important actions
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      var msg = el.getAttribute('data-confirm') || 'Yakin ingin melanjutkan aksi ini?';
      if (!window.confirm(msg)) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  });

  // Auto-dismiss toasts
  document.querySelectorAll('.toast').forEach(function (t) {
    if (window.bootstrap) {
      new bootstrap.Toast(t, { delay: 5000 }).show();
    }
  });
})();

function facConfirmSubmit(form, message) {
  if (window.confirm(message)) form.submit();
  return false;
}
