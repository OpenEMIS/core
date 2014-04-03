<?php echo $this->element('breadcrumb'); ?>

<div class="content_wrapper">
    <h1>
        <span><?php echo __('Custom Report'); ?></span>
        <?php
        echo $this->Html->link(__('List'), array('action' => 'index'), array('class' => 'divider'));
        if($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'reportsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
        ?>
    </h1>
    <?php
	echo $this->element('alert');
	echo $this->Form->create('Report', array(
		'target' => 'blank',
		'url' => array('controller' => 'Report', 'action' => 'reportsWizard', 'load', $data['ReportTemplate']['id']), 
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>
    <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value"><?php echo $data['ReportTemplate']['name']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Description'); ?></div>
        <div class="value"><?php echo $data['ReportTemplate']['description']; ?></div>
    </div>
	
	<div class="row">
        <div class="label"><?php echo __('Format'); ?></div>
        <div class="value"><?php echo $this->Form->input('Output', array('label' => false, 'class' => 'default', 'options' => $outputOptions)); ?></div>
    </div>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Run'); ?>" class="btn_save btn_right" />
	</div>
	<?php echo $this->Form->end() ;?>
</div>
