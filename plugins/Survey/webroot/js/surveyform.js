$( document ).ready( function() {
	SurveyForm.updateSection();
	SurveyForm.changeTextBoxBehavior('#sectionTxt');
});


var SurveyForm = {
	addSection: function(objClassId){
		var sectionName = $(objClassId).val();
		var prependHTML = "<tr>";
		prependHTML += "<td>";
		prependHTML += "<div class=section-header>" + sectionName +"</div>";
		prependHTML += "</td>";
		prependHTML += "<td><button onclick='jsTable.doRemoveWithAppend(this)' aria-expanded='true' type='button' class='btn btn-dropdown action-toggle btn-single-action'><i class='fa fa-trash'></i>&nbsp;<span>Delete</span></button></td>";
		prependHTML += "<td class='sorter rowlink-skip'><div class='reorder-icon'><i class='fa fa-arrows-alt'></i></div></td>";
		prependHTML += "</td>";
		prependHTML += "</tr>";
		$('#sortable').find('tbody').first().prepend(prependHTML);
		SurveyForm.updateSection();
	},

	updateSection: function(){
		var count = 0;
		var found = false;
		var sectionName = "";
		$('#sortable').find('tr').each(function(){
			if (! count++ == 0) {
				$(this).children().each(function(){
					if ($(this).children().hasClass('section-header')) {
						sectionName = $(this).children().text();
					}
					$(this).children('.section').val(sectionName);
				});
			}
		});	
	}, 

	changeTextBoxBehavior: function(objClassId){
		$( document ).on("keypress", objClassId, function(e){
			if (utility.getKeyPressed(e) == 13) {
				SurveyForm.addSection(objClassId);
				e.preventDefault();
				return false;
			}
		});
	}
};