<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');

echo $this->Html->link(__('Back'), array('action' => 'rubricsTemplatesSubheaderView', $rubricTemplateHeaderId), array('class' => 'divider'));
if ($_add && !$disableDelete) {
    echo $this->Html->link(__('Add'), array('action' => 'rubricsTemplatesCriteriaAdd', $id, $rubricTemplateHeaderId), array('class' => 'divider'));
}
if ($_edit && !empty($data)) {

    echo $this->Html->link(__('Reorder'), array('action' => 'rubricsTemplatesCriteriaOrder', $id, $rubricTemplateHeaderId), array('class' => 'divider'));

}
$this->end();

$this->start('contentBody');
?>

<?php echo $this->element('alert'); ?>
<div class="table-responsive">
<table class="table table-striped table-hover table-bordered" action="<?php echo $this->params['controller']; ?>/rubricsTemplatesCriteriaView/<?php echo $id ?>/<?php echo $rubricTemplateHeaderId ?>/">
    <thead class="table_head">
        <tr>
            <td><?php echo __('Option') ?></td>
        </tr>
    </thead>
    <tbody class="table_body">
        <?php
        foreach ($data as $item) {
            //pr($item);
            ?>
            <tr class="table_row"  row-id="<?php echo $item[$modelName]['id']; ?>">
                <td class="table_cell"><?php echo $this->Html->link($item[$modelName]['name'], array('action' => 'rubricsTemplatesView', $item[$modelName]['id']), array('escape' => false)); ?></td>
            </tr>


        <?php } ?>
    </tbody>
</table>
</div>
<?php $this->end(); ?>  