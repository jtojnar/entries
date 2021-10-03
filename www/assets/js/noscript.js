export function register() {
	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('.noscript').forEach((element) => {
			element.classList.add('visually-hidden');
		});
	});
}
