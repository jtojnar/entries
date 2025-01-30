export function register() {
	document.addEventListener('DOMContentLoaded', (event) => {
		const forms = document.querySelectorAll('.smart-checkboxes');

		forms.forEach((form) => {
			let lastCheckbox = null;
			const checkboxes = Array.from(
				form.querySelectorAll(
					'input[type="checkbox"]:not(.toggle-all)',
				),
			);

			// Select consecutive when clicking with shift key.
			checkboxes.forEach((checkbox, idx) => {
				checkbox.addEventListener('click', (event) => {
					if (event.shiftKey && lastCheckbox !== null) {
						let left = lastCheckbox;
						let right = idx;
						if (left > right) {
							[left, right] = [right, left];
						}
						checkboxes.slice(left, right + 1).forEach((box) => {
							box.checked = checkbox.checked;
						});
					}
					lastCheckbox = idx;
				});
			});

			const toggleAll = form.querySelector(
				'input[type="checkbox"].toggle-all',
			);
			if (toggleAll) {
				// Toggle all checkboxes.
				toggleAll.addEventListener('change', (event) => {
					checkboxes.forEach((box) => {
						box.checked = toggleAll.checked;
					});
				});
			}
		});
	});
}
