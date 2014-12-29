<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);

echo $this->Html->script('/Sms/js/sms', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Questions'));
$this->start('contentActions');
$data = $obj[0]['SmsMessage'];
echo $this->Html->link(__('List'), array('action' => 'messages'), array('class' => 'divider'));
if($_edit) {
    echo $this->Html->link(__('Edit'), array('action' => 'messagesEdit', $data['id']), array('class' => 'divider', 'onclick' => 'return sms.confirmModifySmsMessage(this)'));
}
if($_delete) {
    echo $this->Html->link(__('Delete'), array('action' => 'messagesDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<div class="row">
	<div class="col-md-3"><?php echo __('Message'); ?></div>
	<div class="col-md-6"><?php echo $data['message']; ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Enabled'); ?></div>
    <div class="col-md-6"><?php echo $this->Utility->checkOrCrossMarker($data['enabled']==1); ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Order'); ?></div>
     <div class="col-md-6"><?php echo $data['order']; ?></div>
</div>

 <div class="row">
    <div class="col-md-3"><?php echo __('Modified by'); ?></div>
    <div class="col-md-6"><?php echo trim($obj[0]['ModifiedUser']['first_name'] . ' ' . $obj[0]['ModifiedUser']['last_name']); ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Modified on'); ?></div>
    <div class="col-md-6"><?php echo $data['modified']; ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Created by'); ?></div>
    <div class="col-md-6"><?php echo trim($obj[0]['CreatedUser']['first_name'] . ' ' . $obj[0]['CreatedUser']['last_name']); ?></div>
</div>

<div class="row">
    <div class="col-md-3"><?php echo __('Created on'); ?></div>
    <div class="col-md-6"><?php echo $data['created']; ?></div>
</div>
<?php $this->end(); ?>  