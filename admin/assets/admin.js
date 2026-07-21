(function () {
  'use strict';

  var root = document.getElementById('assesscraft-admin');
  if (!root) return;

  var stageList = document.getElementById('ac-stage-list');
  var emptyState = document.getElementById('ac-empty-builder');
  var jsonField = document.getElementById('assesscraft-stages-json');
  var settings = window.assessCraftAdmin || { questionTypes: {}, i18n: {} };
  var state = { stages: parseStages(jsonField.value) };

  function parseStages(value) {
    try {
      var stages = JSON.parse(value || '[]');
      return Array.isArray(stages) ? stages : [];
    } catch (error) {
      return [];
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
  }

  function render() {
    stageList.innerHTML = '';
    state.stages.forEach(function (item, stageIndex) {
      stageList.appendChild(renderStage(item, stageIndex));
    });
    emptyState.hidden = state.stages.length > 0;
    sync();
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
          '<label class="ac-field"><span>Weight</span><input class="ac-stage-weight" type="number" min="0" step="0.1" value="' + escapeHtml(item.weight == null ? 1 : item.weight) + '"></label>' +
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
          '<div class="ac-switches"><label><input class="ac-required" type="checkbox"' + (item.required ? ' checked' : '') + '> Required</label><label><input class="ac-reverse" type="checkbox"' + (item.reverse ? ' checked' : '') + '> Reverse scoring</label></div>' +
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
    el.innerHTML = '<span class="dashicons dashicons-menu"></span><input class="ac-answer-label" value="' + escapeHtml(item.label || '') + '"><input class="ac-answer-score" type="number" step="0.1" value="' + escapeHtml(item.score == null ? 0 : item.score) + '"><button type="button" class="button-link-delete ac-delete-answer" aria-label="Delete answer">&times;</button>';
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

  root.querySelectorAll('.ac-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      root.querySelectorAll('.ac-tab').forEach(function (item) { item.classList.remove('is-active'); });
      root.querySelectorAll('.ac-panel').forEach(function (item) { item.classList.remove('is-active'); });
      tab.classList.add('is-active');
      root.querySelector('[data-panel="' + tab.dataset.tab + '"]').classList.add('is-active');
    });
  });
  root.querySelectorAll('.ac-add-stage').forEach(function (button) { button.addEventListener('click', function () { state.stages.push(stage()); render(); }); });
  render();
}());

