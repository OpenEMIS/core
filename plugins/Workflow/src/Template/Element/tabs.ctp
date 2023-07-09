<?php 
//echo "<pre>";print_r($attr['transitions']);die;
	echo $this->Html->script('Workflow.workflow', ['block' => true]);
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
	<?php
		$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
		$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];

		$tableHeaderComments = isset($attr['tableHeaderComments']) ? $attr['tableHeaderComments'] : [];
		$tableCellComments = isset($attr['tableCellComments']) ? $attr['tableCellComments'] : [];
		$linkCells = isset($attr['linkCells']) ? $attr['linkCells'] : [];
		$transitions = isset($attr['transitions']) ? $attr['transitions'] : [];
	?>


    <div id="tabs" class="nav nav-tabs horizontal-tabs scroll_tabs_container">
        <div class="scroll_tab_left_button" style="position: absolute; left: 0px; top: 0px; width: 26px; cursor: pointer; display: none;"></div>
        <div class="scroll_tab_inner" style="margin: 0px; overflow: hidden; white-space: nowrap; text-overflow: clip; font-size: 0px; position: absolute; top: 0px; left: 0px; right: 0px;">
            <span class="scroll_tab_left_finisher" style="display: inline-block; zoom: 1; user-select: none;">&nbsp;</span>
					<span role="presentation" id="Tab1" class="tab-active scroll_tab_first TabButton" style="display: inline-block; zoom: 1; user-select: none;"><a href="#" onclick="activeTab(event, 'Tab1')">Comments</a></span>
					<span role="presentation" id="Tab2" class="TabButton" style="display: inline-block; zoom: 1; user-select: none;"><a href="#" onclick="activeTab(event, 'Tab2')">Transitions</a></span>
					<span role="presentation" id="Tab3" class="TabButton" style="display: inline-block; zoom: 1; user-select: none;"><a href="#"onclick="activeTab(event, 'Tab3')">Links</a></span>
					
			<span class="scroll_tab_right_finisher" style="display: inline-block; zoom: 1; user-select: none;">&nbsp;</span>
        </div>
        <div class="scroll_tab_right_button" style="position: absolute; right: 0px; top: 0px; width: 26px; cursor: pointer; display: none;"></div>
    </div>

	
	
	<div class="d-flex-col">
		<div id="SectionTab1" class="tab-section d-chart-n d-chart-show" style="display:block"> 
			<table class="table table-curved Output-details">
			<thead><?= $this->Html->tableHeaders($tableHeaderComments) ?></thead>
					<tbody>
						<?php foreach($transitions as $k=>$trans){ ?>
							<tr>
								<td><?= $trans->comment ?></td>
								<td><?= $trans->created_user->name ?></td>
								<td><?= $trans->created->format('Y-m-d H:i:s') ?></td>
								<td class="rowlink-skip">
									<div class="dropdown">
										<button class="btn btn-dropdown action-toggle" type="button" id="action-menu" data-toggle="dropdown" aria-expanded="true">
											Select<span class="caret-down"></span>
										</button>
										<ul class="dropdown-menu action-dropdown" role="menu" aria-labelledby="action-menu">
											<div class="dropdown-arrow"><i class="fa fa-caret-up"></i></div>

											<li role="presentation">
												<a href="" role="menuitem" tabindex="-1" onclick="EditComment(<?= $trans->id ?>)" ><i class="fa fa-eye"></i>Edit</a>			
											</li>
											<li role="presentation">
												<a href="#"  onclick="DeleteCase(<?= $trans->id ?>)"><i class="fa fa-trash"></i>Delete</a>			
											</li>
											
										</ul>
									</div>
								</td>
							</tr>
						<?php } ?>

				
				</tbody>
			</table>
		</div>
		
		<div id="SectionTab2" class="tab-section d-chart-n" style="display:none"> 
		<div class="table-wrapper">
			<div class="table-responsive">
				<table class="table table-curved Output-details">
					<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
					<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
				</table>
			</div>
		</div>
		</div>
		
		<div id="SectionTab3" class="tab-section d-chart-n" style="display:none"> 
			<table class="table table-curved Output-details">
					<thead><tr><th>Case Number</th> <th>Title</th> <th>Status</th> </tr></thead>
					<tbody><?= $this->Html->tableCells($linkCells) ?></tbody>
			</table>
		</div>
		
	</div>

