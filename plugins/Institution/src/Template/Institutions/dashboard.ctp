<?php
echo $this->Html->css('OpenEmis.../plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', ['block' => true]);
echo $this->Html->script('highchart/highcharts', ['block' => true]);
echo $this->Html->script('highchart/modules/exporting', ['block' => true]);
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
<!--Mini Dashboard Start-->
<h3><?= __('Intitution Completness'); ?></h3>
<div class="overview-box alert" ng-class="disableElement">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
	<div class="data-section">
		<!--Getting the correct icon and the header name base on the calling method-->
		<i class="kd-institutions icon"></i>
		<div class="data-field">
			<h4><?= __('Complete') ?>:</h4>
			<h1 class="data-header">
			<?= number_format($percentage) .'%' ?>
			</h1>
		</div>
	</div>
	<div class="data-section">
		<div class="progress">
			<div class="progress-bar" role="progressbar"  style="width:<?= number_format($percentage).'%' ?>">
			<?= number_format($percentage).'%' ?>
			</div>
		</div>
	</div>
   <div class="data-section">
	</div>
    <div class="data-section">
		<div class="data-field">
			<button href="#" class="btn btn-primary">
				Details
			</button>
		</div>
	</div>
	</div>
<!--Mini Dashboard End---->
<div class="table-wrapper" ng-class="disableElement" id="profile-data-div">
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
		 	$data['listing'] = array('feature'=>'General','data'=> '2020-11-26','complete'=>'yes');
		  	foreach ($data as $listing) : 
			  ?>
         <tr>
            <td class="vertical-align-top"><?= $listing['feature']?></td>
            <td class="vertical-align-top"><?= date("F j,Y",strtotime($listing['data'])) ;?></td>
            <td class="vertical-align-top"><i class="fa fa-check" aria-hidden="true"></i></td>
         </tr>
		 <?php endforeach?>
      </tbody>
   </table>
   </div>
</div>


<!---->
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