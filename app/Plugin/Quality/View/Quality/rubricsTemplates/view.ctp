<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$obj = $data[$modelName];
$this->start('contentActions');

echo $this->Html->link(__('List'), array('action' => 'rubricsTemplates'), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link(__('Edit'), array('action' => 'rubricsTemplatesEdit', $obj['id']), array('class' => 'divider'));
}

if ($_delete && !$disableDelete) {
    echo $this->Html->link(__('Delete'), array('action' => 'rubricsTemplatesDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
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
    <div class="col-md-3"><?php echo __('Weighting'); ?></div>
    <div class="col-md-6"><?php echo $weightingOptions[$obj['weighting']]; ?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo __('Pass Mark'); ?></div>
    <div class="col-md-6"><?php echo $obj['pass_mark']; ?></div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo __('Security Role'); ?></div>
    <div class="col-md-6"><?php echo !empty($obj['security_role_id'])?$roleOptions[$obj['security_role_id']]:''; ?> </div>
</div>
<div class="row">
    <div class="col-md-3"><?php echo __('Target Grades'); ?></div>
    <div class="value"><?php
        if (!empty($rubricGradesOptions)) {
            foreach ($rubricGradesOptions as $rubricGrade) {
                echo $rubricGrade . '<br/>';
            }
        }
        ?></div>
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
