import selectParent from 'select-parent';

export const PRESUBMIT_EVENT = 'nette-presubmit';

function categoryChanged(categoryField, conditionalFields) {
	const category = categoryField.value;
	conditionalFields.forEach(({formGroup, categories}) => {
		formGroup.classList.toggle('d-none', !categories.includes(category));
	});
}

export function register(Nette) {
	document.addEventListener('DOMContentLoaded', (event) => {
		const categoryField = document.getElementById('frm-teamForm-category');

		if (categoryField) {
			let conditionalFields = Array.from(document.querySelectorAll('[data-applicable-categories]')).map((control) => ({
				formGroup: selectParent('.form-group', control),
				categories: JSON.parse(control.getAttribute('data-applicable-categories'))
			}));

			categoryField.addEventListener('change', () => {
				categoryChanged(categoryField, conditionalFields);
			});
			categoryChanged(categoryField, conditionalFields);
		}
	});

	// Allow attaching callbacks to run before form validation.
	const originalValidateForm = Nette.validateForm;
	Nette.validateForm = function(sender, onlyCheck) {
		const form = sender.form || sender
		form.dispatchEvent(new Event(PRESUBMIT_EVENT));
		return originalValidateForm(sender, onlyCheck);
	};
}
