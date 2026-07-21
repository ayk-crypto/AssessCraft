(function () {
  'use strict';

  function mount(root) {
    var payload;
    try {
      payload = JSON.parse(root.getAttribute('data-assessment') || '{}');
    } catch (error) {
      root.textContent = 'This assessment could not be loaded.';
      return;
    }

    var config = payload.config || {};
    var overview = config.overview || {};
    var title = overview.heading || payload.title || 'Assessment';
    var description = overview.description || '';
    var startLabel = overview.start_label || 'Begin Assessment';

    root.innerHTML = '';
    var card = document.createElement('section');
    card.className = 'assesscraft-intro';

    var heading = document.createElement('h2');
    heading.textContent = title;
    card.appendChild(heading);

    if (description) {
      var copy = document.createElement('p');
      copy.textContent = description;
      card.appendChild(copy);
    }

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'assesscraft-button';
    button.textContent = startLabel;
    button.addEventListener('click', function () {
      root.dispatchEvent(new CustomEvent('assesscraft:start', { detail: payload }));
      if (!(config.stages || []).length) {
        button.textContent = 'Assessment builder coming next';
        button.disabled = true;
      }
    });
    card.appendChild(button);
    root.appendChild(card);
  }

  document.querySelectorAll('.assesscraft-app').forEach(mount);
}());

