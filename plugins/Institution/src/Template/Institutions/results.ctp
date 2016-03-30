<?= $this->Html->script('app/components/institution/result/toolbar.controller', ['block' => true]); ?>
<?= $this->Html->script('app/components/institution/result/service', ['block' => true]); ?>
<?= $this->Html->script('app/components/institution/result/controller', ['block' => true]); ?>

<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
?>
	<div ng-controller="ToolbarCtrl">
	<?php
		$backUrl = [
			'plugin' => $this->request->params['plugin'],
		    'controller' => $this->request->params['controller'],
		    'action' => 'Assessments',
		    'index'
		];
		echo $this->Html->link('<i class="fa kd-back"></i>', $backUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('Back'), 'escape' => false]);
		// echo '<button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title=' . __('Edit') . ' ng-show="!editMode" ng-click="onEditClick()"><i class="fa kd-edit"></i></button>';
		// echo '<button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title=' . __('Save') . ' ng-show="editMode" ng-click="onSaveClick()"><i class="fa fa-save"></i></button>';
		?>
	</div>
<?php
$this->end();

$this->start('panelBody');
$session = $this->request->session();
$institutionId = $session->read('Institution.Institutions.id');
?>

	<div ng-controller="ResultController" ng-init="institution_id=<?= $institutionId; ?>">
		<div class="scrolltabs sticky-content">
			<scrollable-tabset show-tooltips="false" show-drop-down="false">
				<uib-tabset justified="true">
					<uib-tab heading="{{subject.name}}" ng-repeat="subject in subjects" ng-click="reloadData(subject)">
					</uib-tab>
				</uib-tabset>
				<div class="tabs-divider"></div>
			</scrollable-tabset>

			<div class="table-wrapper">
				<div ng-if="gridOptions" ag-grid="gridOptions" class="ag-fresh ag-height-fixed"></div>
			</div>
		</div>
	</div>

<?php
$this->end();
?>
