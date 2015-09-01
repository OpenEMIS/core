/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

$(document).ready(function() {
	Workflow.init();

	$(".workflowtransition-comment").keyup(function() {
		Workflow.hideError();
	});
});

var Workflow = {
	init: function() {
		Workflow.hideError();
	},

	copy: function(jsonObj) {
		$('.workflowtransition-action-id').val(jsonObj.id);
		$('.workflowtransition-action-name').val(jsonObj.name);
		$('.workflowtransition-step-id').val(jsonObj.next_step_id);
		$('.workflowtransition-comment-required').val(jsonObj.comment_required);
	},

	showError: function() {
		$('.workflowtransition-comment-error').show();
	},

	hideError: function() {
		$('.workflowtransition-comment-error').hide();
	},
	
	onSubmit: function(obj) {
		var required = $('.workflowtransition-comment-required').val();
		var comment = $.trim($('.workflowtransition-comment').val());

		if (required == 1 && comment.length === 0) {
			Workflow.showError();
			return false;
		} else {
			return true;
		}
	}
};
