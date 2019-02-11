import netteForms from 'nette-forms';

export function register() {
	document.querySelectorAll('[data-form-warning-rules]').forEach(el => {
		el.addEventListener('blur', event => {
			netteForms.formErrors = [];

			const rules = JSON.parse(el.getAttribute('data-form-warning-rules'));

			const warningless = netteForms.validateControl(el, rules);

			if (!el.warningContainer) {
				el.warningContainer = document.createElement('ul');
				el.warningContainer.classList.add('warning-feedback');
				el.warningContainer.classList.add('list-unstyled');
				el.warningContainer.setAttribute('id', el.getAttribute('id') + '-warning');
				el.parentNode.insertBefore(el.warningContainer, el.nextSibling);
			}

			if (!warningless) {
				el.warningContainer.textContent = '';
				netteForms.formErrors.forEach(error => {
					let errorWrapper = document.createElement('li');
					errorWrapper.textContent = error.message;
					el.warningContainer.appendChild(errorWrapper);
				});
			}

			el.classList.toggle('is-warning', !warningless);

			// Only attach one input listener
			if (!el.inputNotFired) {
				el.inputNotFired = true;
				el.addEventListener('input', event => {
					el.classList.remove('is-warning');
					el.inputNotFired = false;
				}, {
					once: true,
				});
			}
		});
	});
};
