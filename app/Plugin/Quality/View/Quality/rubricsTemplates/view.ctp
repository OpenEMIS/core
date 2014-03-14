<?php 
//echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
//echo $this->Html->script('/Students/js/students', false);
?>
<?php $obj = $data[$modelName]; ?>
<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
			echo $this->Html->link(__('List'), array('action' => 'rubricsTemplates' ), array('class' => 'divider'));
			if($_edit) {
				echo $this->Html->link(__('Edit'), array('action' => 'rubricsTemplatesEdit',$obj['id'] ), array('class' => 'divider'));
			}
			
			echo $this->Html->link(__('Section Header'), array('action' => 'rubricsTemplatesHeader',$obj['id'] ), array('class' => 'divider'));
			
			if($_delete) {
				echo $this->Html->link(__('Delete'), array('action' => 'rubricsTemplatesDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
			}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
		
		<div class="row">
			<div class="label"><?php echo __('Name'); ?></div>
			<div class="value"><?php echo $obj['name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Description'); ?></div>
			<div class="value"><?php echo $obj['description'];?></div>
		</div>
        <div class="row">
			<div class="label"><?php echo __('Weighthings'); ?></div>
			<div class="value"><?php echo $weighthingsOptions[$obj['weighthings']]; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Pass Mark'); ?></div>
			<div class="value"><?php echo $obj['pass_mark'];?></div>
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
