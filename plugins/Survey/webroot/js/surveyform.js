$( document ).ready( function() {
	SurveyForm.updateSection();
	SurveyForm.changeTextBoxBehavior('#sectionTxt');
	SurveyForm.populateOptions();
});


var SurveyForm = {
	addSection: function(objClassId){

		var sectionName = $(objClassId).val();
		if (sectionName !="") {
			sectionName = SurveyForm.checkSectionExist(sectionName);
			var prependHTML = "<tr>";
			prependHTML += "<td>";
			prependHTML += "<div class=section-header>" + sectionName +"</div>";
			prependHTML += "</td>";
			prependHTML += "<td><button onclick='jsTable.doRemove(this); SurveyForm.updateSection();' aria-expanded='true' type='button' class='btn btn-dropdown action-toggle btn-single-action'><i class='fa fa-trash'></i>&nbsp;<span>Delete</span></button></td>";
			prependHTML += "<td class='sorter rowlink-skip' onmousedown='Reorder.enableSortable(this);'><div class='reorder-icon'><a><i class='fa fa-arrows-alt'></i></a></div></td>";
			prependHTML += "</td>";
			prependHTML += "</tr>";
			$('#sortable').find('tbody').first().prepend(prependHTML);
			SurveyForm.updateSection();
			//SurveyForm.populateOptions();
		}
	},

	populateOptions: function(){
		$('#sectionDropdown').html('');
		$('#sectionDropdown').append($('<option></option>').val('').html('-- Select a Section --'));
		$('#sortable').find('.section-header').each(function(){
			var sectionName = $(this).html();
			$('#sectionDropdown').append($('<option></option>').val(sectionName).html(sectionName));
		});
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

	checkSectionExist: function(sName){
		var found = false;
		var sLength = sName.length;
		$('#sortable').find('.section-header').each(function(){
			if ($(this).text() == sName) {
				found = true;
			}
		});
		if (! found) {
			return sName;
		} else {
			if (sLength + 7 > 250) {
				sName = sName.substr(0, sLength - 7);
			}
			sName = "Copy - " + sName;
			return SurveyForm.checkSectionExist(sName);
		}
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