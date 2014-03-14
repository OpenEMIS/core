<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="rubrics_template" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
        echo $this->Html->link(__('Back'), array('action' => 'rubricsTemplatesSubheaderView', $rubricTemplateHeaderId), array('class' => 'divider'));
        if ($_add) {
            echo $this->Html->link(__('Add'), array('action' => 'rubricsTemplatesCriteriaAdd', $id, $rubricTemplateHeaderId), array('class' => 'divider'));
        }
        if ($_edit && !empty($data)) {
            echo $this->Html->link(__('Reorder'), array('action' => 'RubricsTemplatesCriteriaOrder', $id, $rubricTemplateHeaderId), array('class' => 'divider'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <table class="table allow_hover full_width" action="<?php echo $this->params['controller']; ?>/rubricsTemplatesCriteriaView/<?php echo $id ?>/<?php echo $rubricTemplateHeaderId ?>/">
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
                    <td class="table_cell"><?php echo $item[$modelName]['name']; ?></td>
                </tr>


            <?php } ?>
        </tbody>
    </table>
</div>