<?php
//echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
//echo $this->Html->script('/Students/js/students', false);
?>
<?php $obj = $data[$modelName];

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Utility->ellipsis(__($subheader), 50));
$this->start('contentActions');
 echo $this->Html->link(__('Back'), array('action' => 'rubricsTemplatesHeader', $rubric_template_id), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link(__('Edit'), array('action' => 'rubricsTemplatesHeaderEdit', $rubric_template_id, $obj['id']), array('class' => 'divider'));
}

if ($_delete && !$disableDelete) {
    echo $this->Html->link(__('Delete'), array('action' => 'rubricsTemplatesHeaderDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody'); ?>

<?php echo $this->element('alert'); ?>

<div class="row">
    <div class="col-md-3"><?php echo __('Section Header'); ?></div>
    <div class="col-md-6"><?php echo $obj['title']; ?></div>
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