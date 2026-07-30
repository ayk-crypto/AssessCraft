(function () {
  'use strict';

  var settings = window.assessCraftAdmin || {};
  var features = settings.features || {};
  var feedback = window.assessCraftPublishFeedback || {};

  function unlockPublishAction() {
    var postForm = document.getElementById('post');
    var publishButton = document.getElementById('publish');
    var publishActions = document.getElementById('major-publishing-actions');

    if (postForm && !postForm.noValidate) {
      postForm.noValidate = true;
      postForm.setAttribute('novalidate', 'novalidate');
    }

    if (publishButton) {
      if (publishButton.disabled) publishButton.disabled = false;
      if (publishButton.hasAttribute('disabled')) publishButton.removeAttribute('disabled');
      if (publishButton.classList.contains('disabled')) publishButton.classList.remove('disabled');
      if (publishButton.hasAttribute('aria-disabled')) publishButton.removeAttribute('aria-disabled');
      if (publishButton.getAttribute('aria-describedby') !== 'ac-publish-limit-message') {
        publishButton.setAttribute('aria-describedby', 'ac-publish-limit-message');
      }
    }

    if (!features.publishLimitReached || !publishActions) return;

    var notice = document.getElementById('ac-publish-limit-message');
    if (!notice) {
      notice = document.createElement('div');
      notice.id = 'ac-publish-limit-message';
      notice.className = 'ac-editor-publish-limit';
      notice.setAttribute('role', 'status');
      notice.innerHTML =
        '<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>' +
        '<div><strong></strong><p></p></div>';
      publishActions.parentNode.insertBefore(notice, publishActions);
    }

    var title = notice.querySelector('strong');
    var message = notice.querySelector('p');
    if (title && title.textContent !== (feedback.title || '')) title.textContent = feedback.title || '';
    if (message && message.textContent !== (feedback.message || '')) message.textContent = feedback.message || '';
  }

  function showFallbackError(message) {
    var notice = document.getElementById('ac-direct-publish-result');
    if (!notice) {
      notice = document.createElement('div');
      notice.id = 'ac-direct-publish-result';
      notice.className = 'notice notice-error inline';
      var publishBox = document.getElementById('submitpost');
      if (publishBox && publishBox.parentNode) {
        publishBox.parentNode.insertBefore(notice, publishBox);
      }
    }
    if (notice) {
      notice.textContent = message || feedback.failed || 'WordPress could not publish this assessment.';
      notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function bindDirectPublish() {
    var button = document.getElementById('assesscraft-direct-publish');
    var postForm = document.getElementById('post');
    if (!button || !postForm || button.dataset.bound === 'true') return;

    button.dataset.bound = 'true';
    button.addEventListener('click', function () {
      if (button.disabled) return;

      var originalLabel = button.textContent;
      var formData = new FormData(postForm);
      formData.set('action', 'assesscraft_publish_assessment');
      formData.set('assessment_id', button.dataset.assessment || '0');
      formData.set('_wpnonce', button.dataset.nonce || '');

      button.disabled = true;
      button.textContent = feedback.publishing || 'Saving and publishing…';

      window.fetch(button.dataset.action, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).then(function (response) {
        return response.json();
      }).then(function (payload) {
        var redirect = payload && payload.data && payload.data.redirect ? payload.data.redirect : '';
        if (redirect) {
          window.location.assign(redirect);
          return;
        }
        throw new Error(payload && payload.data && payload.data.message ? payload.data.message : feedback.failed);
      }).catch(function (error) {
        button.disabled = false;
        button.textContent = originalLabel;
        showFallbackError(error && error.message ? error.message : feedback.failed);
      });
    });
  }

  function initializePublishActions() {
    unlockPublishAction();
    bindDirectPublish();

    var postForm = document.getElementById('post');
    if (postForm && postForm.dataset.assesscraftSubmitBound !== 'true') {
      postForm.dataset.assesscraftSubmitBound = 'true';
      postForm.addEventListener('submit', function () {
        postForm.noValidate = true;
        postForm.setAttribute('novalidate', 'novalidate');
        unlockPublishAction();
      }, true);
    }

    // Run only a small, finite set of rechecks for deferred admin scripts.
    // Do not observe the Publish button's attributes: an observer that writes
    // those same attributes can create a self-triggering browser loop.
    [0, 100, 300, 750, 1500, 3000].forEach(function (delay) {
      window.setTimeout(function () {
        unlockPublishAction();
        bindDirectPublish();
      }, delay);
    });
  }

  if ('loading' === document.readyState) {
    document.addEventListener('DOMContentLoaded', initializePublishActions, { once: true });
  } else {
    initializePublishActions();
  }

  window.addEventListener('pageshow', function () {
    unlockPublishAction();
    bindDirectPublish();
  });
}());