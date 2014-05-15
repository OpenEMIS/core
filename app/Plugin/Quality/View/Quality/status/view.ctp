<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$obj = $data[$modelName];
$this->start('contentActions');
$obj = $data[$modelName]; 

echo $this->Html->link(__('List'), array('action' => 'status'), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link(__('Edit'), array('action' => 'statusEdit', $obj['id']), array('class' => 'divider'));
}

if ($_delete && !$disableDelete) {
    echo $this->Html->link(__('Delete'), array('action' => 'statusDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody'); ?>
<?php echo $this->element('alert'); ?>

<div class="row">
    <div class="col-md-3"><?php echo __('Name'); ?></div>
    <div class="col-md-6"><?php echo $rubricName; ?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo __('Year'); ?></div>
    <div class="col-md-6"><?php echo $obj['year']; ?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo __('Date Enabled'); ?></div>
    <div class="col-md-6"><?php echo $this->Utility->formatDate($obj['date_enabled']); ?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo __('Date Disabled'); ?></div>
    <div class="col-md-6"><?php echo $this->Utility->formatDate($obj['date_disabled']); ?></div>
</div>
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
