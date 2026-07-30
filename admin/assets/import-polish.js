(function () {
  'use strict';

  var form = document.querySelector('.ac-unified-import form');
  if (!form) return;

  var input = form.querySelector('input[type="file"][name="json_file"]');
  if (!input || input.dataset.assesscraftEnhanced === 'true') return;

  var settings = window.assessCraftImport || {};
  var id = input.id || 'assesscraft-json-file';
  input.id = id;
  input.dataset.assesscraftEnhanced = 'true';
  input.classList.add('ac-json-native-input');

  var control = document.createElement('div');
  control.className = 'ac-json-upload-control';

  var button = document.createElement('label');
  button.className = 'ac-json-file-button';
  button.htmlFor = id;
  button.innerHTML = '<span class="dashicons dashicons-upload" aria-hidden="true"></span><span>' + escapeHtml(settings.chooseFile || 'Choose JSON file') + '</span>';

  var filename = document.createElement('span');
  filename.className = 'ac-json-file-name';
  filename.setAttribute('aria-live', 'polite');
  filename.textContent = settings.noFile || 'No file selected';

  input.parentNode.insertBefore(control, input);
  control.appendChild(input);
  control.appendChild(button);
  control.appendChild(filename);

  input.addEventListener('change', function () {
    var selected = input.files && input.files.length ? input.files[0].name : '';
    filename.textContent = selected || settings.noFile || 'No file selected';
    control.classList.toggle('has-file', Boolean(selected));
  });

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
}());
