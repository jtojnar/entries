export function register() {
	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('.change-form-submit').forEach((element) => {
			element.addEventListener('change', (event) => {
				event.target.form.submit();
			});
		});
	});
}
