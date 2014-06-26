<?php
echo $this->Html->script('report/index', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Custom Reports'));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'report');
$this->assign('contentClass', 'index');
$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<?php
echo $this->Form->create('Report', array(
	'url' => array('controller' => 'Report', 'action' => 'index'), 
	'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')));

echo $this->Form->input('new', array('type'=>'hidden', 'value'=>'1'));
?>

<div class="row">
	<label class="col-md-3 control-label"><?php echo __('Model'); ?></label>
	<div class="col-md-4">
		<?php echo $this->Form->input('model', array('options' => $models, 'class'=>'form-control')); ?>
	</div>
</div>

<div class="controls view_controls">
    <input type="submit" value="<?php echo __('Next'); ?>" class="btn_save" />
</div>
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>