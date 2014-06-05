<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'statusAdd'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<div class="table-responsive">
    <?php if (isset($data)) { ?>
        <table class="table table-striped table-hover table-bordered" action="<?php echo $this->params['controller']; ?>/statusView/">
            <thead class="table_head">
                <tr>
                    <td class="table_cell"><?php echo __('Name'); ?></td>
                    <td class="table_cell"><?php echo __('Year'); ?></td>
                    <td class="table_cell"><?php echo __('Date Enabled'); ?></td>
                    <td class="table_cell"><?php echo __('Date Disabled'); ?></td>
                </tr>
            </thead>

            <tbody class="table_body">
                <?php foreach ($data as $id => $val) { ?>
                    <tr class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
                        <td class="table_cell"><?php echo $this->Html->link($rubricOptions[ $val[$modelName]['rubric_template_id']], array('action' => 'statusView', $val[$modelName]['id']), array('escape' => false)); ?></td>
                        <td class="table_cell"><?php echo $val[$modelName]['year']; ?></td>
                        <td class="table_cell"><?php echo $this->Utility->formatDate($val[$modelName]['date_enabled']); ?></td>
                        <td class="table_cell"><?php echo $this->Utility->formatDate($val[$modelName]['date_disabled']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>
</div>
<?php $this->end(); ?>  