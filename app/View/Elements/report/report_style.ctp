<div style="margin-top: 10px; padding-left: 5px;">
	<div class="row">
		<label class="col-md-3"><?php echo __('Output'); ?></label>
		<div class="col-md-4"><?php echo $this->Form->input('Output', array('label' => false, 'class' => 'form-control', 'options' => $outputOptions)); ?></div>
	</div>
	<div class="row">
		<label class="col-md-3"><?php echo __('Save Report'); ?></label>
		<div class="col-md-4"><?php echo $this->Form->input('SaveReport', array('label' => false, 'class' => 'form-control save-report', 'options' => array('No', 'Yes'), 'autocomplete' => 'off', 'onchange' => 'CustomReport.toggle(this)')); ?></div>
	</div>
	<div class="row">
		<label class="col-md-3"><?php echo __('Report Name'); ?></label>
		<div class="col-md-4"><?php echo $this->Form->input('ReportName', array('label' => false, 'class' => 'form-control toggle')); ?></div>
	</div>
	<div class="row">
		<label class="col-md-3"><?php echo __('Description'); ?></label>
		<div class="col-md-4"><?php echo $this->Form->textarea('ReportDescription', array('label' => false, 'class' => 'form-control toggle')); ?></div>
	</div>
	<?php
	if($_accessControl->check('Report', 'sharedReportAdd')) :
	?>
	<div class="row">
		<label class="col-md-3"><?php echo __('Shared Report'); ?></label>
		<div class="col-md-4"><?php echo $this->Form->input('SharedReport', array('label' => false, 'class' => 'form-control toggle', 'options' => array(0 => 'No', 1 => 'Yes'))); ?></div>
	</div>
	<?php 
	else :
		echo $this->Form->hidden('SharedReport', array('value' => 0));
	endif;
	?>
</div>
