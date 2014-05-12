<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));

$this->start('contentActions');
if ($_add) {
            echo $this->Html->link(__('Add'), array('action' => 'qualityVisitAdd'), array('class' => 'divider'));
        }
$this->end();

$this->start('contentBody');
?>
<div id="health" class="">
    <?php if (isset($data)) { ?>
        <table class="table table-striped table-hover table-bordered" action="<?php echo $this->params['controller']; ?>/qualityVisitView/">
            <thead class="table_head">
                <tr>
                    <th class="table_cell"><?php echo __('Date'); ?></th>
                    <th class="table_cell"><?php echo __('Grade'); ?></th>
                    <th class="table_cell"><?php echo __('Class'); ?></th>
                    <th class="table_cell"><?php echo __('Teacher'); ?></th>
                </tr>
            </thead>

            <tbody class="table_body">
                <?php foreach ($data as $id => $val) { ?>
                    <tr class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
                        <td class="table_cell"><?php echo $this->Utility->formatDate($val[$modelName]['date']); ?></td>
                        <td class="table_cell"><?php echo $gradeOptions[$val[$modelName]['education_grade_id']]; ?></td>
                        <td class="table_cell"><?php echo $this->Html->link($classOptions[$val[$modelName]['institution_site_class_id']], array('controller' => $this->params['controller'], 'action' => 'qualityVisitView', $val[$modelName]['id']), array('escape' => false)); ?></td>
                        <td class="table_cell"><?php echo $teacherOptions[$val[$modelName]['teacher_id']]; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>
</div>
<?php $this->end(); ?>