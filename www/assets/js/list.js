import $ from 'jquery';
import 'bootstrap/js/dist/popover';

export function register() {
	$(function() {
		$('[data-content]').popover({
			placement: 'left',
			html: true,
			trigger: 'hover',
			container: 'body'
		});
	});
};
