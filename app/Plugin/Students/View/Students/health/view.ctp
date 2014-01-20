<?php 
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Students/js/students', false);
?>
<?php $obj = $data[$modelName]; ?>
<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper">
	<h1>
		<span><?php echo __('Health - Overview'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'health_edit'), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
		
		<div class="row">
			<div class="label"><?php echo __('Blood Type'); ?></div>
			<div class="value"><?php echo $obj['blood_type']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Doctor Name'); ?></div>
			<div class="value"><?php echo $obj['doctor_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Doctor Contact'); ?></div>
			<div class="value"><?php echo $obj['doctor_contact']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Medical Facility'); ?></div>
			<div class="value"><?php echo $obj['medical_facility']; ?></div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Health Insurance'); ?></div>
			<div class="value"><?php echo $this->Utility->formatBoolean($obj['health_insurance']); ?></div>
		</div>
        
        <div class="row">
            <div class="label"><?php echo __('Modified by'); ?></div>
            <div class="value"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
        </div>
        
        <div class="row">
            <div class="label"><?php echo __('Modified on'); ?></div>
            <div class="value"><?php echo $obj['modified']; ?></div>
        </div>
        
        <div class="row">
            <div class="label"><?php echo __('Created by'); ?></div>
            <div class="value"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
        </div>
        
        <div class="row">
            <div class="label"><?php echo __('Created on'); ?></div>
            <div class="value"><?php echo $obj['created']; ?></div>
        </div>
</div>
