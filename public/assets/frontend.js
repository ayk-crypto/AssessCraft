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
      return { id: stage.id, name: stage.name || 'Untitled stage', description: stage.description || '', score: score, weight: Math.max(0, Number(stage.weight) || 0) };
    });
    var weightTotal = stageResults.reduce(function (sum, item) { return sum + item.weight; }, 0);
    var overall = weightTotal
      ? stageResults.reduce(function (sum, item) { return sum + item.score * item.weight; }, 0) / weightTotal
      : (stageResults.length ? stageResults.reduce(function (sum, item) { return sum + item.score; }, 0) / stageResults.length : 0);
    return { stages: stageResults, overall: overall };
  }

  function bandFor(score, bands) {
    return (bands || []).find(function (band) {
      return score >= Number(band.min) && score <= Number(band.max);
    }) || null;
  }

  function metricValue(metric, result) {
    if (metric === 'overall') return result.overall;
    if (metric.indexOf('stage_') === 0) {
      var id = metric.slice(6);
      var stage = result.stages.find(function (item) { return item.id === id; });
      return stage ? stage.score : null;
    }
    return null;
  }

  function conditionMatches(condition, result) {
    var actual = metricValue(condition.metric || 'overall', result);
    var value = Number(condition.value) || 0;
    var value2 = Number(condition.value2) || 0;
    if (actual === null) return false;
    if (condition.operator === 'lte') return actual <= value;
    if (condition.operator === 'gt') return actual > value;
    if (condition.operator === 'lt') return actual < value;
    if (condition.operator === 'between') return actual >= Math.min(value, value2) && actual <= Math.max(value, value2);
    return actual >= value;
  }

  function resolveProfile(profiles, result) {
    return (profiles || []).slice().sort(function (a, b) { return (Number(b.priority) || 0) - (Number(a.priority) || 0); }).find(function (profile) {
      var conditions = profile.conditions || [];
      if (!conditions.length) return false;
      return profile.match === 'any'
        ? conditions.some(function (condition) { return conditionMatches(condition, result); })
        : conditions.every(function (condition) { return conditionMatches(condition, result); });
    }) || null;
  }

  function generatedProfile(result) {
    var ordered = result.stages.slice().sort(function (a, b) { return b.score - a.score; });
    var strongest = ordered[0];
    var weakest = ordered[ordered.length - 1];
    if (!strongest || !weakest) return null;
    if (strongest.score - weakest.score >= 15) {
      return {
        title: strongest.name + ' Ahead of ' + weakest.name,
        description: 'Your strongest area is developing ahead of your lowest-scoring area. This gap may affect how consistently the organization can turn current strengths into sustainable results.',
        recommendation: 'Protect what is working in ' + strongest.name + ' while prioritizing practical improvements in ' + weakest.name + '.'
      };
    }
    return {
      title: 'Balanced Development',
      description: 'The assessed areas are developing at a broadly similar pace, without a pronounced gap between the strongest and weakest dimensions.',
      recommendation: 'Focus on the lowest-scoring dimension while continuing to monitor progress across the complete operating system.'
    };
  }

  function responseSignals(items, responses) {
    return items.map(function (item) {
      var answer = responses[item.question.id];
      return answer ? { prompt: item.question.prompt, stage: item.stage.name, score: normalizedScore(item.question, answer) } : null;
    }).filter(Boolean).sort(function (a, b) { return a.score - b.score; });
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
    if (design.surface) this.root.style.setProperty('--ac-surface', design.surface);
    if (design.text) this.root.style.setProperty('--ac-text', design.text);
    if (design.muted) this.root.style.setProperty('--ac-muted', design.muted);
    if (design.button_text) this.root.style.setProperty('--ac-button-text', design.button_text);
    this.root.style.setProperty('--ac-radius', Math.max(0, Number(design.radius) || 0) + 'px');
    this.root.style.setProperty('--ac-width', Math.max(520, Number(design.width) || 760) + 'px');
    this.root.style.setProperty('--ac-font', design.font === 'serif' ? 'Georgia, "Times New Roman", serif' : '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif');
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
    var bands = (this.config.scoring || {}).bands || [];
    var overallBand = bandFor(result.overall, bands);
    var profile = resolveProfile(this.config.profiles || [], result) || generatedProfile(result);
    var reportConfig = this.config.report || {};
    var sections = reportConfig.sections || [];
    var has = function (section) { return sections.indexOf(section) !== -1; };
    this.clear();
    var shell = element('section', 'ac-front-shell ac-result-screen');
    shell.appendChild(element('div', 'ac-front-eyebrow', 'Assessment complete'));
    shell.appendChild(element('h2', 'ac-front-title', reportConfig.heading || 'Your preliminary results'));
    if (reportConfig.intro) shell.appendChild(element('p', 'ac-front-description', reportConfig.intro));
    if (profile && has('profile')) {
      var profileCard = element('div', 'ac-profile-result');
      profileCard.appendChild(element('span', '', 'Your result profile'));
      profileCard.appendChild(element('h3', '', profile.title || 'Assessment profile'));
      if (profile.description) profileCard.appendChild(element('p', '', profile.description));
      shell.appendChild(profileCard);
    }
    if (has('overall')) {
      var overall = element('div', 'ac-overall-score');
      var overallLabel = element('div', '');
      overallLabel.appendChild(element('span', '', 'Overall score'));
      if (overallBand) {
        var classification = element('em', '', overallBand.label || '');
        classification.style.color = overallBand.color || 'var(--ac-accent)';
        overallLabel.appendChild(classification);
      }
      overall.appendChild(overallLabel);
      overall.appendChild(element('strong', '', Math.round(result.overall) + '%'));
      shell.appendChild(overall);
    }
    if (has('interpretations') && overallBand && overallBand.interpretation) shell.appendChild(element('p', 'ac-band-interpretation-result', overallBand.interpretation));
    if (has('stage_scores')) {
      var scores = element('div', 'ac-result-grid');
      result.stages.forEach(function (stage) {
        var card = element('div', 'ac-result-card');
        card.appendChild(element('strong', '', stage.name));
        card.appendChild(element('span', '', Math.round(stage.score) + '%'));
        var stageBand = bandFor(stage.score, bands);
        if (stageBand) {
          var badge = element('em', 'ac-result-band', stageBand.label || '');
          badge.style.color = stageBand.color || 'var(--ac-accent)';
          card.appendChild(badge);
        }
        var meter = element('div', 'ac-result-meter');
        var fill = element('div', ''); fill.style.width = stage.score + '%'; meter.appendChild(fill); card.appendChild(meter);
        scores.appendChild(card);
      });
      shell.appendChild(scores);
    }
    if (has('interpretations')) {
      var detail = element('section', 'ac-report-section');
      detail.appendChild(element('div', 'ac-front-eyebrow', 'Detailed interpretation'));
      result.stages.forEach(function (stage) {
        var stageBand = bandFor(stage.score, bands);
        var row = element('article', 'ac-interpretation-card');
        var copy = stageBand && stageBand.interpretation ? stageBand.interpretation : (stage.description || 'Review this dimension in the context of the organization’s priorities and operating environment.');
        row.appendChild(element('h3', '', stage.name));
        row.appendChild(element('span', '', Math.round(stage.score) + '% · ' + (stageBand ? stageBand.label : 'Unclassified')));
        row.appendChild(element('p', '', copy));
        detail.appendChild(row);
      });
      shell.appendChild(detail);
      var signals = responseSignals(this.items, this.responses);
      if (signals.length) {
        var signalSection = element('section', 'ac-report-section ac-signal-section');
        signalSection.appendChild(element('div', 'ac-front-eyebrow', 'Response signals'));
        signals.slice(0, Math.min(3, signals.length)).forEach(function (signal) {
          var signalRow = element('article', 'ac-signal-item');
          signalRow.appendChild(element('span', '', signal.stage));
          signalRow.appendChild(element('p', '', signal.prompt));
          signalSection.appendChild(signalRow);
        });
        shell.appendChild(signalSection);
      }
    }
    if (has('recommendation')) {
      var orderedStages = result.stages.slice().sort(function (a, b) { return a.score - b.score; });
      var attention = element('section', 'ac-report-section ac-attention-section');
      attention.appendChild(element('div', 'ac-front-eyebrow', 'Areas requiring attention'));
      orderedStages.slice(0, Math.min(2, orderedStages.length)).forEach(function (stage) {
        var item = element('article', 'ac-attention-item');
        item.appendChild(element('strong', '', stage.name));
        item.appendChild(element('p', '', stage.description || 'This is one of the lower-scoring dimensions and may benefit from focused review.'));
        attention.appendChild(item);
      });
      shell.appendChild(attention);
      if (profile && profile.recommendation) {
        var nextStep = element('section', 'ac-next-step');
        nextStep.appendChild(element('div', 'ac-front-eyebrow', 'Recommended next step'));
        nextStep.appendChild(element('p', '', profile.recommendation));
        shell.appendChild(nextStep);
      }
    }
    if (has('cta') && (this.config.lead_form || {}).enabled) {
      var cta = element('div', 'ac-report-cta');
      cta.appendChild(element('h3', '', reportConfig.cta_heading || 'Ready to take the next step?'));
      if (reportConfig.cta_text) cta.appendChild(element('p', '', reportConfig.cta_text));
      var ctaButton = element('button', 'ac-front-button', reportConfig.cta_label || 'Request a Comprehensive Assessment');
      ctaButton.type = 'button';
      ctaButton.addEventListener('click', function () { ctaButton.remove(); self.renderLeadForm(cta, result, profile, overallBand); });
      cta.appendChild(ctaButton);
      cta.appendChild(element('small', '', 'Your results are shared only if you submit the form.'));
      shell.appendChild(cta);
    }
    if (has('restart')) {
      var restart = element('button', 'ac-front-button ac-front-button-secondary', 'Start over');
      restart.type = 'button'; restart.addEventListener('click', function () { self.responses = {}; self.index = 0; self.renderIntro(); });
      shell.appendChild(restart);
    }
    this.root.appendChild(shell);
    this.root.dispatchEvent(new CustomEvent('assesscraft:complete', { detail: { assessment: this.payload, result: result, profile: profile, overallBand: overallBand, responses: this.responses } }));
  };

  Runner.prototype.renderLeadForm = function (container, result, profile, overallBand) {
    var self = this;
    var leadConfig = this.config.lead_form || {};
    var form = element('form', 'ac-lead-form');
    form.noValidate = true;
    form.innerHTML =
      '<div class="ac-lead-grid">' +
        '<label><span>Name *</span><input name="name" autocomplete="name" required></label>' +
        '<label><span>Email *</span><input name="email" type="email" autocomplete="email" required></label>' +
        '<label><span>Company</span><input name="company" autocomplete="organization"></label>' +
        '<label><span>Phone</span><input name="phone" autocomplete="tel"></label>' +
        '<label class="ac-lead-wide"><span>How can we help?</span><textarea name="message" rows="3"></textarea></label>' +
        '<label class="ac-honeypot" aria-hidden="true"><span>Website</span><input name="website" tabindex="-1" autocomplete="off"></label>' +
      '</div>';
    var consent = element('label', 'ac-lead-consent');
    var consentInput = document.createElement('input'); consentInput.type = 'checkbox'; consentInput.name = 'consent'; consentInput.required = true;
    consent.appendChild(consentInput); consent.appendChild(element('span', '', leadConfig.consent_label || 'I agree to share my contact details and assessment results for follow-up.'));
    form.appendChild(consent);
    var status = element('p', 'ac-lead-status'); status.setAttribute('role', 'status'); form.appendChild(status);
    var submit = element('button', 'ac-front-button', 'Send request'); submit.type = 'submit'; form.appendChild(submit);
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (!form.reportValidity()) return;
      submit.disabled = true; submit.textContent = 'Sending...'; status.textContent = '';
      var data = new FormData(form);
      var payload = {
        assessment_id: self.payload.id,
        name: data.get('name'), email: data.get('email'), company: data.get('company'), phone: data.get('phone'), message: data.get('message'), website: data.get('website'),
        consent: data.get('consent') === 'on',
        result: { overall: result.overall, classification: overallBand ? overallBand.label : '', profile: profile ? profile.title : '', stages: result.stages.map(function (stage) { return { name: stage.name, score: stage.score }; }) }
      };
      fetch(self.payload.lead_endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload), credentials: 'same-origin' })
        .then(function (response) { return response.json().then(function (body) { if (!response.ok) throw new Error(body.message || 'The request could not be sent.'); return body; }); })
        .then(function (body) { form.innerHTML = ''; var success = element('div', 'ac-lead-success', body.message || leadConfig.success_message || 'Thank you. Your request has been sent.'); success.setAttribute('role', 'status'); form.appendChild(success); })
        .catch(function (error) { status.textContent = error.message; status.classList.add('is-error'); submit.disabled = false; submit.textContent = 'Send request'; });
    });
    container.appendChild(form);
    form.querySelector('input[name="name"]').focus();
  };

  document.querySelectorAll('.assesscraft-app').forEach(function (root) {
    var payload = parsePayload(root);
    if (!payload) { root.textContent = 'This assessment could not be loaded.'; return; }
    new Runner(root, payload).renderIntro();
  });
}());
