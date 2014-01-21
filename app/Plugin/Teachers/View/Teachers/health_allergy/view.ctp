<?php 
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Teachers/js/teachers', false);
?>
<?php $obj = $data[$modelName]; ?>
<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
			echo $this->Html->link(__('List'), array('action' => 'healthAllergy' ), array('class' => 'divider'));
			if($_edit) {
				echo $this->Html->link(__('Edit'), array('action' => 'healthAllergyEdit',$obj['id'] ), array('class' => 'divider'));
			}
			if($_delete) {
				echo $this->Html->link(__('Delete'), array('action' => 'healthAllergyDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
			}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
		
		<div class="row">
			<div class="label"><?php echo __('Type'); ?></div>
			<div class="value"><?php echo $healthAllergiesOptions[$obj['health_allergy_type_id']]; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Description'); ?></div>
			<div class="value"><?php echo $obj['description'];?></div>
		</div>
        <div class="row">
			<div class="label"><?php echo __('Severe'); ?></div>
			<div class="value"><?php echo $this->Utility->formatBoolean($obj['severe']); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Comment'); ?></div>
			<div class="value"><?php echo $obj['comment']; ?></div>
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
