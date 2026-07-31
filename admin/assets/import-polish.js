(function () {
  'use strict';

  var form = document.querySelector('.ac-unified-import form');
  if (!form) return;

  var input = form.querySelector('input[type="file"][name="json_file"]');
  if (!input || input.dataset.assesscraftEnhanced === 'true') return;

  var settings = window.assessCraftImport || {};
  var id = input.id || 'assesscraft-json-file';
  var maxBytes = 5 * 1024 * 1024;
  input.id = id;
  input.dataset.assesscraftEnhanced = 'true';
  input.classList.add('ac-json-native-input');
  input.setAttribute('accept', '.json,application/json');

  var style = document.createElement('style');
  style.textContent = [
    '.ac-json-upload-control{position:relative;display:grid;grid-template-columns:auto minmax(0,1fr);gap:8px 14px;align-items:center;margin:12px 0 8px;padding:22px;border:2px dashed #b7bec8;border-radius:12px;background:#f8fafc;transition:border-color .18s ease,background .18s ease,box-shadow .18s ease}',
    '.ac-json-upload-control:hover,.ac-json-upload-control.is-dragover{border-color:#806414;background:#fffaf0;box-shadow:0 0 0 3px rgba(128,100,20,.1)}',
    '.ac-json-native-input{position:absolute!important;width:1px!important;height:1px!important;overflow:hidden!important;clip:rect(0 0 0 0)!important;white-space:nowrap!important}',
    '.ac-json-file-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;padding:0 16px;border:1px solid #806414;border-radius:8px;background:#806414;color:#fff;font-weight:600;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,.08)}',
    '.ac-json-file-button:hover{background:#6d5510;color:#fff}',
    '.ac-json-file-name{min-width:0;color:#50575e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}',
    '.ac-json-upload-hint{grid-column:1/-1;margin:2px 0 0;color:#646970;font-size:12px}',
    '.ac-json-upload-message{grid-column:1/-1;margin:2px 0 0;padding:10px 12px;border-radius:7px;font-size:13px;line-height:1.45}',
    '.ac-json-upload-message.is-error{border:1px solid #f0b7b7;background:#fff1f1;color:#8a2424}',
    '.ac-json-upload-message.is-success{border:1px solid #b8d8bd;background:#eff8f0;color:#285b31}',
    '.ac-json-upload-control.has-error{border-color:#d63638;background:#fff7f7}',
    '.ac-json-upload-control.has-file{border-style:solid;border-color:#6e7f6a;background:#f4f8f3}',
    '@media(max-width:600px){.ac-json-upload-control{grid-template-columns:1fr}.ac-json-file-button{width:100%}.ac-json-file-name{white-space:normal;word-break:break-word}}'
  ].join('');
  document.head.appendChild(style);

  var control = document.createElement('div');
  control.className = 'ac-json-upload-control';
  control.setAttribute('role', 'group');
  control.setAttribute('aria-labelledby', id + '-label');

  var button = document.createElement('label');
  button.className = 'ac-json-file-button';
  button.htmlFor = id;
  button.id = id + '-label';
  button.innerHTML = '<span class="dashicons dashicons-upload" aria-hidden="true"></span><span>' + escapeHtml(settings.chooseFile || 'Choose JSON file') + '</span>';

  var filename = document.createElement('span');
  filename.className = 'ac-json-file-name';
  filename.setAttribute('aria-live', 'polite');
  filename.textContent = settings.noFile || 'No file selected';

  var hint = document.createElement('p');
  hint.className = 'ac-json-upload-hint';
  hint.textContent = settings.dropHint || 'Choose a valid AssessCraft JSON export, or drag and drop it here. Maximum size: 5 MB.';

  var message = document.createElement('div');
  message.className = 'ac-json-upload-message';
  message.setAttribute('role', 'alert');
  message.hidden = true;

  input.parentNode.insertBefore(control, input);
  control.appendChild(input);
  control.appendChild(button);
  control.appendChild(filename);
  control.appendChild(hint);
  control.appendChild(message);

  input.addEventListener('change', function () {
    validateSelection(input.files && input.files.length ? input.files[0] : null);
  });

  ['dragenter', 'dragover'].forEach(function (eventName) {
    control.addEventListener(eventName, function (event) {
      event.preventDefault();
      event.stopPropagation();
      control.classList.add('is-dragover');
    });
  });

  ['dragleave', 'drop'].forEach(function (eventName) {
    control.addEventListener(eventName, function (event) {
      event.preventDefault();
      event.stopPropagation();
      control.classList.remove('is-dragover');
    });
  });

  control.addEventListener('drop', function (event) {
    var file = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files[0] : null;
    if (!file) return;

    try {
      var transfer = new DataTransfer();
      transfer.items.add(file);
      input.files = transfer.files;
    } catch (error) {
      // Some browsers do not permit programmatically assigning FileList.
    }
    validateSelection(file);
  });

  form.addEventListener('submit', function (event) {
    var file = input.files && input.files.length ? input.files[0] : null;
    if (!validateSelection(file)) {
      event.preventDefault();
      control.scrollIntoView({ behavior: 'smooth', block: 'center' });
      button.focus();
    }
  });

  function validateSelection(file) {
    clearMessage();
    control.classList.remove('has-file', 'has-error');

    if (!file) {
      filename.textContent = settings.noFile || 'No file selected';
      showMessage(settings.selectFileError || 'Select an AssessCraft JSON file before importing.', 'error');
      return false;
    }

    filename.textContent = file.name;

    var isJsonName = /\.json$/i.test(file.name || '');
    var isJsonType = !file.type || file.type === 'application/json' || file.type === 'text/json';
    if (!isJsonName || !isJsonType) {
      input.value = '';
      filename.textContent = settings.noFile || 'No file selected';
      showMessage(settings.invalidFileError || 'This file is not a valid JSON file. Please choose an AssessCraft .json export.', 'error');
      return false;
    }

    if (file.size > maxBytes) {
      input.value = '';
      filename.textContent = settings.noFile || 'No file selected';
      showMessage(settings.fileTooLargeError || 'The selected file is larger than 5 MB. Please choose a smaller AssessCraft JSON export.', 'error');
      return false;
    }

    control.classList.add('has-file');
    showMessage((settings.readyMessage || 'Ready to import: %s').replace('%s', file.name), 'success');
    return true;
  }

  function showMessage(text, type) {
    message.textContent = text;
    message.className = 'ac-json-upload-message is-' + type;
    message.hidden = false;
    control.classList.toggle('has-error', type === 'error');
  }

  function clearMessage() {
    message.hidden = true;
    message.textContent = '';
    message.className = 'ac-json-upload-message';
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
}());
