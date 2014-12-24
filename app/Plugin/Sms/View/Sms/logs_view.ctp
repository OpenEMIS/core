<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Logs'));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'logs'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>
<div class="row">
	<div class="col-md-3"><?php echo __('Date') . '/' . __('Time'); ?></div>
	<div class="col-md-6"><?php echo $data['AlertLog']['created']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Destination'); ?></div>
	<div class="col-md-6"><?php echo $data['AlertLog']['destination']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Channel'); ?></div>
	<div class="col-md-6"><?php echo __('Sent'); ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Method'); ?></div>
	<div class="col-md-6"><?php echo $data['AlertLog']['method']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Status'); ?></div>
	<div class="col-md-6"><?php echo isset($statusOptions[$data['AlertLog']['status']]) ? $statusOptions[$data['AlertLog']['status']] : ''; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Subject'); ?></div>
	<div class="col-md-6"><?php echo $data['AlertLog']['subject']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Message'); ?></div>
	<div class="col-md-6"><?php echo $data['AlertLog']['message']; ?></div>
</div>
<?php $this->end(); ?>  