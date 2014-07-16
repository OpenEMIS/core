<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$obj = $data[$model];
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.list'), array('action' => 'indicator'), array('class' => 'divider'));
if($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'indicatorEdit', $obj['id']), array('class' => 'divider'));
}

$this->end();

$this->start('contentBody'); ?>
<?php echo $this->element('alert'); ?>

<div class="row">
	<div class="col-md-3"><?php echo __('Name'); ?></div>
	<div class="col-md-6"><?php echo $obj['name']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Description'); ?></div>
	<div class="col-md-6"><?php echo $obj['description']; ?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Code'); ?></div>
	<div class="col-md-6"><?php echo $obj['code'];?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo __('Unit'); ?></div>
	<div class="col-md-6"><?php echo $data['DatawarehouseUnit']['name'];?></div>
</div>

<?php echo $this->element('datawarehouse_condition');?>

<div class="row">
    <div class="col-md-3"><?php echo __('Modified by'); ?></div>
    <div class="col-md-6"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Modified on'); ?></div>
    <div class="col-md-6"><?php echo $obj['modified']; ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Created by'); ?></div>
    <div class="col-md-6"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Created on'); ?></div>
    <div class="col-md-6"><?php echo $obj['created']; ?></div>
</div>

<?php $this->end(); ?>
