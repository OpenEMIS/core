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
		$('.workflowtransition-assignee-required').val(jsonObj.assignee_required);
		$('.workflowtransition-comment-required').val(jsonObj.comment_required);
		$('.workflowtransition-event-description').html(jsonObj.event_description);
        var assigneeUrl = $('.workflowtransition-assignee-id').attr('assignee-url');

		Workflow.getAssigneeOptions(assigneeUrl, jsonObj.is_school_based, jsonObj.next_step_id, jsonObj.auto_assign_assignee);
		Workflow.resetError();
		Workflow.toggleAssignee(jsonObj.assignee_required);
		Workflow.toggleComment(jsonObj.comment_required);
	},

	hideError: function() {
		$('.workflowtransition-assignee-loading').hide();
		$('.workflowtransition-assignee-no_options').hide();
		$('.workflowtransition-assignee-error').hide();
		$('.workflowtransition-assignee-sql-error').hide();
		$('.workflowtransition-comment-error').hide();
	},

	resetError: function() {
		$('div').remove('.assignee-error');
		$('.workflowtransition-assignee-id').removeClass('form-error');
		$('.workflowtransition-comment').removeClass('form-error');
	},

	toggleAssignee: function(required) {
		if (required == 0) {
			$('.workflowtransition-assignee-id').closest('.input').hide().removeClass('required');
		} else {
			$('.workflowtransition-assignee-id').closest('.input').show().addClass('required');
		}
	},

	toggleComment: function(required) {
		console.log('toggleComment: ' + required);
		if (required == 0) {
			$('.workflowtransition-comment').closest('.input').removeClass('required');
		} else {
			$('.workflowtransition-comment').closest('.input').addClass('required');
		}
	},

	onSubmit: function(obj) {
		var assigneeRequired = $('.workflowtransition-assignee-required').val();
		var assigneeId = $('.workflowtransition-assignee-id').val();
		var commentRequired = $('.workflowtransition-comment-required').val();
		var comment = $.trim($('.workflowtransition-comment').val());
		Workflow.resetError();

		var error = false;
		if (assigneeRequired == 1 && assigneeId == '') {
			$('.workflowtransition-assignee-id').addClass('form-error');
			$('.workflowtransition-assignee-id').closest('.input-select-wrapper').after('<div class="assignee-error error-message">' + $('.workflowtransition-assignee-error').html() + '</div>');
			error = true;
		}

		if (commentRequired == 1 && comment.length === 0) {
			$('.workflowtransition-comment').addClass('form-error');
			$('.workflowtransition-comment-error').show();
			error = true;
		} else {
			$('.workflowtransition-comment').removeClass('form-error');
			$('.workflowtransition-comment-error').hide();
		}

		if (error) {
			return false;
		} else {
			return true;
		}
	},

	getAssigneeOptions: function(assigneeUrl, isSchoolBased, nextStepId, autoAssignAssignee) {
		var url = assigneeUrl;

		$.ajax({
			url: url,
            dataType: "json",
            data: {
            	is_school_based: isSchoolBased,
                next_step_id: nextStepId,
                auto_assign_assignee: autoAssignAssignee
            },
			beforeSend: function(xhr) {
				// always show loading when user click on submit button
				$('.workflowtransition-assignee-id').empty();
				$('.workflowtransition-assignee-id').append($('<option>').text($('.workflowtransition-assignee-loading').html() + '...').attr('value', ''));
			},
            success: function(response) {
            	var defaultKey = response.default_key;
            	var assignees = response.assignees;

            	$('.workflowtransition-assignee-id').empty();
            	if (jQuery.isEmptyObject(assignees)) {
            		// show No options if assignees is empty
            		$('.workflowtransition-assignee-id').append($('<option>').text(defaultKey).attr('value', ''));
            	} else {
            		$('.workflowtransition-assignee-id').append($('<option>').text(defaultKey).attr('value', ''));
					$.each(assignees, function(i, value) {
						$('.workflowtransition-assignee-id').append($('<option>').text(value).attr('value', i));
					});
            	}
            },
            error: function(error) {
            	console.log('Workflow.getAssigneeOptions() error callback:');
            	console.log(error);
            	$('.workflowtransition-assignee-id').empty();
            	$('.workflowtransition-assignee-id').append($('<option>').text($('.workflowtransition-assignee-no_options').html()).attr('value', ''));

            	if (typeof error.responseJSON != 'undefined' && typeof error.responseJSON.message != 'undefined') {
            		$('.workflowtransition-assignee-sql-error').show();
            	}
            }
        });
	}
};
