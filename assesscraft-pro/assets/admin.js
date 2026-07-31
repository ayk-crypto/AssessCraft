(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		applyAssessCraftBrandmark();
		setupLicenseKeyToggle();
		setupLicenseFormFeedback();
	});

	function applyAssessCraftBrandmark() {
		var brandmark = document.querySelector('.ac-pro-brandmark');
		if (!brandmark) return;

		brandmark.innerHTML = [
			'<svg viewBox="0 0 64 64" role="img" aria-label="AssessCraft">',
			'<defs><linearGradient id="ac-pro-mark-gradient" x1="10" y1="8" x2="54" y2="56" gradientUnits="userSpaceOnUse"><stop stop-color="#3155e7"/><stop offset="1" stop-color="#806414"/></linearGradient></defs>',
			'<rect x="4" y="4" width="56" height="56" rx="15" fill="url(#ac-pro-mark-gradient)"/>',
			'<path d="M18 45.5 29.2 18h6.2L46 45.5h-7.1l-2-5.8H27l-2.1 5.8H18Zm11.1-11.8h5.8L32 25.5l-2.9 8.2Z" fill="#fff"/>',
			'<path d="m36.8 20.7 3.6 3.6 7.4-7.5 3.2 3.2-10.6 10.7-6.8-6.8 3.2-3.2Z" fill="#fff"/>',
			'</svg>'
		].join('');
		brandmark.setAttribute('aria-label', 'AssessCraft');
		brandmark.removeAttribute('aria-hidden');

		var svg = brandmark.querySelector('svg');
		if (svg) {
			svg.style.width = '100%';
			svg.style.height = '100%';
			svg.style.display = 'block';
		}
	}

	function setupLicenseKeyToggle() {
		var toggle = document.querySelector('.ac-pro-toggle-key');
		var field = document.getElementById('assesscraft-pro-license-key');

		if (!toggle || !field) return;

		toggle.addEventListener('click', function () {
			var isHidden = field.type === 'password';
			field.type = isHidden ? 'text' : 'password';
			toggle.textContent = isHidden ? toggle.dataset.hideLabel : toggle.dataset.showLabel;
			toggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
			field.focus();
		});
	}

	function setupLicenseFormFeedback() {
		var form = document.querySelector('.ac-pro-activation-form');
		var field = document.getElementById('assesscraft-pro-license-key');
		if (!form || !field) return;

		var submit = form.querySelector('button[type="submit"]');
		var feedback = document.createElement('div');
		feedback.className = 'ac-pro-inline-message is-error ac-pro-client-error';
		feedback.setAttribute('role', 'alert');
		feedback.hidden = true;
		feedback.innerHTML = '<span class="dashicons dashicons-warning" aria-hidden="true"></span><p></p>';
		form.appendChild(feedback);

		field.addEventListener('input', function () {
			field.setCustomValidity('');
			feedback.hidden = true;
		});

		form.addEventListener('submit', function (event) {
			var key = field.value.trim();
			if (!key) {
				event.preventDefault();
				showError('Enter your AssessCraft Pro license key before activating.');
				return;
			}

			if (key.length < 12 || !/^[A-Za-z0-9_-]+$/.test(key)) {
				event.preventDefault();
				showError('The license key format does not look valid. Copy the complete key exactly as provided, without spaces.');
				return;
			}

			field.value = key;
			if (submit) {
				submit.disabled = true;
				submit.dataset.originalText = submit.textContent;
				submit.textContent = 'Activating…';
				submit.setAttribute('aria-busy', 'true');
			}
		});

		window.addEventListener('pageshow', function () {
			if (submit) {
				submit.disabled = false;
				submit.removeAttribute('aria-busy');
				if (submit.dataset.originalText) submit.textContent = submit.dataset.originalText;
			}
		});

		function showError(text) {
			var paragraph = feedback.querySelector('p');
			if (paragraph) paragraph.textContent = text;
			feedback.hidden = false;
			field.setCustomValidity(text);
			field.focus();
			field.reportValidity();
		}
	}
}());
