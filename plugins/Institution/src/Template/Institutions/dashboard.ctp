<?php
echo $this->Html->css('OpenEmis.../plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', ['block' => true]);
echo $this->Html->script('highchart/highcharts', ['block' => true]);
echo $this->Html->script('highchart/dashboard-highcharts', ['block' => true]);
echo $this->Html->script('highchart/modules/exporting', ['block' => true]);
echo $this->Html->script('highchart/modules/export-data', ['block' => true]);
echo $this->Html->script('dashboards', ['block' => true]);
?>
<!-- <style type="text/css">
	.data-section {
		vertical-align: middle;
	}
	.minidashboard-donut {
		height: 100px;
		width: 100px;
		visibility: hidden;
	}
</style> -->

<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('panelBody');  
?>
<style type="text/css">
.data-section.section_custom_1 {
    width: 20%;
}

.data-section.section_custom_2 {
    width: 59%;
    margin-top: 20px;
	padding-left: 10px;
}

.data-section.section_custom_3 {
    width: 20%;
    text-align: center;
}
.progress-bar {
	background-color: #6699CC !important;
}
</style>
<!--Mini Dashboard Start-->
<?php if(isset($haveProfilePermission) && $haveProfilePermission == 1) :?>
<div class="overview-box alert" ng-class="disableElement">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close" style="position: absolute;right: 20px;">×</a>
	<div class="data-section section_custom_1">
		<!--Getting the correct icon and the header name base on the calling method-->
		<i class="kd-institutions icon"></i>
		<div class="data-field">
			<h4><?= __('Complete') ?>:</h4>
			<h1 class="data-header">
			<?= number_format($instituteprofileCompletness['percentage']) .'%' ?>
			</h1>
		</div>
	</div>
	<div class="data-section section_custom_2">
		<div class="progress" style= "border-radius: 25px;height: 22px;">
			<div class="navbar progress-bar" role="progressbar"  style="background-color: #6699CC; width:<?= number_format($instituteprofileCompletness['percentage']).'%' ?>">
			<?= number_format($instituteprofileCompletness['percentage']).'%' ?>
			</div>
		</div>
	</div>
    <div class="data-section section_custom_3">
		<div class="data-field">
			<button id="institute_profile_detail"  class="btn btn-default btn-save">Details</button>
		</div>
	</div>
	</div>
<!--Mini Dashboard End---->
<div class="table-wrapper" ng-class="disableElement" id="profile-data-div" style="display:none">
   <div class="table-responsive">
      <table class="table" id="profile_data_div">
      <thead>
         <tr>
            <th><?= __('Feature')?></th>
            <th><?= __('Last Updated')?></th>
            <th><?= __('Complete')?></th>
         </tr>
      </thead>
      <tbody>
         <!-- <tr ng-repeat="teacher in InstitutionSubjectStudentsController.pastTeachers"> -->
		<?php
			unset($instituteprofileCompletness['percentage']);
			foreach ($instituteprofileCompletness as $pcVal) : 
		?>
         <tr>
            <td class="vertical-align-top"><?= $pcVal['feature']?></td>
            <td class="vertical-align-top"><?= $pcVal['modifiedDate'] ;?></td>
            <td class="vertical-align-top"><?php echo ($pcVal['complete']=='yes') ?  '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-close" aria-hidden="true"></i>'?></td>
         </tr>
		 <?php endforeach?>
      </tbody>
   </table>
   </div>
</div>
<?php endif ?>
<!---->
<?php if(isset($haveProfilePermission) && $haveProfilePermission == 1) :?>
<h2><?= __($instituteName.'  -  '.'Dashboard'); ?></h2>
<hr class="ng-scope">
<?php endif ?>
<div class="row institution-dashboard">
    <div id="dashboard-spinner" class="spinner-wrapper">
        <div class="spinner-text">
            <div class="spinner lt-ie9"></div>
            <p><?= __('Loading'); ?> ...</p>
        </div>
    </div>

	<?php foreach ($highChartDatas as $key => $highChartData) : ?>
		<div class="highchart col-md-6" style="visibility: hidden">
			<?php echo $highChartData; ?>
		</div>
	<?php endforeach ?>
</div>

<?php
$this->end();
?>