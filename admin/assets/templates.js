(function () {
  'use strict';

  var grid = document.getElementById('ac-template-grid');
  var search = document.getElementById('ac-template-search');
  var category = document.getElementById('ac-template-category');
  var source = document.getElementById('ac-template-source');
  var resultCount = document.getElementById('ac-template-result-count');
  var pagination = document.getElementById('ac-template-pagination');
  var empty = document.getElementById('ac-template-empty');
  var currentPage = 1;

  function normalized(value) {
    return String(value || '').toLocaleLowerCase().trim();
  }

  function matchingCards() {
    if (!grid) return [];
    var term = normalized(search && search.value);
    var selectedCategory = normalized(category && category.value);
    var selectedSource = normalized(source && source.value);
    return Array.prototype.filter.call(grid.querySelectorAll('.ac-template-card'), function (card) {
      return (!term || normalized(card.dataset.search).indexOf(term) !== -1) &&
        (!selectedCategory || card.dataset.category === selectedCategory) &&
        (!selectedSource || card.dataset.source === selectedSource);
    });
  }

  function pageButton(label, page, disabled, active, ariaLabel) {
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'button' + (active ? ' is-current' : '');
    button.textContent = label;
    button.disabled = disabled;
    if (ariaLabel) button.setAttribute('aria-label', ariaLabel);
    if (active) button.setAttribute('aria-current', 'page');
    button.addEventListener('click', function () { currentPage = page; renderCatalog(); });
    return button;
  }

  function renderPagination(pageCount) {
    if (!pagination) return;
    pagination.innerHTML = '';
    if (pageCount <= 1) {
      pagination.hidden = true;
      return;
    }
    pagination.hidden = false;
    pagination.appendChild(pageButton('‹', Math.max(1, currentPage - 1), currentPage === 1, false, 'Previous template page'));
    for (var page = 1; page <= pageCount; page += 1) {
      if (pageCount > 7 && page > 2 && page < pageCount - 1 && Math.abs(page - currentPage) > 1) {
        if (!pagination.lastElementChild || pagination.lastElementChild.textContent !== '…') {
          var ellipsis = document.createElement('span');
          ellipsis.textContent = '…';
          ellipsis.setAttribute('aria-hidden', 'true');
          pagination.appendChild(ellipsis);
        }
        continue;
      }
      pagination.appendChild(pageButton(String(page), page, false, page === currentPage, 'Template page ' + page));
    }
    pagination.appendChild(pageButton('›', Math.min(pageCount, currentPage + 1), currentPage === pageCount, false, 'Next template page'));
  }

  function renderCatalog() {
    if (!grid) return;
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.ac-template-card'));
    var matches = matchingCards();
    var perPage = Number(grid.dataset.perPage) || 9;
    var pageCount = Math.max(1, Math.ceil(matches.length / perPage));
    currentPage = Math.min(currentPage, pageCount);
    var first = (currentPage - 1) * perPage;
    var visible = matches.slice(first, first + perPage);
    cards.forEach(function (card) { card.hidden = visible.indexOf(card) === -1; });
    if (empty) empty.hidden = matches.length !== 0;
    if (resultCount) {
      resultCount.textContent = matches.length ?
        'Showing ' + (first + 1) + '–' + Math.min(first + perPage, matches.length) + ' of ' + matches.length :
        '0 templates';
    }
    renderPagination(pageCount);
  }

  function filtersChanged() {
    currentPage = 1;
    renderCatalog();
  }

  function resetCatalog() {
    if (search) search.value = '';
    if (category) category.value = '';
    if (source) source.value = '';
    filtersChanged();
    if (search) search.focus();
  }

  if (search) search.addEventListener('input', filtersChanged);
  if (category) category.addEventListener('change', filtersChanged);
  if (source) source.addEventListener('change', filtersChanged);
  document.querySelectorAll('#ac-template-reset, [data-reset-templates]').forEach(function (button) {
    button.addEventListener('click', resetCatalog);
  });
  renderCatalog();

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
