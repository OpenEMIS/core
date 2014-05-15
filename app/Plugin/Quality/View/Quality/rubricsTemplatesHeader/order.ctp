<?php
echo $this->Html->css('/Quality/css/rubrics', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('table_cell', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if($_edit) {
    echo $this->Html->link(__('Back'), array('action' => 'rubricsTemplatesHeader',  $id), array('class' => 'divider', 'id'=>'back'));
}
$this->end();
$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'id'=>'submitForm'), 'file');
echo $this->Form->create($modelName, $formOptions);
?>
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
    <thead class="table_head">
    <tr class="table_head">
        <td class="table_cell"><?php echo __('Header'); ?></td>
        <td class="table_cell cell_order"><?php echo __('Order'); ?></td>
    </tr>
    </thead>
    <tbody>
<?php
$index = 0;
echo $this->Utility->getListStart();
foreach ($data as $i => $item) {
    echo '<tr>';
    $fieldName = sprintf('data[%s][%s][%%s]', $modelName, $index++);
    //echo $this->Utility->getListRowStart($i, true);
    echo $this->Utility->getIdInput($this->Form, $fieldName, $item[$modelName]['id']);
    echo $this->Utility->getOrderInput($this->Form, $fieldName, ($i + 1));
    echo '<td class="cell cell-option-rubric-header"><span>' . $item[$modelName]['title'] . '</span></td>';
    echo '<td>' . $this->Utility->getOrderControls() . '</td>';
    //echo $this->Utility->getListRowEnd();
    echo '</tr>';
}
echo $this->Utility->getListEnd();
?>
    </tbody>
</table>
</div>
<div class="controls">
    <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
    <?php echo $this->Html->link(__('Cancel'), array('action' => 'rubricsTemplatesHeader',  $id), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>  