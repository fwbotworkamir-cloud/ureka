(function () {
  'use strict';

  var all = document.querySelector('[data-uipp-select-all]');
  if (all) {
    all.addEventListener('change', function () {
      document.querySelectorAll('input[name="ids[]"]').forEach(function (box) {
        box.checked = all.checked;
      });
    });
  }

  document.querySelectorAll('[data-uipp-confirm]').forEach(function (link) {
    link.addEventListener('click', function (event) {
      if (!window.confirm(link.getAttribute('data-uipp-confirm'))) {
        event.preventDefault();
      }
    });
  });

  var bulkForm = document.querySelector('form[action*="admin-post.php"] input[value="ureka_ipp_bulk"]');
  if (bulkForm) {
    bulkForm.form.addEventListener('submit', function (event) {
      var action = bulkForm.form.querySelector('[name="bulk_action"]').value;
      var selected = bulkForm.form.querySelectorAll('input[name="ids[]"]:checked');
      if (!action || !selected.length) {
        event.preventDefault();
        window.alert('Choose an action and at least one submission.');
        return;
      }
      if (action === 'delete' && !window.confirm('Permanently delete the selected submissions? This cannot be undone.')) {
        event.preventDefault();
      }
    });
  }
}());