</div>
<?php endif ?>


<div class="modal fade" id="largeModal" tabindex="-1" role="dialog" aria-labelledby="largeModal" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title" id="myModalLabel">Case comment</h4>
          </div>
		  <div class="modal-body">
			<form name="frm" method="POST" action="">
				<!-- <input type="hidden" name="WorkflowTransitions[comment_required]" class="workflowtransition-comment-required" id="workflowtransitions-comment-required" value="0"> -->
				<div class="input textarea">
					<label for="workflowtransitions-comment">Comment</label>
					<textarea name="WorkflowTransitions[comment]" class="workflowtransition-comment" name="name" id="name" rows="5"></textarea>
				</div>
				<div class="modal-footer">
					
					<button type="submit" name="Update" id="update" value="Update" class="btn btn-primary">Update</button>
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				</div>
			</form>
						</div>
        </div>
      </div>
    </div>

<script>
	function activeTab(evt, id) {
			
	// Get all elements with class="tablinks" and remove the class "active"
		let tabactive = document.getElementsByClassName("tab-section");
		tabactive[0].className = tabactive[0].className.replace(" tab-active", "");
		
		if(id == 'Tab1'){
			document.getElementById('Tab2').classList.remove("tab-active");
			document.getElementById('Tab3').classList.remove("tab-active");
		}	
		if(id == 'Tab2'){
			document.getElementById('Tab1').classList.remove("tab-active");
			document.getElementById('Tab3').classList.remove("tab-active");
		}	
		if(id == 'Tab3'){
			document.getElementById('Tab1').classList.remove("tab-active");
			document.getElementById('Tab2').classList.remove("tab-active");
		}	


		document.getElementById(id).className = "tab-active";
		evt.currentTarget.className += " tab-active";

		displaySection(evt,id)
	}
			
	function displaySection(evt, id) {

		let tabactive = document.getElementsByClassName("tab-section");
		tabactive[0].className = tabactive[0].className.replace(" d-chart-show", "d-chart-n");
		// add below line of codes
		[...document.querySelectorAll('div.tab-section')].forEach(item => item.style.display='none',)
		document.getElementById("Section" + id).style.display = "block";
		evt.currentTarget.className += " d-chart-show";

	}

	function DeleteCase(caseId){
		var url = '/Workflows/ajaxDelCase';
		$.ajax({
			url: url,
			dataType: "json",
			data: {
				caseId: caseId
			},
			beforeSend: function(xhr) {
				// always show loading when user click on submit button
				$('.workflowtransition-assignee-id').empty();
				$('.workflowtransition-assignee-id').append($('<option>').text($('.workflowtransition-assignee-loading').html() + '...').attr('value', ''));
			},
			success: function(response) {
				var defaultKey = response.default_key;
				//alert(defaultKey);
				if (defaultKey == 'success') {
					location.reload();
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


	function EditComment(caseId){
		//alert("edit here");
		$('#largeModal').modal('show');
		var url = '/Workflows/ajaxGetComment';
		$.ajax({
			url: url,
			dataType: "json",
			data: {
				caseId: caseId
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
					if (defaultKey.length != 0) {
						$('.workflowtransition-assignee-id').append($('<option>').text(defaultKey).attr('value', ''));
					}
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
    
	$("#update").click(function(e) {
		e.preventDefault();
		var url = '/Workflows/ajaxUpdateComment';
		var name = $("#name").val(); 
		var last_name = $("#last_name").val();
		var dataString = 'name='+name+'&last_name='+last_name;
		$.ajax({
			url: url,
			dataType: "json",
			data: {
				name: name,
				last_name: last_name
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
					if (defaultKey.length != 0) {
						$('.workflowtransition-assignee-id').append($('<option>').text(defaultKey).attr('value', ''));
					}
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
	});
</script>


