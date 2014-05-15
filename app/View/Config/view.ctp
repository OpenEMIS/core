<?php
echo $this->Html->script('setup_variables', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Config.name'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'index', $type), array('class' => 'divider'));
if($_edit && $editable) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'edit', $id), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody'); ?>
<?php echo $this->element('alert'); ?>

<?php echo $this->element('layout/view', array('fields' => $fields, 'data' => $data)); ?>

<?php $this->end(); ?>