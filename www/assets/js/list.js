import Popover from 'bootstrap/js/dist/popover';

export function register() {
	document.querySelectorAll('[data-bs-content]').forEach((element) => {
		if (element.getAttribute('data-bs-content') !== '') {
			new Popover(element, {
				placement: 'left',
				html: true,
				trigger: 'hover',
				container: 'body',
			});
		}
	});
}
