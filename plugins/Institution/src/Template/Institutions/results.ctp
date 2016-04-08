<?= $this->Html->script('app/components/institution/result/toolbar.controller', ['block' => true]); ?>
<?= $this->Html->script('app/components/alert/service', ['block' => true]); ?>
<?= $this->Html->script('app/components/alert/controller', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/results/institutions.results.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/results/institutions.results.ctrl', ['block' => true]); ?>

<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
?>
	<div ng-controller="ToolbarCtrl">
		<!-- Show buttons when action is view: -->
		<?php
			$backUrl = [
				'plugin' => $this->request->params['plugin'],
			    'controller' => $this->request->params['controller'],
			    'action' => 'Assessments',
			    'index'
			];
			echo $this->Html->link('<i class="fa kd-back"></i>', $backUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('Back'), 'escape' => false, 'ng-show' => 'action == \'view\'']);
		?>
		<button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Edit');?>" ng-show="action == 'view'" ng-click="onEditClick()">
			<i class="fa kd-edit"></i>
		</button>
		<!-- End -->

		<!-- Show buttons when action is edit: -->
		<button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Back');?>" ng-show="action == 'edit'" ng-click="onBackClick()">
			<i class="fa kd-back"></i>
		</button>
		<button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Save');?>" ng-show="action == 'edit'" ng-click="onSaveClick()">
			<i class="fa fa-save"></i>
		</button>
		<!-- End -->
	</div>
<?php
$this->end();

$this->start('panelBody');
$session = $this->request->session();
$institutionId = $session->read('Institution.Institutions.id');
?>
	<div ng-controller="AlertCtrl">
		<div class="alert {{class}}" ng-hide="message == null">
			<a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
		</div>
	</div>

	<div ng-init="institution_id=<?= $institutionId; ?>">
		<div class="scrolltabs sticky-content">
			<scrollable-tabset show-tooltips="false" show-drop-down="false">
				<uib-tabset justified="true">
					<uib-tab heading="{{subject.name}}" ng-repeat="subject in subjects" ng-click="reloadRowData(subject)">
					</uib-tab>
				</uib-tabset>
				<div class="tabs-divider"></div>
			</scrollable-tabset>

			<div id="institution-result-table" class="table-wrapper">
				<div ng-if="gridOptions" ag-grid="gridOptions" class="ag-fresh ag-height-fixed"></div>
			</div>
		</div>
	</div>

<?php
$this->end();
?>
