import $ from 'jquery';

function categoryChanged(categoryField, conditionalFields) {
	const category = categoryField.val();
	conditionalFields.forEach(([formGroup, categories]) => {
		if (categories.includes(category)) {
			$(formGroup).show();
		} else {
			$(formGroup).hide();
		}
	});
};

export function register() {
	$(function() {
		var categoryField = $('#frm-teamForm-category');

		if (categoryField) {
			var conditionalFields = [];
			$('[data-applicable-categories]').each(function() {
				conditionalFields.push([
					$(this).parents('.form-group'),
					JSON.parse(this.getAttribute('data-applicable-categories'))
				]);
			});

			categoryField.change(() => {
				categoryChanged(categoryField, conditionalFields);
			});
			categoryChanged(categoryField, conditionalFields);
		}
	});
};
