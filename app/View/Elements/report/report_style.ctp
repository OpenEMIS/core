<div style="margin-top: 10px; padding-left: 5px;">
	<div class="row">
		<div class="label"><?php echo __('Output'); ?></div>
		<div class="value"><?php echo $this->Form->input('Output', array('label' => false, 'class' => 'default', 'options' => $outputOptions)); ?></div>
	</div>
	<div class="row">
		<div class="label"><?php echo __('Save Report'); ?></div>
		<div class="value"><?php echo $this->Form->input('SaveReport', array('label' => false, 'class' => 'default', 'options' => array('No', 'Yes'))); ?></div>
	</div>
	<div class="row">
		<div class="label"><?php echo __('Report Name'); ?></div>
		<div class="value"><?php echo $this->Form->input('ReportName', array('label' => false, 'class' => 'default')); ?></div>
	</div>
	<div class="row">
		<div class="label"><?php echo __('Description'); ?></div>
		<div class="value"><?php echo $this->Form->textarea('ReportDescription', array('label' => false, 'class' => 'default')); ?></div>
	</div>
	<?php
	if($_accessControl->check('Report', 'sharedReportAdd')) :
	?>
	<div class="row">
		<div class="label"><?php echo __('Shared Report'); ?></div>
		<div class="value"><?php echo $this->Form->input('SharedReport', array('label' => false, 'class' => 'default', 'options' => array(0 => 'No', 1 => 'Yes'))); ?></div>
	</div>
	<?php 
	else :
		echo $this->Form->hidden('SharedReport', array('value' => 0));
	endif;
	?>
</div>
