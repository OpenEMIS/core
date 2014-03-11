<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="health" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
        if ($_add) {
            echo $this->Html->link(__('Add'), array('action' => 'qualityVisitAdd'), array('class' => 'divider'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php if (isset($data)) { ?>
        <table class="table allow_hover full_width" action="<?php echo $this->params['controller']; ?>/qualityVisitView/">
            <thead class="table_head">
                <tr>
                    <td class="table_cell"><?php echo __('Date'); ?></td>
                    <td class="table_cell"><?php echo __('Grade'); ?></td>
                    <td class="table_cell"><?php echo __('Class'); ?></td>
                    <td class="table_cell"><?php echo __('Teacher'); ?></td>
                </tr>
            </thead>

            <tbody class="table_body">
                <?php foreach ($data as $id => $val) { ?>
                    <tr class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
                        <td class="table_cell"><?php echo $val[$modelName]['date']; ?></td>
                        <td class="table_cell"><?php echo $gradeOptions[$val[$modelName]['education_grade_id']]; ?></td>
                        <td class="table_cell"><?php echo $classOptions[$val[$modelName]['institution_site_class_id']]; ?></td>
                        <td class="table_cell"><?php echo $teacherOptions[$val[$modelName]['teacher_id']]; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>
</div>