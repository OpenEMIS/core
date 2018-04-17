<h3><?= __('Workbench'); ?></h3>
<div class="row dashboard-container">
	<div id="workbench">
		<div class="dashboard-content margin-top-10">
			<table class="table table-lined" ng-show="(DashboardController.workbenchItems && DashboardController.workbenchItems.length == 0) || (!DashboardController.workbenchItems)">
				<tbody class="table_body">
					<tr ng-if="!DashboardController.workbenchItems" ng-cloak><td><?= __('No Workbench Data'); ?></td></tr>
					<tr ng-if="DashboardController.workbenchItems && DashboardController.workbenchItems.length == 0"><td><?= __('Loading'); ?> ...</td></tr>
				</tbody>
			</table>
			<div ng-if="DashboardController.workbenchItems && DashboardController.workbenchItems.length > 0" ng-cloak>
				<ul class="list-group">
					<li class="list-group-item" ng-show="item.total > 0" ng-repeat="item in DashboardController.workbenchItems | orderBy:'order'" ng-click="DashboardController.onChangeModel(item)">
						<div class="list-icon">
							<i class="fa fa-table"></i>
							<div class="badge btn-red badge-right">{{item.total}}</div>
						</div>
						<div class="list-text">
							<p>{{item.name}}</p>
						</div>
						<i class="chervon"></i>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>