<?php
//echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
//echo $this->Html->script('/Students/js/students', false);

$this->extend('/Elements/layout/container');

$this->assign('contentHeader', __($subheader));

$obj = $data['QualityInstitutionRubric'];

$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'qualityRubric'), array('class' => 'divider'));
        echo $this->Html->link(__('View Rubric'), array('action' => 'qualityRubricHeader', $obj['id'],$rubric_template_id), array('class' => 'divider'));

        if ($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'qualityRubricEdit', $obj['id']), array('class' => 'divider'));
        }

        if ($_delete && !$disableDelete) {
            echo $this->Html->link(__('Delete'), array('action' => 'qualityRubricDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
$this->end();

$this->start('contentBody');

?>
<?php $obj = $data[$modelName]; ?>

<div id="student" class="dataDisplay content_wrapper ">

    <div class="row">
        <div class="label"><?php echo __('School Year'); ?></div>
        <div class="value"><?php echo $schoolYear; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value"><?php echo $rubric; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Grade'); ?></div>
        <div class="value"><?php echo $grade; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Class'); ?></div>
        <div class="value"><?php echo $class; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Staff'); ?></div>
        <div class="value"><?php echo trim($staff); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Evaluator'); ?></div>
        <div class="value"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Comment'); ?></div>
        <div class="value"><?php echo $obj['comment']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $obj['modified']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $obj['created']; ?></div>
    </div>
</div>
<?php $this->end(); ?>