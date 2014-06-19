// JavaScript Document
$(document).ready(function() {
	rubricsCriteriaForms.init();
});

var rubricsCriteriaForms = {
	init: function (){
		$('#rubrics-template .icon_plus').click(function (){rubricsCriteriaForms.add(this)});
	},
	add: function(obj) {
		alert('asdasd');
	}
};