<?php
//echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
//echo $this->Html->script('/Students/js/students', false);
?>
<?php $obj = $data[$modelName]; ?>
<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
        echo $this->Html->link(__('List'), array('action' => 'status'), array('class' => 'divider'));
        if ($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'statusEdit', $obj['id']), array('class' => 'divider'));
        }

        if ($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'statusDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>

    <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value"><?php echo $rubricName; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Year'); ?></div>
        <div class="value"><?php echo $obj['year']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Date Enabled'); ?></div>
        <div class="value"><?php echo $this->Utility->formatDate($obj['date_enabled']); ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Date Disabled'); ?></div>
        <div class="value"><?php echo $this->Utility->formatDate($obj['date_disabled']); ?></div>
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
