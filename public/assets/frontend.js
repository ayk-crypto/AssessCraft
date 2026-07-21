(function () {
  'use strict';

  function element(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null) node.textContent = text;
    return node;
  }

  function parsePayload(root) {
    try {
      return JSON.parse(root.getAttribute('data-assessment') || '{}');
    } catch (error) {
      return null;
    }
  }

  function flattenQuestions(stages) {
    var output = [];
    stages.forEach(function (stage, stageIndex) {
      (stage.questions || []).forEach(function (question, questionIndex) {
        output.push({ stage: stage, stageIndex: stageIndex, question: question, questionIndex: questionIndex });
      });
    });
    return output;
  }

  function normalizedScore(question, answer) {
    var answers = question.answers || [];
    var scores = answers.map(function (item) { return Number(item.score) || 0; });
    var selected = Number(answer.score) || 0;
    if (!scores.length) return 0;
    var min = Math.min.apply(null, scores);
    var max = Math.max.apply(null, scores);
    if (question.reverse) selected = max + min - selected;
    if (max === min) return 100;
    return Math.max(0, Math.min(100, ((selected - min) / (max - min)) * 100));
  }

  function calculate(stages, responses) {
    var stageResults = stages.map(function (stage) {
      var scored = (stage.questions || []).map(function (question) {
        var answer = responses[question.id];
        return answer ? normalizedScore(question, answer) : null;
      }).filter(function (score) { return score !== null; });
      var score = scored.length ? scored.reduce(function (sum, value) { return sum + value; }, 0) / scored.length : 0;
      return { id: stage.id, name: stage.name || 'Untitled stage', score: score, weight: Math.max(0, Number(stage.weight) || 0) };
    });
    var weightTotal = stageResults.reduce(function (sum, item) { return sum + item.weight; }, 0);
    var overall = weightTotal
      ? stageResults.reduce(function (sum, item) { return sum + item.score * item.weight; }, 0) / weightTotal
      : (stageResults.length ? stageResults.reduce(function (sum, item) { return sum + item.score; }, 0) / stageResults.length : 0);
    return { stages: stageResults, overall: overall };
  }

  function Runner(root, payload) {
    this.root = root;
    this.payload = payload;
    this.config = payload.config || {};
    this.overview = this.config.overview || {};
    this.stages = (this.config.stages || []).filter(function (stage) { return (stage.questions || []).length; });
    this.items = flattenQuestions(this.stages);
    this.responses = {};
    this.index = 0;
    this.error = '';
    this.applyDesign();
  }

  Runner.prototype.applyDesign = function () {
    var design = this.config.design || {};
    if (design.primary) this.root.style.setProperty('--ac-primary', design.primary);
    if (design.accent) this.root.style.setProperty('--ac-accent', design.accent);
    if (design.background) this.root.style.setProperty('--ac-bg', design.background);
  };

  Runner.prototype.clear = function () {
    this.root.innerHTML = '';
    this.root.setAttribute('aria-live', 'polite');
  };

  Runner.prototype.renderIntro = function () {
    var self = this;
    this.clear();
    var shell = element('section', 'ac-front-shell ac-intro');
    var eyebrow = element('div', 'ac-front-eyebrow', 'AssessCraft assessment');
    var heading = element('h2', 'ac-front-title', this.overview.heading || this.payload.title || 'Assessment');
    shell.appendChild(eyebrow);
    shell.appendChild(heading);
    if (this.overview.description) shell.appendChild(element('p', 'ac-front-description', this.overview.description));

    if (this.stages.length) {
      var stageGrid = element('div', 'ac-stage-preview');
      this.stages.forEach(function (stage, index) {
        var card = element('div', 'ac-stage-preview-card');
        card.appendChild(element('span', 'ac-stage-preview-number', String(index + 1).padStart(2, '0')));
        card.appendChild(element('strong', '', stage.name || 'Untitled stage'));
        if (stage.description) card.appendChild(element('p', '', stage.description));
        stageGrid.appendChild(card);
      });
      shell.appendChild(stageGrid);
    }

    var meta = element('div', 'ac-intro-meta');
    meta.appendChild(element('span', '', this.items.length + ' question' + (this.items.length === 1 ? '' : 's')));
    if (this.overview.estimated_time) meta.appendChild(element('span', '', this.overview.estimated_time));
    shell.appendChild(meta);

    if (this.overview.disclaimer) shell.appendChild(element('p', 'ac-front-disclaimer', this.overview.disclaimer));
    var button = element('button', 'ac-front-button', this.overview.start_label || 'Begin Assessment');
    button.type = 'button';
    button.disabled = !this.items.length;
    button.addEventListener('click', function () {
      self.root.dispatchEvent(new CustomEvent('assesscraft:start', { detail: self.payload }));
      self.index = 0;
      self.renderQuestion();
    });
    shell.appendChild(button);
    if (!this.items.length) shell.appendChild(element('p', 'ac-front-empty', 'This assessment does not contain any questions yet.'));
    this.root.appendChild(shell);
  };

  Runner.prototype.renderQuestion = function () {
    var self = this;
    var item = this.items[this.index];
    if (!item) return this.renderReport();
    this.clear();
    var shell = element('section', 'ac-front-shell ac-question-screen');
    var top = element('div', 'ac-progress-row');
    var track = element('div', 'ac-progress-track');
    var fill = element('div', 'ac-progress-fill');
    fill.style.width = ((this.index / this.items.length) * 100) + '%';
    track.appendChild(fill);
    top.appendChild(track);
    top.appendChild(element('span', 'ac-progress-label', (this.index + 1) + ' of ' + this.items.length));
    shell.appendChild(top);

    var card = element('div', 'ac-front-question-card');
    card.appendChild(element('div', 'ac-front-eyebrow', item.stage.name || 'Assessment'));
    var title = element('h2', 'ac-question-prompt', item.question.prompt || 'Untitled question');
    title.id = 'ac-question-' + item.question.id;
    title.tabIndex = -1;
    card.appendChild(title);
    var group = element('div', 'ac-choice-list');
    group.setAttribute('role', 'radiogroup');
    group.setAttribute('aria-labelledby', title.id);
    (item.question.answers || []).forEach(function (answer) {
      var label = element('label', 'ac-choice');
      var input = document.createElement('input');
      input.type = 'radio';
      input.name = 'ac-answer-' + item.question.id;
      input.value = answer.id;
      input.checked = Boolean(self.responses[item.question.id] && self.responses[item.question.id].id === answer.id);
      input.addEventListener('change', function () {
        self.responses[item.question.id] = { id: answer.id, label: answer.label, score: answer.score };
        self.error = '';
        group.querySelectorAll('.ac-choice').forEach(function (choice) { choice.classList.toggle('is-selected', choice.contains(input) && input.checked); });
        var error = shell.querySelector('.ac-validation');
        if (error) error.remove();
      });
      var marker = element('span', 'ac-choice-marker');
      var text = element('span', 'ac-choice-label', answer.label || 'Untitled answer');
      label.appendChild(input); label.appendChild(marker); label.appendChild(text);
      if (input.checked) label.classList.add('is-selected');
      group.appendChild(label);
    });
    if (!(item.question.answers || []).length) {
      group.appendChild(element('p', 'ac-front-empty', 'This question does not have any answer choices. Please contact the site administrator.'));
    }
    card.appendChild(group);
    shell.appendChild(card);

    if (this.error) {
      var validation = element('p', 'ac-validation', this.error);
      validation.setAttribute('role', 'alert');
      shell.appendChild(validation);
    }

    var nav = element('div', 'ac-front-nav');
    var back = element('button', 'ac-front-button ac-front-button-secondary', 'Back');
    back.type = 'button'; back.disabled = this.index === 0;
    back.addEventListener('click', function () { if (self.index > 0) { self.index--; self.error = ''; self.renderQuestion(); } });
    var next = element('button', 'ac-front-button', this.index === this.items.length - 1 ? 'See results' : 'Next');
    next.type = 'button'; next.disabled = !(item.question.answers || []).length;
    next.addEventListener('click', function () {
      if (item.question.required && !self.responses[item.question.id]) {
        self.error = 'Please select an answer before continuing.';
        self.renderQuestion();
        var first = self.root.querySelector('input[type="radio"]');
        if (first) first.focus();
        return;
      }
      self.error = '';
      if (self.index < self.items.length - 1) { self.index++; self.renderQuestion(); } else { self.renderReport(); }
    });
    nav.appendChild(back); nav.appendChild(next); shell.appendChild(nav);
    this.root.appendChild(shell);
    window.requestAnimationFrame(function () { title.focus(); });
  };

  Runner.prototype.renderReport = function () {
    var self = this;
    var result = calculate(this.stages, this.responses);
    this.clear();
    var shell = element('section', 'ac-front-shell ac-result-screen');
    shell.appendChild(element('div', 'ac-front-eyebrow', 'Assessment complete'));
    shell.appendChild(element('h2', 'ac-front-title', 'Your preliminary results'));
    shell.appendChild(element('p', 'ac-front-description', 'This summary reflects the scoring configured for the questions you completed. Detailed profiles and recommendations will be available through the AssessCraft report builder.'));
    var overall = element('div', 'ac-overall-score');
    overall.appendChild(element('span', '', 'Overall score'));
    overall.appendChild(element('strong', '', Math.round(result.overall) + '%'));
    shell.appendChild(overall);
    var scores = element('div', 'ac-result-grid');
    result.stages.forEach(function (stage) {
      var card = element('div', 'ac-result-card');
      card.appendChild(element('strong', '', stage.name));
      card.appendChild(element('span', '', Math.round(stage.score) + '%'));
      var meter = element('div', 'ac-result-meter');
      var fill = element('div', ''); fill.style.width = stage.score + '%'; meter.appendChild(fill); card.appendChild(meter);
      scores.appendChild(card);
    });
    shell.appendChild(scores);
    var restart = element('button', 'ac-front-button ac-front-button-secondary', 'Start over');
    restart.type = 'button'; restart.addEventListener('click', function () { self.responses = {}; self.index = 0; self.renderIntro(); });
    shell.appendChild(restart);
    this.root.appendChild(shell);
    this.root.dispatchEvent(new CustomEvent('assesscraft:complete', { detail: { assessment: this.payload, result: result, responses: this.responses } }));
  };

  document.querySelectorAll('.assesscraft-app').forEach(function (root) {
    var payload = parsePayload(root);
    if (!payload) { root.textContent = 'This assessment could not be loaded.'; return; }
    new Runner(root, payload).renderIntro();
  });
}());
