<?php
echo $this->Html->css('/Quality/css/colorpicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Quality/js/colorpicker', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>
<?php $obj = $data[$modelName]; ?>
<?php echo $this->element('breadcrumb'); ?>

<div id="rubrics_template" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
        echo $this->Html->link(__('List'), array('action' => 'RubricsTemplatesCriteria', $id, $rubricTemplateHeaderId), array('class' => 'divider'));

        if ($_edit) {
            echo $this->Html->link(__('Edit'), array('action' => 'RubricsTemplatesCriteriaEdit', $id, $rubricTemplateHeaderId, $obj['id']), array('class' => 'divider'));
        }
        if ($_delete) {
            echo $this->Html->link(__('Delete'), array('action' => 'rubricsTemplatesCriteriaDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <div class="row">
        <div class="label"><?php echo __('Name'); ?></div>
        <div class="value"><?php echo $obj['name']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Weighting'); ?></div>
        <div class="value"><?php echo $obj['weighting']; ?></div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Color'); ?></div>
        <div class="value"><div id="colorSelector"><div style="background-color:#<?php echo $obj['color']; ?>"></div></div></div>
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