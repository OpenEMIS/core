$(document).ready(function() {
	SurveyQuestion.init();
});

var SurveyQuestion = {
	init: function() {
		$('span[move]').click(function() {
			SurveyQuestion.move(this);
		});
	},
	
	move: function(obj) {
		var row = $(obj).closest('tr');
		var form = $('#SurveyQuestionMoveForm');
		$('.option-id').val(row.attr('row-id'));
		$('.option-move').val($(obj).attr('move'));
		if(form.find('input.option-grade-id').length > 0){
			$('.option-grade-id').val(row.attr('grade-id'));
		}
		form.submit();
	}
};