$(function(){
	var conditionalFields = [];
	$('[data-applicable-categories]').each(function() {
		conditionalFields.push([
			$(this).parents('.form-group'),
			JSON.parse(this.getAttribute('data-applicable-categories'))
		]);
	});

	var categoryField = $('#frm-teamForm-category');

	function categoryChanged() {
		var category = categoryField.val();
		$.each(conditionalFields, function() {
			if (this[1].indexOf(category) === -1) {
				$(this[0]).hide();
			} else {
				$(this[0]).show();
			}
		});
	};

	categoryField.change(categoryChanged);
	categoryChanged();
});
