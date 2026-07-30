(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var toggle = document.querySelector('.ac-pro-toggle-key');
		var field = document.getElementById('assesscraft-pro-license-key');

		if (!toggle || !field) {
			return;
		}

		toggle.addEventListener('click', function () {
			var isHidden = field.type === 'password';
			field.type = isHidden ? 'text' : 'password';
			toggle.textContent = isHidden ? toggle.dataset.hideLabel : toggle.dataset.showLabel;
			field.focus();
		});
	});
}());
