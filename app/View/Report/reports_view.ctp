<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

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
    <?php echo $this->element('alert'); ?>
    <?php 
	echo $this->Form->create('Report', array(
		'url' => array('controller' => 'Report', 'action' => 'index'), 
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	echo $this->Form->hidden('load', array('name' => 'load', 'value' => '1'));
	echo $this->Form->hidden('id', array('value' => $data['ReportTemplate']['id']));
	?>
    <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value"><?php echo $data['ReportTemplate']['name']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Description'); ?></div>
        <div class="value"><?php echo $data['ReportTemplate']['description']; ?></div>
    </div>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Run'); ?>" class="btn_save btn_right" />
	</div>
	
	<?php echo $this->Form->end() ;?>
</div>
