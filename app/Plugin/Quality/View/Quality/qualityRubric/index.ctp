<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'qualityRubricAdd'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<?php if (isset($data)) { ?>
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered" action="<?php echo $this->params['controller']; ?>/qualityRubricView/">
        <thead class="table_head">
            <tr>
                <td class="table_cell"><?php echo __('Year'); ?></td>
                <td class="table_cell"><?php echo __('Name'); ?></td>
                <td class="table_cell"><?php echo __('Class'); ?></td>
                <td class="table_cell"><?php echo __('Teacher'); ?></td>
            </tr>
        </thead>

        <tbody class="table_body">
            <?php foreach ($data as $id => $val) { ?>
                <tr class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
                    <td class="table_cell"><?php echo $schoolYearOptions[$val[$modelName]['school_year_id']]; ?></td>
                    <td class="table_cell"><?php echo $rubricOptions[$val[$modelName]['rubric_template_id']]; ?></td>
                    <td class="table_cell"><?php echo $classOptions[$val[$modelName]['institution_site_class_id']]; ?></td>
                    <td class="table_cell"><?php echo $teacherOptions[$val[$modelName]['teacher_id']]; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php } ?>
<?php $this->end(); ?>  