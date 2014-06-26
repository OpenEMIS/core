<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Shifts'));

$this->start('contentActions');
if ($_edit && $isEditable) {
    echo $this->Html->link(__('Edit'), array('action' => 'shiftsEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
?>

<div class="table-responsive">

    <?php if ($displayContent) { ?>
        <fieldset class="section_group">
            <legend><?php echo __('Single Grade Classes Only'); ?></legend>

            <table class="table table-striped table-hover table-bordered">
                <thead>
                    <tr>
                        <th><?php echo __('Programme'); ?></th>
                        <th class="cell_grade"><?php echo __('Grade'); ?></th>
                        <th><?php echo __('Classes'); ?></th>
                        <?php
                        for ($i = 1; $i <= intval($noOfShifts); $i++) {
                            echo '<th class="cell_shifts">' . __('Shift') . ' ' . $i . '</th>';
                        }
                        ?>
                        <th><?php echo __('Total'); ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $totalClasses = 0;
                    foreach ($singleGradeData as $name => $value) {
                        $record_tag = "";
                        foreach ($source_type as $k => $v) {
                            if ($value['source'] == $v) {
                                $record_tag = "row_" . $k;
                            }
                        }
                        $totalClasses += $value['classes'];
                        ?>
                        <tr>
                            <td class="<?php echo $record_tag; ?>"><?php echo $value['education_programme_name']; ?></td>
                            <td class="<?php echo $record_tag; ?>"><?php echo $value['education_grade_name']; ?></td>
                            <td class="cell-number <?php echo $record_tag; ?>"><?php echo $value['classes']; ?></td>
                            <?php
                            $totalShifts = 0;
                            for ($s = 1; $s <= intval($noOfShifts); $s++) {
                                $shift = null;
                                if (isset($value['shift_' . $s])) {
                                    $shift = $value['shift_' . $s];
                                    $totalShifts += $shift;
                                }
                                echo '<td class="cell-number ' . $record_tag . '">' . $shift . '</td>';
                            }
                            ?>

                            <td class="cell-number cell_subtotal"><?php echo $totalShifts; ?></td>
                        </tr>

                        <?php
                    }
                    ?>
                </tbody>

                <tfoot>
                    <tr>
                        <td class="cell_label" colspan="2"><?php echo __('Total'); ?></td>
                        <td class="cell_value cell-number"><?php echo $totalClasses; ?></td>
                    </tr>
                </tfoot>
            </table>
        </fieldset>
        <fieldset class="section_group multi">
            <legend><?php echo __('Multi Grade Classes'); ?></legend>

            <table class="table table-striped table-hover table-bordered">
                <thead>
                    <tr>
                        <th><?php echo __('Programme'); ?></th>
                        <th class="cell_grade"><?php echo __('Grade'); ?></th>
                        <th><?php echo __('Classes'); ?></th>
                        <?php
                        for ($i = 1; $i <= intval($noOfShifts); $i++) {
                            echo '<th class="cell_shifts">' . __('Shift') . ' ' . $i . '</th>';
                        }
                        ?>
                        <th class="table_cell"><?php echo __('Total'); ?></th>
                    </tr>
                </thead>

                <?php
                $totalClasses = 0;
                if (!empty($multiGradeData)) {
                    ?>
                    <tbody>
                        <?php
                        foreach ($multiGradeData as $name => $value) {
                            $record_tag = "";
                            foreach ($source_type as $k => $v) {
                                if ($value['source'] == $v) {
                                    $record_tag = "row_" . $k;
                                }
                            }
                            $totalClasses += $value['classes'];
                            ?>
                            <tr>

                                <td class="<?php echo $record_tag; ?>">
                                    <?php foreach ($value['programmes'] as $programmeId => $programmeName) { ?>
                                        <div class="table_cell_row"><?php echo $programmeName; ?></div>
                                    <?php } ?>
                                </td>

                                <td class="<?php echo $record_tag; ?>">
                                    <?php foreach ($value['grades'] as $gradeId => $gradeName) { ?>
                                        <div class="table_cell_row"><?php echo $gradeName; ?></div>
                                    <?php } ?>
                                </td>
                                <td class="cell-number <?php echo $record_tag; ?>"><?php echo $value['classes']; ?></td>
                                <?php
                                $totalShifts = 0;
                                for ($s = 1; $s <= intval($noOfShifts); $s++) {
                                    $shift = null;
                                    if (isset($value['shift_' . $s])) {
                                        $shift = $value['shift_' . $s];
                                        $totalShifts += $shift;
                                    }
                                    echo '<td class="cell-number ' . $record_tag . '">' . $shift . '</td>';
                                }
                                ?>

                                <td class="cell-number cell_subtotal"><?php echo $totalShifts; ?></td>
                            </tr>

                            <?php
                        }
                        ?>
                    </tbody>
                <?php } ?>
                <tfoot>
                    <tr>
                        <td class="cell_label" colspan="2"><?php echo __('Total'); ?></td>
                        <td class="cell_value cell-number"><?php echo $totalClasses; ?></td>
                    </tr>
                </tfoot>
            </table>
        </fieldset>

    <?php } ?>
</div>
<?php $this->end(); ?>