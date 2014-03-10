<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('table_cell', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="rubrics_template" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
         echo $this->Html->link(__('Back'), array('action' => 'rubricsTemplatesView', $id), array('class' => 'divider')); 
        if ($_add) {
            echo $this->Html->link(__('Add'), array('action' => 'rubricsTemplatesHeaderAdd', $id), array('class' => 'divider'));
        }

        if ($_edit && !empty($data)) {
            echo $this->Html->link(__('Reorder'), array('action' => 'rubricsTemplatesHeaderOrder', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <table class="table  full_width" action="<?php echo $this->params['controller']; ?>/rubricsTemplatesHeaderView/<?php echo $id ?>/">
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
                    <td class="table_cell"><?php echo $this->Html->link($item[$modelName]['title'], array('action' => 'rubricsTemplatesHeaderView', $id,$item[$modelName]['id'])); ?></td>
                    <td class="table_cell cell_status"><?php echo $this->Html->link(__('Rubric Table'), array('action' => 'rubricsTemplatesSubheaderView', $item[$modelName]['id'])); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>