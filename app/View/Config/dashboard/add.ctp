
<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->css('config-attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('dashboard', false);
?>
<?php 
echo $this->Html->script('setup_variables', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Config.name'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'index', 'Dashboard'), array('class' => 'divider'));
$this->end();

$this->start('contentBody'); ?>
<?php echo $this->element('alert'); ?>
<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action), 'file');
echo $this->Form->create(null, $formOptions);
?>
<?php echo $this->Form->input('ConfigAttachment.0.name'); ?>
<div class="form-group">

	<label class="col-md-3 control-label"><?php echo $this->Label->get('Config.file');?></label>
	<div class="col-md-4">
		<div class="file_input">
			<input type="file" name="files[0]" onchange="dashboard.updateFile(this)" onmouseout="dashboard.updateFile(this)" />
			<div class="file">
				<div class="input_wrapper"><input type="text" /></div>
				<input type="button" class="btn" value="<?php echo __('Select File'); ?>" onclick="dashboard.selectFile(this)" />
			</div>
		</div>
	</div>
</div>
<?php //echo $this->Form->input('ConfigAttachment.0.visible', array('options'=>array('0'=>'No', '1'=>'Yes'))); ?>
<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'dashboard'), array('class' => 'btn_cancel btn_left')); ?>
</div>
<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
