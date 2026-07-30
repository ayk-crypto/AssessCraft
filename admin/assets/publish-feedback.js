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

  function watchPublishAction() {
    unlockPublishAction();

    var publishButton = document.getElementById('publish');
    if (publishButton && window.MutationObserver) {
      observer = new MutationObserver(unlockPublishAction);
      observer.observe(publishButton, {
        attributes: true,
        attributeFilter: ['disabled', 'class', 'aria-disabled']
      });
    }

    var postForm = document.getElementById('post');
    if (postForm) {
      postForm.addEventListener('submit', function () {
        postForm.noValidate = true;
        postForm.setAttribute('novalidate', 'novalidate');
        unlockPublishAction();
      }, true);
    }

    // Some admin optimization plugins defer scripts. Recheck briefly after load
    // so the native WordPress button cannot be left disabled by a late script.
    [0, 100, 300, 750, 1500, 3000].forEach(function (delay) {
      window.setTimeout(unlockPublishAction, delay);
    });
  }

  if ('loading' === document.readyState) {
    document.addEventListener('DOMContentLoaded', watchPublishAction);
  } else {
    watchPublishAction();
  }

  window.addEventListener('pageshow', watchPublishAction);
}());
