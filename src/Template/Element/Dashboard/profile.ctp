<?php
/**
* Mini Dashboard
*/
echo $this->Html->css('OpenEmis.../plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', ['block' => true]);
echo $this->Html->script('dashboards', ['block' => true]);
if(!$isAdmin): ?>
<style type="text/css">
.data-section.section_custom_1 {
    /* width: 20%; */
}

.data-section.section_custom_2 {
    width: 55%;
    margin-top: 20px;
	padding-left: 10px;
}

.data-section.section_custom_3 {
    width: 20%;
    text-align: center;
}
</style>
<h3><?= __('Profile Completeness'); ?></h3>
<div class="overview-box alert attendance-dashboard ng-scope" ng-class="disableElement">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close" style="position: absolute;right: 20px;">×</a>
	<div class="data-section section_custom_1 single-day">
		<i class="kd-staff icon"></i>
		<div class="data-field">
			<h4>Percent Complete:</h4>
			<h1 class="data-header ng-binding"><?= $profileCompletness['percentage'];?>%</h1>
		</div>
	</div>
	<!-- <div class="data-section">
		<div class="data-field">
			
		</div>
	</div> -->
	<div class="data-section section_custom_2">		
		<div class="progress" style= "border-radius: 25px;height: 22px;">
			<div class="progress-bar" role="progressbar"  style="background-color: #6699CC; width:<?= $profileCompletness['percentage'];?>%">
			<?= $profileCompletness['percentage'];?>%
			</div>
		</div>
	</div>
	<div class="data-section section_custom_3">
		<div class="data-field">
			<button id="profile_detail" class="btn btn-default btn-save">Details</button>
		</div>
	</div>
</div>
<?php endif ?>
<table class="table" id="profile_data_div" style="display:none">
	<thead>
		<tr>
			<th><?= __('Feature')?></th>
			<th><?= __('Last Updated')?></th>
			<th><?= __('Complete')?></th>
		</tr>
	</thead>
	<tbody>
		<?php
			unset($profileCompletness['percentage']);
			foreach ($profileCompletness as $pcVal) : 
		?>
		<tr>
			 <td class="vertical-align-top"><?= $pcVal['feature']?></td>
            <td class="vertical-align-top"><?= $pcVal['modifiedDate'];?></td>
            <td class="vertical-align-top"><?php echo ($pcVal['complete']=='yes') ?  '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-close" aria-hidden="true"></i>'?></td>
		</tr>
		 <?php endforeach?>
	</tbody>
</table>