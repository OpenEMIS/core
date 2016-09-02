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
		$('.workflowtransition-assignee-id').val('');
		$('.workflowtransition-comment').val('');
		$('.workflowtransition-action-id').val(jsonObj.id);
		$('.workflowtransition-action-name').val(jsonObj.name);
		$('.workflowtransition-action-description').val(jsonObj.description);
		$('.workflowtransition-step-id').val(jsonObj.next_step_id);
		$('.workflowtransition-step-name').val(jsonObj.next_step_name);
		$('.workflowtransition-comment-required').val(jsonObj.comment_required);
		$('.workflowtransition-event-description').html(jsonObj.event_description);

		Workflow.getAssigneeOptions(jsonObj.next_step_id);
	},

	hideError: function() {
		$('.workflowtransition-comment-error').hide();
		$('.workflowtransition-assignee-error').hide();
		$('.workflowtransition-assignee-sql-error').hide().html('');
	},
	
	onSubmit: function(obj) {
		var assigneeId = $('.workflowtransition-assignee-id').val();
		var required = $('.workflowtransition-comment-required').val();
		var comment = $.trim($('.workflowtransition-comment').val());

		var error = false;
		if (assigneeId == '') {
			$('.workflowtransition-assignee-error').show();
			error = true;
		} else {
			$('.workflowtransition-assignee-error').hide();
		}

		if (required == 1 && comment.length === 0) {
			$('.workflowtransition-comment-error').show();
			error = true;
		} else {
			$('.workflowtransition-comment-error').hide();
		}

		if (error) {
			return false;
		} else {
			return true;
		}
	},

	getAssigneeOptions: function(nextStepId) {
		var url = '/core/Workflows/ajaxGetAssignees';

		$.ajax({
			url: url,
            dataType: "json",
            data: {
                next_step_id: nextStepId
            },
			beforeSend: function(xhr) {
				// always show loading when user click on submit button
				$('.workflowtransition-assignee-id').empty();
				$('.workflowtransition-assignee-id').append($('<option>').text('Loading...').attr('value', ''));
			},
            success: function(response) {
            	console.log('Workflow.getAssigneeOptions() success callback:');
            	console.log(response);

            	var defaultKey = response.default_key;
            	var assignees = response.assignees;

            	$('.workflowtransition-assignee-id').empty();
            	if (jQuery.isEmptyObject(assignees)) {
            		// show No options if assignees is empty
            		$('.workflowtransition-assignee-id').append($('<option>').text(defaultKey).attr('value', ''));
            	} else {
            		$('.workflowtransition-assignee-id').append($('<option>').text(defaultKey).attr('value', ''));
					$.each(assignees, function(i, value) {
						$('.workflowtransition-assignee-id').append($('<option>').text(value).attr('value', value));
					});
            	}
            },
            error: function(error) {
            	console.log('Workflow.getAssigneeOptions() error callback:');
            	console.log(error);
            	$('.workflowtransition-assignee-id').empty();
            	$('.workflowtransition-assignee-id').append($('<option>').text('No options').attr('value', ''));

            	if (typeof error.responseJSON != 'undefined' && typeof error.responseJSON.message != 'undefined') {
            		$('.workflowtransition-assignee-sql-error').html(error.responseJSON.message).show();
            	}
            }
        });
	}
};
