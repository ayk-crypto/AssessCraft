(function () {
  'use strict';

  var root = document.getElementById('assesscraft-admin');
  if (!root) return;

  var stageList = document.getElementById('ac-stage-list');
  var emptyState = document.getElementById('ac-empty-builder');
  var jsonField = document.getElementById('assesscraft-stages-json');
  var scoringField = document.getElementById('assesscraft-scoring-json');
  var profilesField = document.getElementById('assesscraft-profiles-json');
  var bandList = document.getElementById('ac-band-list');
  var profileList = document.getElementById('ac-profile-list');
  var emptyProfiles = document.getElementById('ac-empty-profiles');
  var profileLimitNotice = document.getElementById('ac-profile-limit-notice');
  var settings = window.assessCraftAdmin || { questionTypes: {}, i18n: {} };
  var features = settings.features || { profileLimit: -1, weighted: true, reverseScoring: true };
  var state = {
    stages: parseStages(jsonField.value),
    scoring: parseObject(scoringField.value, { method: 'weighted_percentage', bands: [] }),
    profiles: parseStages(profilesField.value)
  };

  function parseStages(value) {
    try {
      var stages = JSON.parse(value || '[]');
      return Array.isArray(stages) ? stages : [];
    } catch (error) {
      return [];
    }
  }

  function parseObject(value, fallback) {
    try {
      var parsed = JSON.parse(value || '{}');
      return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : fallback;
    } catch (error) {
      return fallback;
    }
  }

  function id(prefix) {
    if (window.crypto && window.crypto.randomUUID) return prefix + '_' + window.crypto.randomUUID().replace(/-/g, '');
    return prefix + '_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 10);
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function formatMessage(template, values) {
    var index = 0;
    return String(template || '').replace(/%(?:(\d+)\$)?d/g, function (match, position) {
      var valueIndex = position ? Number(position) - 1 : index++;
      return values[valueIndex];
    });
  }

  function updateProfileLimitState() {
    if (features.profileLimit < 0) return;
    var used = state.profiles.length;
    var atLimit = used >= features.profileLimit;
    var addButton = document.getElementById('ac-add-profile');
    if (addButton) {
      addButton.classList.toggle('is-limit-reached', atLimit);
      if (atLimit) addButton.setAttribute('aria-describedby', 'ac-profile-limit-description');
      else addButton.removeAttribute('aria-describedby');
    }
    if (!profileLimitNotice) return;
    if (!atLimit) {
      profileLimitNotice.hidden = true;
      return;
    }
    profileLimitNotice.querySelector('#ac-profile-limit-title').textContent =
      formatMessage(settings.i18n.profileLimit, [features.profileLimit]);
    profileLimitNotice.querySelector('#ac-profile-limit-description').textContent =
      settings.i18n.profileLimitHelp || '';
    profileLimitNotice.querySelector('#ac-profile-limit-usage').textContent =
      formatMessage(settings.i18n.profileLimitUsed, [used, features.profileLimit]);
  }

  function defaultAnswers(type) {
    if (type === 'yes_no') return [answer('Yes', 1), answer('No', 0)];
    if (type === 'numeric') return [1, 2, 3, 4, 5].map(function (score) { return answer(String(score), score); });
    return [
      answer('Strongly Disagree', 1), answer('Disagree', 2), answer('Neutral or Unsure', 3),
      answer('Agree', 4), answer('Strongly Agree', 5)
    ];
  }

  function answer(label, score) { return { id: id('answer'), label: label, score: score }; }
  function question() {
    return { id: id('question'), type: 'scale', prompt: '', required: true, reverse: false, answers: defaultAnswers('scale') };
  }
  function stage() { return { id: id('stage'), name: '', description: '', weight: 1, questions: [] }; }

  function sync() {
    jsonField.value = JSON.stringify(state.stages);
    scoringField.value = JSON.stringify(state.scoring);
    profilesField.value = JSON.stringify(state.profiles);
  }

  function render() {
    stageList.innerHTML = '';
    state.stages.forEach(function (item, stageIndex) {
      stageList.appendChild(renderStage(item, stageIndex));
    });
    emptyState.hidden = state.stages.length > 0;
    renderBands();
    renderProfiles();
    updateProfileLimitState();
    var questionCount = state.stages.reduce(function (total, item) { return total + (item.questions || []).length; }, 0);
    var statusValues = { stages: state.stages.length, questions: questionCount, profiles: state.profiles.length };
    Object.keys(statusValues).forEach(function (key) { var node = root.querySelector('[data-status="' + key + '"]'); if (node) node.textContent = statusValues[key]; });
    sync();
  }

  function renderBands() {
    bandList.innerHTML = '';
    (state.scoring.bands || []).forEach(function (band, bandIndex) {
      var row = document.createElement('article');
      row.className = 'ac-band';
      row.innerHTML =
        '<header class="ac-band-header"><div><span class="ac-band-index">Band ' + (bandIndex + 1) + '</span><strong>' + escapeHtml(band.label || 'Untitled classification') + '</strong></div><button type="button" class="ac-icon-delete ac-delete-band" aria-label="Delete score band"><span class="dashicons dashicons-trash"></span></button></header>' +
        '<div class="ac-band-body">' +
          '<div class="ac-band-color"><span>Band color</span><div><i class="ac-band-swatch" style="background:' + escapeHtml(band.color || '#6E7F6A') + '" aria-hidden="true"></i><input class="ac-band-color-code" value="' + escapeHtml((band.color || '#6E7F6A').toUpperCase()) + '" maxlength="7" spellcheck="false" aria-label="Hex color code"></div></div>' +
          '<label class="ac-field ac-band-name"><span>Classification</span><input class="ac-band-label" value="' + escapeHtml(band.label || '') + '" placeholder="e.g. Strong"></label>' +
          '<div class="ac-band-range"><span>Score range</span><div><label><span class="screen-reader-text">Minimum score</span><input class="ac-band-min" type="number" min="0" max="100" step="0.01" value="' + escapeHtml(band.min == null ? 0 : band.min) + '" aria-label="Minimum score"></label><span class="ac-range-separator">to</span><label><span class="screen-reader-text">Maximum score</span><input class="ac-band-max" type="number" min="0" max="100" step="0.01" value="' + escapeHtml(band.max == null ? 100 : band.max) + '" aria-label="Maximum score"></label></div></div>' +
          '<label class="ac-field ac-band-interpretation"><span>Interpretation shown in report</span><textarea rows="3" placeholder="Explain what this classification means and what the respondent should understand.">' + escapeHtml(band.interpretation || '') + '</textarea></label>' +
        '</div>';
      var swatch = row.querySelector('.ac-band-swatch');
      var colorCode = row.querySelector('.ac-band-color-code');
      colorCode.addEventListener('input', function (event) {
        var value = event.target.value.trim().toUpperCase();
        if (/^#[0-9A-F]{6}$/.test(value)) { band.color = value; swatch.style.background = value; event.target.classList.remove('is-invalid'); sync(); }
        else { event.target.classList.add('is-invalid'); }
      });
      colorCode.addEventListener('blur', function () { colorCode.value = (band.color || '#6E7F6A').toUpperCase(); colorCode.classList.remove('is-invalid'); });
      row.querySelector('.ac-band-label').addEventListener('input', function (event) { band.label = event.target.value; row.querySelector('.ac-band-header strong').textContent = band.label || 'Untitled classification'; sync(); });
      row.querySelector('.ac-band-min').addEventListener('input', function (event) { band.min = Number(event.target.value); sync(); });
      row.querySelector('.ac-band-max').addEventListener('input', function (event) { band.max = Number(event.target.value); sync(); });
      row.querySelector('textarea').addEventListener('input', function (event) { band.interpretation = event.target.value; sync(); });
      row.querySelector('.ac-delete-band').addEventListener('click', function () { if (window.confirm(settings.i18n.confirmDelete)) { state.scoring.bands.splice(bandIndex, 1); render(); } });
      bandList.appendChild(row);
    });
  }

  function metricOptions(selected) {
    var options = [{ value: 'overall', label: 'Overall score' }];
    state.stages.forEach(function (stage, index) { options.push({ value: 'stage_' + stage.id, label: 'Stage: ' + (stage.name || 'Stage ' + (index + 1)) }); });
    return options.map(function (item) { return '<option value="' + escapeHtml(item.value) + '"' + (selected === item.value ? ' selected' : '') + '>' + escapeHtml(item.label) + '</option>'; }).join('');
  }

  function renderProfiles() {
    profileList.innerHTML = '';
    state.profiles.sort(function (a, b) { return (Number(b.priority) || 0) - (Number(a.priority) || 0); });
    state.profiles.forEach(function (profile, profileIndex) {
      var card = document.createElement('article');
      card.className = 'ac-profile';
      card.innerHTML =
        '<header class="ac-profile-header"><div><span>Result profile</span><strong>' + escapeHtml(profile.title || 'Untitled profile') + '</strong></div><button type="button" class="button-link-delete ac-delete-profile">Delete</button></header>' +
        '<div class="ac-profile-body"><div class="ac-form-grid">' +
          '<label class="ac-field"><span>Profile title</span><input class="ac-profile-title" value="' + escapeHtml(profile.title || '') + '"></label>' +
          '<label class="ac-field"><span>Priority</span><input class="ac-profile-priority" type="number" value="' + escapeHtml(profile.priority || 0) + '"></label>' +
          '<label class="ac-field ac-field-wide"><span>Description</span><textarea class="ac-profile-description" rows="3">' + escapeHtml(profile.description || '') + '</textarea></label>' +
          '<label class="ac-field ac-field-wide"><span>Recommendation</span><textarea class="ac-profile-recommendation" rows="3">' + escapeHtml(profile.recommendation || '') + '</textarea></label>' +
          '<label class="ac-field"><span>Condition matching</span><select class="ac-profile-match"><option value="all"' + (profile.match !== 'any' ? ' selected' : '') + '>Match all conditions</option><option value="any"' + (profile.match === 'any' ? ' selected' : '') + '>Match any condition</option></select></label>' +
        '</div><div class="ac-condition-list"></div><button type="button" class="button-link ac-add-condition">+ Add condition</button></div>';
      var conditionList = card.querySelector('.ac-condition-list');
      (profile.conditions || []).forEach(function (condition, conditionIndex) { conditionList.appendChild(renderCondition(condition, profile, conditionIndex)); });
      card.querySelector('.ac-profile-title').addEventListener('input', function (event) { profile.title = event.target.value; card.querySelector('.ac-profile-header strong').textContent = profile.title || 'Untitled profile'; sync(); });
      card.querySelector('.ac-profile-priority').addEventListener('input', function (event) { profile.priority = Number(event.target.value) || 0; sync(); });
      card.querySelector('.ac-profile-description').addEventListener('input', function (event) { profile.description = event.target.value; sync(); });
      card.querySelector('.ac-profile-recommendation').addEventListener('input', function (event) { profile.recommendation = event.target.value; sync(); });
      card.querySelector('.ac-profile-match').addEventListener('change', function (event) { profile.match = event.target.value; sync(); });
      card.querySelector('.ac-add-condition').addEventListener('click', function () { profile.conditions = profile.conditions || []; profile.conditions.push({ metric: 'overall', operator: 'gte', value: 70, value2: 100 }); render(); });
      card.querySelector('.ac-delete-profile').addEventListener('click', function () { if (window.confirm(settings.i18n.confirmDelete)) { state.profiles.splice(profileIndex, 1); render(); } });
	  if (features.profileLimit >= 0 && profileIndex >= features.profileLimit) {
		card.classList.add('ac-pro-locked');
		card.querySelectorAll('input, textarea, select, button').forEach(function (control) { control.disabled = true; });
	  }
      profileList.appendChild(card);
    });
    emptyProfiles.hidden = state.profiles.length > 0;
  }

  function renderCondition(condition, profile, conditionIndex) {
    var row = document.createElement('div');
    row.className = 'ac-condition';
    row.innerHTML =
      '<select class="ac-condition-metric" aria-label="Score to evaluate">' + metricOptions(condition.metric) + '</select>' +
      '<select class="ac-condition-operator" aria-label="Comparison"><option value="gte">is at least</option><option value="lte">is at most</option><option value="gt">is greater than</option><option value="lt">is less than</option><option value="between">is between</option></select>' +
      '<input class="ac-condition-value" type="number" min="0" max="100" step="0.01" value="' + escapeHtml(condition.value == null ? 0 : condition.value) + '" aria-label="Value">' +
      '<input class="ac-condition-value2" type="number" min="0" max="100" step="0.01" value="' + escapeHtml(condition.value2 == null ? 100 : condition.value2) + '" aria-label="Second value">' +
      '<button type="button" class="button-link-delete" aria-label="Delete condition">&times;</button>';
    row.querySelector('.ac-condition-operator').value = condition.operator || 'gte';
    row.classList.toggle('is-between', condition.operator === 'between');
    row.querySelector('.ac-condition-metric').addEventListener('change', function (event) { condition.metric = event.target.value; sync(); });
    row.querySelector('.ac-condition-operator').addEventListener('change', function (event) { condition.operator = event.target.value; row.classList.toggle('is-between', condition.operator === 'between'); sync(); });
    row.querySelector('.ac-condition-value').addEventListener('input', function (event) { condition.value = Number(event.target.value); sync(); });
    row.querySelector('.ac-condition-value2').addEventListener('input', function (event) { condition.value2 = Number(event.target.value); sync(); });
    row.querySelector('.button-link-delete').addEventListener('click', function () { profile.conditions.splice(conditionIndex, 1); render(); });
    return row;
  }

  function renderStage(item, stageIndex) {
    var el = document.createElement('article');
    el.className = 'ac-stage';
    el.draggable = true;
    el.dataset.stageIndex = stageIndex;
    el.innerHTML =
      '<header class="ac-stage-header">' +
        '<button type="button" class="ac-drag" aria-label="Drag stage"><span class="dashicons dashicons-move"></span></button>' +
        '<div class="ac-stage-title"><span class="ac-stage-number">Stage ' + (stageIndex + 1) + '</span><strong>' + escapeHtml(item.name || settings.i18n.untitledStage) + '</strong><small>' + (item.questions || []).length + ' question' + ((item.questions || []).length === 1 ? '' : 's') + '</small></div>' +
        '<div class="ac-row-actions"><button type="button" class="button-link ac-toggle-stage" aria-expanded="true">Collapse</button><button type="button" class="button-link-delete ac-delete-stage">Delete</button></div>' +
      '</header>' +
      '<div class="ac-stage-body">' +
        '<div class="ac-form-grid ac-stage-fields">' +
          '<label class="ac-field"><span>Stage name</span><input class="ac-stage-name" value="' + escapeHtml(item.name || '') + '" placeholder="e.g. Growth"></label>' +
          '<label class="ac-field' + (features.weighted ? '' : ' ac-pro-locked') + '"><span>Weight' + (features.weighted ? '' : ' — Pro') + '</span><input class="ac-stage-weight" type="number" min="0" step="0.1" value="' + escapeHtml(item.weight == null ? 1 : item.weight) + '"' + (features.weighted ? '' : ' disabled') + '></label>' +
          '<label class="ac-field ac-field-wide"><span>Description</span><textarea class="ac-stage-description" rows="2" placeholder="What does this stage measure?">' + escapeHtml(item.description || '') + '</textarea></label>' +
        '</div>' +
        '<div class="ac-question-list"></div>' +
        '<button type="button" class="button ac-add-question"><span class="dashicons dashicons-plus-alt2"></span> Add question</button>' +
      '</div>';

    var questionList = el.querySelector('.ac-question-list');
    (item.questions || []).forEach(function (q, questionIndex) {
      questionList.appendChild(renderQuestion(q, stageIndex, questionIndex));
    });

    el.querySelector('.ac-stage-name').addEventListener('input', function (event) {
      item.name = event.target.value;
      el.querySelector('.ac-stage-title strong').textContent = item.name || settings.i18n.untitledStage;
      sync();
    });
    el.querySelector('.ac-stage-description').addEventListener('input', function (event) { item.description = event.target.value; sync(); });
    el.querySelector('.ac-stage-weight').addEventListener('input', function (event) { item.weight = Number(event.target.value) || 0; sync(); });
    el.querySelector('.ac-add-question').addEventListener('click', function () { item.questions = item.questions || []; item.questions.push(question()); render(); });
    el.querySelector('.ac-delete-stage').addEventListener('click', function () { if (window.confirm(settings.i18n.confirmDelete)) { state.stages.splice(stageIndex, 1); render(); } });
    el.querySelector('.ac-toggle-stage').addEventListener('click', function (event) {
      var body = el.querySelector('.ac-stage-body');
      var collapsed = body.hidden;
      body.hidden = !collapsed;
      event.target.textContent = collapsed ? 'Collapse' : 'Expand';
      event.target.setAttribute('aria-expanded', collapsed ? 'true' : 'false');
    });
    bindStageDrag(el);
    return el;
  }

  function renderQuestion(item, stageIndex, questionIndex) {
    var el = document.createElement('section');
    el.className = 'ac-question';
    el.draggable = true;
    el.dataset.stageIndex = stageIndex;
    el.dataset.questionIndex = questionIndex;
    var options = Object.keys(settings.questionTypes).map(function (key) {
      return '<option value="' + escapeHtml(key) + '"' + (item.type === key ? ' selected' : '') + '>' + escapeHtml(settings.questionTypes[key]) + '</option>';
    }).join('');
    el.innerHTML =
      '<header class="ac-question-header">' +
        '<button type="button" class="ac-drag" aria-label="Drag question"><span class="dashicons dashicons-move"></span></button>' +
        '<span class="ac-question-count">Q' + (questionIndex + 1) + '</span>' +
        '<strong class="ac-question-title">' + escapeHtml(item.prompt || settings.i18n.untitledQuestion) + '</strong>' +
        '<button type="button" class="button-link-delete ac-delete-question">Delete</button>' +
      '</header>' +
      '<div class="ac-question-body">' +
        '<label class="ac-field ac-field-wide"><span>Question</span><textarea class="ac-question-prompt" rows="2" placeholder="Enter the statement or question">' + escapeHtml(item.prompt || '') + '</textarea></label>' +
        '<div class="ac-form-grid ac-question-settings">' +
          '<label class="ac-field"><span>Question type</span><select class="ac-question-type">' + options + '</select></label>' +
          '<div class="ac-switches"><label><input class="ac-required" type="checkbox"' + (item.required ? ' checked' : '') + '> Required</label><label class="' + (features.reverseScoring ? '' : 'ac-pro-locked') + '"><input class="ac-reverse" type="checkbox"' + (item.reverse ? ' checked' : '') + (features.reverseScoring ? '' : ' disabled') + '> Reverse scoring' + (features.reverseScoring ? '' : ' — Pro') + '</label></div>' +
        '</div>' +
        '<div class="ac-answer-heading"><span>Answer choices</span><span>Score</span></div>' +
        '<div class="ac-answer-list"></div>' +
        '<button type="button" class="button-link ac-add-answer">+ Add answer choice</button>' +
      '</div>';

    var answerList = el.querySelector('.ac-answer-list');
    (item.answers || []).forEach(function (a, answerIndex) { answerList.appendChild(renderAnswer(a, item, answerIndex)); });
    el.querySelector('.ac-question-prompt').addEventListener('input', function (event) { item.prompt = event.target.value; el.querySelector('.ac-question-title').textContent = item.prompt || settings.i18n.untitledQuestion; sync(); });
    el.querySelector('.ac-required').addEventListener('change', function (event) { item.required = event.target.checked; sync(); });
    el.querySelector('.ac-reverse').addEventListener('change', function (event) { item.reverse = event.target.checked; sync(); });
    el.querySelector('.ac-question-type').addEventListener('change', function (event) { item.type = event.target.value; item.answers = defaultAnswers(item.type); render(); });
    el.querySelector('.ac-add-answer').addEventListener('click', function () { item.answers = item.answers || []; item.answers.push(answer('New answer', item.answers.length + 1)); render(); });
    el.querySelector('.ac-delete-question').addEventListener('click', function () { if (window.confirm(settings.i18n.confirmDelete)) { state.stages[stageIndex].questions.splice(questionIndex, 1); render(); } });
    bindQuestionDrag(el);
    return el;
  }

  function renderAnswer(item, questionItem, answerIndex) {
    var el = document.createElement('div');
    el.className = 'ac-answer';
    el.innerHTML = '<span class="ac-answer-grip dashicons dashicons-menu" aria-hidden="true"></span><div class="ac-answer-main"><span class="ac-answer-option-number">' + String(answerIndex + 1).padStart(2, '0') + '</span><input class="ac-answer-label" value="' + escapeHtml(item.label || '') + '" aria-label="Answer choice ' + (answerIndex + 1) + '"></div><label class="ac-answer-score-wrap"><span>Score</span><input class="ac-answer-score" type="number" step="0.1" value="' + escapeHtml(item.score == null ? 0 : item.score) + '"></label><button type="button" class="ac-icon-delete ac-delete-answer" aria-label="Delete answer"><span class="dashicons dashicons-trash"></span></button>';
    el.querySelector('.ac-answer-label').addEventListener('input', function (event) { item.label = event.target.value; sync(); });
    el.querySelector('.ac-answer-score').addEventListener('input', function (event) { item.score = Number(event.target.value) || 0; sync(); });
    el.querySelector('.ac-delete-answer').addEventListener('click', function () { questionItem.answers.splice(answerIndex, 1); render(); });
    return el;
  }

  function bindStageDrag(el) {
    el.addEventListener('dragstart', function (event) { event.dataTransfer.setData('text/ac-stage', el.dataset.stageIndex); el.classList.add('is-dragging'); });
    el.addEventListener('dragend', function () { el.classList.remove('is-dragging'); });
    el.addEventListener('dragover', function (event) { if (event.dataTransfer.types.indexOf('text/ac-stage') !== -1) event.preventDefault(); });
    el.addEventListener('drop', function (event) {
      var from = Number(event.dataTransfer.getData('text/ac-stage'));
      var to = Number(el.dataset.stageIndex);
      if (!Number.isNaN(from) && from !== to) { var moved = state.stages.splice(from, 1)[0]; state.stages.splice(to, 0, moved); render(); }
    });
  }

  function bindQuestionDrag(el) {
    el.addEventListener('dragstart', function (event) { event.stopPropagation(); event.dataTransfer.setData('text/ac-question', el.dataset.stageIndex + ':' + el.dataset.questionIndex); el.classList.add('is-dragging'); });
    el.addEventListener('dragend', function () { el.classList.remove('is-dragging'); });
    el.addEventListener('dragover', function (event) { if (event.dataTransfer.types.indexOf('text/ac-question') !== -1) event.preventDefault(); });
    el.addEventListener('drop', function (event) {
      event.stopPropagation();
      var source = event.dataTransfer.getData('text/ac-question').split(':').map(Number);
      var targetStage = Number(el.dataset.stageIndex); var targetQuestion = Number(el.dataset.questionIndex);
      if (source.length !== 2 || source.some(Number.isNaN)) return;
      var moved = state.stages[source[0]].questions.splice(source[1], 1)[0];
      if (source[0] === targetStage && source[1] < targetQuestion) targetQuestion--;
      state.stages[targetStage].questions.splice(targetQuestion, 0, moved); render();
    });
  }

  function activateTab(tab) {
    if (!tab) return;
    root.querySelectorAll('.ac-tab').forEach(function (item) { item.classList.remove('is-active'); });
    root.querySelectorAll('.ac-panel').forEach(function (item) { item.classList.remove('is-active'); });
    tab.classList.add('is-active');
    root.querySelector('[data-panel="' + tab.dataset.tab + '"]').classList.add('is-active');
    try { window.sessionStorage.setItem('assesscraft-active-tab', tab.dataset.tab); } catch (error) {}
  }
  root.querySelectorAll('.ac-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      activateTab(tab);
    });
  });
  try { var savedTab = window.sessionStorage.getItem('assesscraft-active-tab'); if (savedTab) activateTab(root.querySelector('[data-tab="' + savedTab + '"]')); } catch (error) {}
  root.querySelectorAll('.ac-add-stage').forEach(function (button) { button.addEventListener('click', function () { state.stages.push(stage()); render(); }); });
  document.getElementById('ac-add-band').addEventListener('click', function () { state.scoring.bands.push({ id: id('band'), min: 0, max: 100, label: 'New classification', color: '#6E7F6A', interpretation: '' }); render(); });
  root.querySelectorAll('.ac-add-profile, #ac-add-profile').forEach(function (button) { button.addEventListener('click', function () {
	if (features.profileLimit >= 0 && state.profiles.length >= features.profileLimit) {
	  updateProfileLimitState();
	  profileLimitNotice.hidden = false;
	  profileLimitNotice.focus();
	  profileLimitNotice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	  return;
	}
	state.profiles.push({ id: id('profile'), title: '', description: '', recommendation: '', match: 'all', priority: state.profiles.length + 1, conditions: [{ metric: 'overall', operator: 'gte', value: 70, value2: 100 }] }); render();
  }); });

  var designPreview = document.getElementById('ac-design-preview');
  function updateDesignPreview() {
    if (!designPreview) return;
    var values = {};
    root.querySelectorAll('[data-design]').forEach(function (input) { values[input.dataset.design] = input.value; });
    var article = designPreview.querySelector('article');
    article.style.background = values.background;
    article.style.color = values.text;
    article.style.borderRadius = values.radius + 'px';
    article.style.fontFamily = values.font === 'serif' ? 'Georgia, "Times New Roman", serif' : '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
    designPreview.style.maxWidth = Math.min(720, Number(values.width) || 760) + 'px';
    article.querySelector('small').style.color = values.accent;
    article.querySelector('p').style.color = values.muted;
    article.querySelector('div').style.background = values.surface;
    article.querySelector('em').style.color = values.accent;
    article.querySelector('button').style.background = values.primary;
    article.querySelector('button').style.color = values.button_text;
    article.querySelector('button').style.borderRadius = values.radius + 'px';
    root.querySelectorAll('.ac-color-field').forEach(function (field) {
      var input = field.querySelector('.ac-design-color-code');
      if (!input) return;
      var value = input.value.trim().toUpperCase();
      var valid = /^#[0-9A-F]{6}$/.test(value);
      input.classList.toggle('is-invalid', !valid);
      if (valid) field.querySelector('.ac-color-swatch').value = value;
    });
    root.querySelectorAll('[data-output]').forEach(function (output) { output.textContent = values[output.dataset.output] + 'px'; });
  }
  root.querySelectorAll('[data-design]').forEach(function (input) { input.addEventListener('input', updateDesignPreview); input.addEventListener('change', updateDesignPreview); });
  root.querySelectorAll('[data-color-picker]').forEach(function (picker) {
    picker.addEventListener('input', function () {
      var codeInput = root.querySelector('.ac-design-color-code[data-design="' + picker.dataset.colorPicker + '"]');
      if (!codeInput) return;
      codeInput.value = picker.value.toUpperCase();
      codeInput.classList.remove('is-invalid');
      updateDesignPreview();
    });
  });
  root.querySelectorAll('.ac-design-color-code').forEach(function (input) { input.addEventListener('blur', function () { if (!/^#[0-9A-F]{6}$/.test(input.value.trim().toUpperCase())) { input.value = input.defaultValue.toUpperCase(); updateDesignPreview(); } }); });
  updateDesignPreview();

  var saveTemplate = document.querySelector('[data-save-template]');
  if (saveTemplate) {
    saveTemplate.querySelector('.ac-save-template-submit').addEventListener('click', function () {
      var nameInput = saveTemplate.querySelector('[data-template-field="name"]');
      if (!nameInput.value.trim()) {
        nameInput.focus();
        nameInput.reportValidity();
        return;
      }

      var form = document.createElement('form');
      form.method = 'post';
      form.action = saveTemplate.dataset.action;
      form.hidden = true;
      var values = {
        action: 'assesscraft_save_template',
        assessment_id: saveTemplate.dataset.assessment,
        _wpnonce: saveTemplate.dataset.nonce,
        template_name: nameInput.value,
        template_category: saveTemplate.querySelector('[data-template-field="category"]').value,
        template_version: saveTemplate.querySelector('[data-template-field="version"]').value,
        template_description: saveTemplate.querySelector('[data-template-field="description"]').value
      };
      Object.keys(values).forEach(function (key) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = values[key];
        form.appendChild(input);
      });
      document.body.appendChild(form);
      form.submit();
    });
  }
  render();
}());
