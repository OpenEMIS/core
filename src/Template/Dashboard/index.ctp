<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('angular/dashboard/dashboard.ctrl', ['block' => true]); ?>
<?= $this->Html->script('angular/dashboard/dashboard.svc', ['block' => true]); ?>

<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('panelBody');
?>
	<bg-splitter orientation="horizontal" class="content-splitter" collapse="{{DashboardController.collapse}}" elements="getSplitterElements" float-btn="false">
		<bg-pane class="main-content">
			<?= $this->element('Dashboard/notices'); ?>
			<hr>
			<?= $this->element('Dashboard/workbench'); ?>
		</bg-pane>

		<!-- With Buttons -->
		<bg-pane class="split-content splitter-slide-out split-with-btn" min-size-p="20" max-size-p="80" size-p="70">
			<div class="split-content-header">
				<h3>{{DashboardController.workbenchTitle}}</h3>
				<div class="split-content-btn">
					<button href="#" class="btn btn-outline" ng-click="DashboardController.removeSplitContentResponsive()">
						<i class="fa fa-close fa-lg"></i>
					</button>
				</div>
			</div>
			<div class="split-content-area">
				<div class="html-box">
					<div id="dashboard-workbench-table" class="table-wrapper">
						<div ng-if="DashboardController.gridOptions['workbench']" ag-grid="DashboardController.gridOptions['workbench']" class="sg-theme"></div>
					</div>
				</div>
			</div>
		</bg-pane>
	</bg-splitter>
<?php
$this->end();
?>
