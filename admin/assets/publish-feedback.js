(function () {
  'use strict';

  var settings = window.assessCraftAdmin || {};
  var features = settings.features || {};
  var feedback = window.assessCraftPublishFeedback || {};

  function restorePublishAction() {
    if (!features.publishLimitReached) return;

    var publishButton = document.getElementById('publish');
    var publishActions = document.getElementById('major-publishing-actions');

    if (publishButton) {
      publishButton.disabled = false;
      publishButton.removeAttribute('disabled');
      publishButton.classList.remove('disabled');
      publishButton.setAttribute('aria-describedby', 'ac-publish-limit-message');
    }

    if (!publishActions) return;

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

  if ('loading' === document.readyState) {
    document.addEventListener('DOMContentLoaded', restorePublishAction);
  } else {
    restorePublishAction();
  }

  window.addEventListener('pageshow', restorePublishAction);
}());
