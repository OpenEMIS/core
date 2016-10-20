<div class="main-content-header">
	<h3><?= __('Notices'); ?></h3>
</div>
<div id="dashboard-notices-table" class="row dashboard-container table-wrapper">
	<div id="news">
		<div class="dashboard-content margin-top-10">
			<table class="table table-lined">
				<tbody class="table_body">
					<tr ng-if="DashboardController.notices && DashboardController.notices.length == 0"><td><?= __('Loading'); ?> ...</td></tr>
					<tr ng-if="!DashboardController.notices"><td><?= __('No Notices'); ?></td></tr>
					<tr ng-repeat="notice in DashboardController.notices | orderBy:'order'"><td>{{notice.message}}</td></tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
