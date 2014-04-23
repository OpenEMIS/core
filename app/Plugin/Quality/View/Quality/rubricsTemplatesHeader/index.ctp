<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('table_cell', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));

echo $this->Html->link(__('Back'), array('action' => 'rubricsTemplates'), array('class' => 'divider')); 
if ($_add) {
    echo $this->Html->link(__('Add Section Header'), array('action' => 'rubricsTemplatesHeaderAdd', $id), array('class' => 'divider'));
}

if ($_edit && !empty($data)) {
    echo $this->Html->link(__('Reorder'), array('action' => 'rubricsTemplatesHeaderOrder', $id), array('class' => 'divider'));
}
$this->start('contentActions');
?>
<?php echo $this->element('alert'); ?>
<div class="table-responsive">
<table class="table table-striped table-hover table-bordered" action="<?php echo $this->params['controller']; ?>/rubricsTemplatesHeaderView/<?php echo $id ?>/">
    <thead class="table_head">
        <tr>
           <!-- <td class="cell_id_no"><?php echo __('No.') ?></td> -->
            <td><?php echo __('Section Header') ?></td>
            <td class='cell_status'><?php echo __('Action') ?></td>
        </tr>
    </thead>
    <tbody class="table_body">
        <?php foreach ($data as $key => $item) { ?>
            <tr class="table_row"  row-id="<?php echo $item[$modelName]['id']; ?>">
               <!-- <td class="table_cell"><?php echo $key + 1; ?></td> -->
                <td class="table_cell"><?php echo $this->Html->link('<div>'.$item[$modelName]['title'].'</div>',array('action' => 'rubricsTemplatesSubheaderView', $item[$modelName]['id']), array('escape' => false)); ?></td>
                <td class="table_cell cell_status"><?php echo $this->Html->link('<div>'.__('View Details').'</div>',  array('action' => 'rubricsTemplatesHeaderView', $id,$item[$modelName]['id']), array('escape' => false)); ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
</div>
<?php $this->end(); ?>  