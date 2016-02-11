<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', 'Preferences');

$this->start('contentBody');
$this->start('toolbar');
	echo $this->Html->link('<i class="fa fa-pencil"></i>', [], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Edit' , 'escape' => false]);	
$this->end();

?>

<?= $this->element('preferences_tabs') ?>

<div class="wrapper panel panel-body">
			<div class="row">
				<div class="col-xs-6 col-md-3 form-label">Username</div>
				<div class="col-xs-6 col-md-6 form-input">administrator</div>
			</div>
			<div class="row">
				<div class="col-xs-6 col-md-3 form-label">First Name</div>
				<div class="col-xs-6 col-md-6 form-input">Administrator</div>
			</div>
			<div class="row">
				<div class="col-xs-6 col-md-3 form-label">Last Name</div>
				<div class="col-xs-6 col-md-6 form-input">User</div>
			</div>
			<div class="row">
				<div class="col-xs-6 col-md-3 form-label">Last Login</div>
				<div class="col-xs-6 col-md-6 form-input">June 17, 2015 16:14:25</div>
			</div>

			<hr>

			<h3>Contact</h3>
			<div class="table-wrapper">
				<div class="table-responsive">
					<table class="table table-curved table-sortable">
						<thead>
							<th>Description</th>
							<th>Value</th>
							<th>Preferred</th>
						</thead>
						<tbody>
							<td></td>
							<td></td>
							<td></td>
						</tbody>
					</table>			
				</div>	
			</div>

			<h3>Groups</h3>
			<div class="table-wrapper">
				<div class="table-responsive">
					<table class="table table-curved table-sortable">
						<thead>
							<th>Group</th>
							<th>Role</th>
						</thead>
						<tbody>
							<td>Ministry of Education</td>
							<td>Administrator</td>
						</tbody>
					</table>			
				</div>
			</div>
		</div>

	</div>
</div>

<?php $this->end() ?>

