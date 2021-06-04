import selectParent from 'select-parent';

function categoryChanged(categoryField, conditionalFields) {
	const category = categoryField.value;
	conditionalFields.forEach(({formGroup, categories}) => {
		formGroup.classList.toggle('d-none', !categories.includes(category));
	});
}

export function register() {
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
}
