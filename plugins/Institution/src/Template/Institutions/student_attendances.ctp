<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/student_attendances/institution.student.attendances.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/student_attendances/institution.student.attendances.ctrl', ['block' => true]); ?>

<?php
$this->start('toolbar');
?>

<button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Export');?>" ng-show="$ctrl.action == 'view'">
    <i class="fa kd-export"></i>
</button>

<button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Import');?>" ng-show="$ctrl.action == 'view'">
    <i class="fa kd-import"></i>
</button>

<button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Edit');?>" ng-show="$ctrl.action == 'view'" ng-click="$ctrl.onEditClick()">
    <i class="fa kd-edit"></i>
</button>

<button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Back');?>" ng-show="$ctrl.action == 'edit'" ng-click="$ctrl.onBackClick()">
    <i class="fa kd-back"></i>
</button>

<?php
$this->end();
?>

<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model._content_header")));

$this->start('contentBody');
$panelHeader = $this->fetch('panelHeader');
?>

<?= $this->element('OpenEmis.alert') ?>

<style>
.ag-cell textarea {
	display: inline-block;
    padding: 5px 10px;
    margin-bottom: 15px;
    -webkit-border-radius: 3px;
    border-radius: 3px;
    font-size: 12px;
    height: 70px;
    width: 100%;
    border: 1px solid #CCC;
}

.ag-cell .input-select-wrapper select {
	background: #FFFFFF;
	display: block;
}
</style>

<div class="panel">
	<div class="panel-body" style="position: relative;">
		<bg-splitter orientation="horizontal" class="content-splitter" elements="getSplitterElements" ng-init="$ctrl.institutionId=<?= $institution_id ?>" float-btn="false">
			<bg-pane class="main-content">
				<div class="alert {{class}}" ng-hide="message == null">
			        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
			    </div>

				<div class="overview-box alert" ng-class="disableElement">
					<a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
					<div class="data-section">
						<i class="kd-students icon"></i>
						<div class="data-field">
							<h4><?= __('Total Students') ?>:</h4>
							<h1 class="data-header">Over 9000</h1>
						</div>
					</div>
						<div class="data-section">
						<div class="data-field">
							<h4><?= __('No. of Students Present') ?></h4>	
							<h1 class="data-header">Over 9000</h1>
						</div>
					</div>
						<div class="data-section">
						<div class="data-field">
							<h4><?= __('No. of Students Absent') ?></h4>	
							<h1 class="data-header">Over 9000</h1>
						</div>
					</div>
					<div class="data-section">
						<div class="data-field">
							<h4><?= __('No. of Students Late') ?></h4>	
							<h1 class="data-header">Over 9000</h1>
						</div>
					</div>
				</div>
				<div id="institution-student-attendances-table" class="table-wrapper">
				    <div ng-if="$ctrl.gridReady" kd-ag-grid="$ctrl.gridOptions" has-tabs="true" class="ag-height-fixed"></div>
				</div>
			</bg-pane>

			<!-- With Buttons -->
			<bg-pane class="split-content splitter-slide-out" min-size-p="20" max-size-p="20" size-p="20">
				<div class="split-content-header" style="margin-bottom: 15px;">
					<h3>Filter</h3>
				</div>
				<div class="split-content-area">
					<h5><?= __('Academic Period') ?>: </h5>
					<div class="input-select-wrapper">
                		<select ng-disabled="$ctrl.action=='edit'" name="academic_period" ng-options="period.id as period.name for period in $ctrl.academicPeriodOptions" ng-model="$ctrl.selectedAcademicPeriod" ng-change="$ctrl.changeAcademicPeriod();">
                			<option value="" ng-if="$ctrl.academicPeriodOptions.length == 0"><?= __('No Options') ?></option>
						</select>
					</div>
					<h5><?= __('Week') ?>: </h5>
					<div class="input-select-wrapper">
						<select ng-disabled="$ctrl.action=='edit'" name="week" ng-options="week.id as week.name for week in $ctrl.weekListOptions" ng-model="$ctrl.selectedWeek" ng-change="$ctrl.changeWeek();">
                			<option value="" ng-if="$ctrl.weekListOptions.length == 0"><?= __('No Options') ?></option>
						</select>
					</div>
					<h5><?= __('Day') ?>: </h5>
					<div class="input-select-wrapper">
						<select ng-disabled="$ctrl.action=='edit'" name="day" ng-options="day.date as day.name for day in $ctrl.dayListOptions" ng-model="$ctrl.selectedDay" ng-change="$ctrl.changeDay();">
                			<option value="" ng-if="$ctrl.dayListOptions.length == 0"><?= __('No Options') ?></option>
						</select>
					</div>
					<h5><?= __('Class') ?>: </h5>
					<div class="input-select-wrapper">
						<select ng-disabled="$ctrl.action=='edit'" name="class" ng-options="class.id as class.name for class in $ctrl.classListOptions" ng-model="$ctrl.selectedClass" ng-change="$ctrl.changeClass();">
                			<option value="" ng-if="$ctrl.classListOptions.length == 0"><?= __('No Options') ?></option>
						</select>
					</div>
					<h5><?= __('Attendance per day') ?>: </h5>
					<div class="input">
						<div class="input-selection" style="width: 100%;">
							<div class="input" ng-repeat="attendance_period in $ctrl.attendancePeriodOptions">
								<input kd-checkbox-radio="{{attendance_period.name}}" ng-model="$ctrl.selectedAttendancePeriod" ng-change="$ctrl.changeAttendancePeriod();" value="{{attendance_period.id}}" type="radio" name="attendance_per_day">
							</div>
						</div>
					</div>	
				</div>
			</bg-pane>
		</bg-splitter>
	</div>
</div>


<?php
$this->end();
?>
