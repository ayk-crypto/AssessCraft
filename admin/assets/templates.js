(function () {
  'use strict';

  document.querySelectorAll('.ac-preview-template').forEach(function (button) {
    button.addEventListener('click', function () {
      var dialog = document.getElementById('ac-template-' + button.dataset.template);
      if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
    });
  });

  document.querySelectorAll('.ac-template-dialog').forEach(function (dialog) {
    dialog.querySelectorAll('.ac-dialog-close').forEach(function (button) {
      button.addEventListener('click', function () { dialog.close(); });
    });
    dialog.addEventListener('click', function (event) {
      if (event.target === dialog) dialog.close();
    });
  });
}());
