<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Classes'));

$this->start('contentActions');
if ($_edit && $isEditable) {
    echo $this->Html->link(__('Edit'), array('action' => 'classesEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="classes" class="content_wrapper">

    <?php if ($displayContent) { ?>
        <fieldset class="section_group">
            <legend><?php echo __('Single Grade Classes Only'); ?></legend>

            <table class="table table-striped table-hover table-bordered">
                <thead>


                    <tr>
                        <td class="table_cell"><?php echo __('Programme'); ?></td>
                        <td class="table_cell cell_grade"><?php echo __('Grade'); ?></td>
                        <td class="table_cell cell_classes"><?php echo __('Classes'); ?></td>
                        <td class="table_cell cell_classes"><?php echo __('Seats'); ?></td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalClasses = 0;
                    $totalSeats = 0;

                    foreach ($singleGradeData as $name => $programme) {
                        foreach ($programme['education_grades'] as $gradeId => $grade) {
                            $totalClasses += $grade['classes'];
                            $totalSeats += $grade['seats'];
                            $record_tag = "";
                            foreach ($source_type as $k => $v) {
                                if ($grade['source'] == $v) {
                                    $record_tag = "row_" . $k;
                                }
                            }
                            ?>
                            <tr>
                                <td class="table_cell <?php echo $record_tag; ?>"><?php echo $name; ?></td>
                                <td class="table_cell <?php echo $record_tag; ?>"><?php echo $grade['name']; ?></td>
                                <td class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $grade['classes']; ?></td>
                                <td class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $grade['seats']; ?></td>
                            </tr>

                            <?php
                        }
                    }
                    ?>
                </tbody>

                <tfoot>
                    <tr>

                        <td class="table_cell"></td>
                        <td class="table_cell cell_label"><?php echo __('Total'); ?></td>
                        <td class="table_cell cell_value cell_number"><?php echo $totalClasses; ?></td>
                        <td class="table_cell cell_value cell_number"><?php echo $totalSeats; ?></td>
                    </tr>
                </tfoot>
            </table>
        </fieldset>

        <fieldset class="section_group multi">
            <legend><?php echo __('Multi Grade Classes'); ?></legend>

            <table class="table table-striped table-hover table-bordered">
                <thead>
                    <tr>
                        <td class="table_cell"><?php echo __('Programme'); ?></td>
                        <td class="table_cell cell_grade"><?php echo __('Grade'); ?></td>
                        <td class="table_cell cell_classes"><?php echo __('Classes'); ?></td>
                        <td class="table_cell cell_classes"><?php echo __('Seats'); ?></td>
                    </tr>
                </thead>

                <?php
                $totalClasses = 0;
                $totalSeats = 0;
                if (!empty($multiGradeData)) {
                    ?>
                    <tbody>
                        <?php foreach ($multiGradeData as $obj) { ?>
                            <tr>

                                <?php
                                $totalClasses += $obj['classes'];
                                $totalSeats += $obj['seats'];
                                $record_tag = "";
                                foreach ($source_type as $k => $v) {
                                    if ($obj['source'] == $v) {
                                        $record_tag = "row_" . $k;
                                    }
                                }
                                ?>
                                <td class="table_cell <?php echo $record_tag; ?>">
                                    <?php foreach ($obj['programmes'] as $programmeId => $programmeName) { ?>
                                        <div class="table_cell_row"><?php echo $programmeName; ?></div>
                                    <?php } ?>
                                </td>

                                <td class="table_cell <?php echo $record_tag; ?>">
                                    <?php foreach ($obj['grades'] as $gradeId => $gradeName) { ?>
                                        <div class="table_cell_row"><?php echo $gradeName; ?></div>
                                    <?php } ?>
                                </td>

                                <td class="table_cell cell_number"><?php echo $obj['classes']; ?></td>
                                <td class="table_cell cell_number"><?php echo $obj['seats']; ?></td>
                            </tr>
                        <?php } // end for (multigrade) ?>
                    </tbody>
                <?php } // end if empty(multigrade) ?>
                <tfoot>
                    <tr>
                        <td class="table_cell"></td>
                        <td class="table_cell cell_label"><?php echo __('Total'); ?></td>
                        <td class="table_cell cell_value cell_number"><?php echo $totalClasses; ?></td>
                        <td class="table_cell cell_value cell_number"><?php echo $totalSeats; ?></td>
                    </tr>
                </tfoot>
            </table>
        </fieldset>

    <?php } ?>
</div>

<?php $this->end(); ?>