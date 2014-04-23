<?php
echo $this->Html->css('/Quality/css/rubrics', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('table_cell', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<?php
echo $this->Form->create('Rubrics', array(
    'id' => 'submitForm',
    'inputDefaults' => array('label' => false, 'div' => false),
        //  'url' => array('controller' => 'Quality', 'action' => 'RubricsTemplatesCriteriaOrder', $selectedOption)
));
?>
<h1>
    <span><?php echo __($subheader); ?></span>
</h1>
<?php echo $this->element('alert'); ?>
<div class="table full_width">
    <div class="table_head">
        <div class="table_cell"><?php echo __('Header'); ?></div>
        <div class="table_cell cell_order"><?php echo __('Order'); ?></div>
    </div>
</div>
<?php
$index = 0;
echo $this->Utility->getListStart();
foreach ($data as $i => $item) {
    $fieldName = sprintf('data[%s][%s][%%s]', $modelName, $index++);
    echo $this->Utility->getListRowStart($i, true);
    echo $this->Utility->getIdInput($this->Form, $fieldName, $item[$modelName]['id']);
    echo $this->Utility->getOrderInput($this->Form, $fieldName, ($i + 1));
    echo '<div class="cell cell-option-rubric-header"><span>' . $item[$modelName]['title'] . '</span></div>';
    echo $this->Utility->getOrderControls();
    echo $this->Utility->getListRowEnd();
}
echo $this->Utility->getListEnd();
?>
<div class="controls">
    <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
    <?php echo $this->Html->link(__('Cancel'), array('action' => 'rubricsTemplatesHeader',  $id), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>  