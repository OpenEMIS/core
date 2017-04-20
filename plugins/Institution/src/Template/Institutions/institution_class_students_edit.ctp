<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/institutionclasses/institution.class.students.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/institutionclasses/institution.class.students.ctrl', ['block' => true]); ?>
<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('panelBody');
?>
<div class="alert {{class}}" ng-hide="message == null">
	<a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
</div>
<form method="post" accept-charset="utf-8" id="content-main-form" class="form-horizontal ng-pristine ng-valid" novalidate="novalidate" action="">
	<div class="input-form-wrapper" ng-controller="InstitutionClassStudentsCtrl as InstitutionClassStudentsController" ng-init="InstitutionClassStudentsController.classId=<?= $classId ?>">
		<!-- <kd-multi-select grid-options-top="InstitutionClassStudentsController.gridOptionsTop" grid-options-bottom="InstitutionClassStudentsController.gridOptionsBottom"></kd-multi-select> -->
	</div>
</form>
<?php
$this->end();
?>
