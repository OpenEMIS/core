<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Student.angular/results/student.results.svc', ['block' => true]); ?>
<?= $this->Html->script('Student.angular/results/student.results.ctrl', ['block' => true]); ?>
<?php
$this->start('toolbar');
?>
<?php if ($_archive) : ?>
    <a href="<?=$archiveUrl ?>">
        <button class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Archive') ?>" >
            <i class="fa fa-folder"></i>
        </button>
    </a>
<?php endif; ?>
<?php /*************** Start POCOR-5188 */ ?>
    <?php 
        if(!empty($is_manual_exist)):
    ?>

    <a href="<?php echo $is_manual_exist['url']; ?>" target="_blank">
        <button  class="btn btn-xs btn-default icon-big"  data-toggle="tooltip" data-placement="bottom" data-container="body" title="<?= __('Help') ?>" >
        <i class="fa fa-question-circle"></i>
        </button>
    </a>
    <?php endif ?>
    <?php /*************** End POCOR-5188 */ ?>
<?php
$this->end();
?>
<?php
$this->start('panelBody');
?>

	<style type='text/css'>
	    .ag-grid-dir-ltr {
	        direction: ltr !important;
	    }
	</style>

	<div class="alert {{class}}" ng-hide="message == null">
		<a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
	</div>

	<div class="row section-header" ng-repeat-start="section in StudentResultsController.sections | orderBy:'order'" ng-show={{section.visible}}>
		{{section.name}}
	</div>
	<!--- POCOR-6963 Here Style is added to show the Assesments of individual student in Instituions > Students > Academic > Assessment tab -->
	<div ng-repeat-end class="table-wrapper" id="student-result-table_{{section.id}}" style="margin-bottom:0px !important; margin-top:0px !important">
		<div ng-if="StudentResultsController.gridOptions[section.id]" kd-ag-grid="StudentResultsController.gridOptions[section.id]" class="ag-height-fixed"></div>
	</div>
<?php
$this->end();
?>
