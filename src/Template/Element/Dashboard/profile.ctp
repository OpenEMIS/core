<h3><?= __('Profile Completness'); ?></h3>
<div class="overview-box alert attendance-dashboard ng-scope" ng-class="disableElement">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
	<div class="data-section single-day">
		<i class="kd-staff icon"></i>
		<div class="data-field">
			<h4>Profile Complete:</h4>
			<h1 class="data-header ng-binding">75%</h1>
		</div>
	</div>
	<div class="data-section">
		<div class="data-field">
			
		</div>
	</div>
	<div class="data-section">
		<div class="data-field">
			<div class="progress">
				<div class="progress-bar" role="progressbar" aria-valuenow="70"
				aria-valuemin="0" aria-valuemax="100" style="width:70%">
				70%
				</div>
			</div>
		</div>
	</div>
	<div class="data-section">
		<div class="data-field">
			<button href="#" class="btn btn-primary" ng-click="DashboardController.showProfileCompleteData()">
				Details
			</button>
		</div>
	</div>
</div>
