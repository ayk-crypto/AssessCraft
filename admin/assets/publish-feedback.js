(function () {
  'use strict';

  var settings = window.assessCraftAdmin || {};
  var features = settings.features || {};
  var feedback = window.assessCraftPublishFeedback || {};
  var observer = null;

  function unlockPublishAction() {
    var postForm = document.getElementById('post');
    var publishButton = document.getElementById('publish');
    var publishActions = document.getElementById('major-publishing-actions');

    if (postForm) {
      postForm.noValidate = true;
      postForm.setAttribute('novalidate', 'novalidate');
    }

    if (publishButton) {
      publishButton.disabled = false;
      publishButton.removeAttribute('disabled');
      publishButton.classList.remove('disabled');
      publishButton.removeAttribute('aria-disabled');
      publishButton.setAttribute('aria-describedby', 'ac-publish-limit-message');
    }

    if (!features.publishLimitReached || !publishActions) return;

    var notice = document.getElementById('ac-publish-limit-message');
    if (!notice) {
      notice = document.createElement('div');
      notice.id = 'ac-publish-limit-message';
      notice.className = 'ac-editor-publish-limit';
      notice.setAttribute('role', 'status');
      publishActions.parentNode.insertBefore(notice, publishActions);
    }

    notice.innerHTML =
      '<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>' +
      '<div><strong></strong><p></p></div>';
    notice.querySelector('strong').textContent = feedback.title || '';
    notice.querySelector('p').textContent = feedback.message || '';
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

  function watchPublishAction() {
    unlockPublishAction();
    bindDirectPublish();

    var publishButton = document.getElementById('publish');
    if (publishButton && window.MutationObserver && !observer) {
      observer = new MutationObserver(unlockPublishAction);
      observer.observe(publishButton, {
        attributes: true,
        attributeFilter: ['disabled', 'class', 'aria-disabled']
      });
    }

    var postForm = document.getElementById('post');
    if (postForm && postForm.dataset.assesscraftSubmitBound !== 'true') {
      postForm.dataset.assesscraftSubmitBound = 'true';
      postForm.addEventListener('submit', function () {
        postForm.noValidate = true;
        postForm.setAttribute('novalidate', 'novalidate');
        unlockPublishAction();
      }, true);
    }

    // Some admin optimization plugins defer scripts. Recheck briefly after load
    // so the native WordPress button cannot be left disabled by a late script.
    [0, 100, 300, 750, 1500, 3000].forEach(function (delay) {
      window.setTimeout(function () {
        unlockPublishAction();
        bindDirectPublish();
      }, delay);
    });
  }

  if ('loading' === document.readyState) {
    document.addEventListener('DOMContentLoaded', watchPublishAction);
  } else {
    watchPublishAction();
  }

  window.addEventListener('pageshow', watchPublishAction);
}());
