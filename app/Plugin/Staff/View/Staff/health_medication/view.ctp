<?php 
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Staff/js/staff', false);
?>
<?php $obj = $data[$modelName]; ?>
<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
			echo $this->Html->link(__('List'), array('action' => 'healthMedication' ), array('class' => 'divider'));
			if($_edit) {
				echo $this->Html->link(__('Edit'), array('action' => 'healthMedicationEdit',$obj['id'] ), array('class' => 'divider'));
			}
			if($_delete) {
				echo $this->Html->link(__('Delete'), array('action' => 'healthMedicationDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
			}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
		
		<div class="row">
			<div class="label"><?php echo __('Name'); ?></div>
			<div class="value"><?php echo $obj['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Dosage'); ?></div>
			<div class="value"><?php echo $obj['dosage'];?></div>
		</div>
        <div class="row">
			<div class="label"><?php echo __('Commenced Date'); ?></div>
			<div class="value"><?php echo $obj['start_date']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Ended Date'); ?></div>
			<div class="value"><?php echo $obj['end_date']; ?></div>
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