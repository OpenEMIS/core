<div class="main-content-header">
	<h3><?= __('Workbench'); ?></h3>
</div>
<div id="dashboard-workbench-item-table" class="row dashboard-container table-wrapper">
	<div id="workbench">
		<div class="dashboard-content margin-top-10">
			<table class="table table-lined">
				<tbody class="table_body">
					<tr ng-if="DashboardController.workbenchItems && DashboardController.workbenchItems.length == 0"><td><?= __('Loading'); ?> ...</td></tr>
					<tr ng-if="!DashboardController.workbenchItems"><td><?= __('No Workbench Data'); ?></td></tr>
				</tbody>
			</table>
			<div ng-if="DashboardController.workbenchItems && DashboardController.workbenchItems.length > 0">
				<ul class="list-group">
					<li class="list-group-item" ng-show="item.total > 0" ng-repeat="item in DashboardController.workbenchItems | orderBy:'order'" ng-click="DashboardController.onChangeModel(item)" style="cursor: pointer;"><span class="badge btn-red">{{item.total}}</span> {{item.name}}</li>
				</ul>
			</div>
		</div>
	</div>
</div>
