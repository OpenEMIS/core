<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_classes', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Shifts'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'shifts', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('CensusShift', array(
    'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
    'url' => array('controller' => 'Census', 'action' => 'shiftsEdit')
));
echo $this->element('census/year_options');
?>

<div id="classes" class="content_wrapper edit">

    <?php if ($displayContent) { ?>
        <fieldset class="section_group">
            <legend><?php echo __('Single Grade Classes Only'); ?></legend>
            <table class="table table-striped table-hover table-bordered page-controls">
                <thead>
                    <tr>
                        <th class="table_cell"><?php echo __('Programme'); ?></th>
                        <th class="table_cell cell_grade"><?php echo __('Grade'); ?></th>
                        <th class="table_cell"><?php echo __('Classes'); ?></th>
                        <?php
                        for ($i = 1; $i <= intval($noOfShifts); $i++) {
                            echo '<th class="table_cell cell_shifts">' . __('Shift') . ' ' . $i . '</th>';
                        }
                        ?>
                        <th class="table_cell"><?php echo __('Total'); ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $totalClasses = 0;
                    $i = 0;

                    foreach ($singleGradeData as $name => $value) {
                        $record_tag = "";
                        foreach ($source_type as $k => $v) {
                            if (isset($value['shift_source'])) {
                                if ($value['shift_source'] == $v) {
                                    $record_tag = "row_" . $k;
                                }
                            }
                        }
                        $totalClasses += $value['classes'];
                        ?>
                        <tr>
                            <td class="table_cell <?php echo $record_tag; ?>"><?php echo $value['education_programme_name']; ?></td>
                            <td class="table_cell <?php echo $record_tag; ?>"><?php echo $value['education_grade_name']; ?></td>
                            <td class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $value['classes']; ?></td>

                            <?php
                            $totalShifts = 0;
                            $pk = $value['id'];



                            for ($s = 1; $s <= intval($noOfShifts); $s++) {
                                ?>
                                <?php
                                $shift = null;
                                if (isset($this->request->data['CensusShift'][$pk])) {
                                    $shift = $this->request->data['CensusShift'][$pk]['shift_value_' . $s];
                                    $totalShifts += $this->request->data['CensusShift'][$pk]['shift_value_' . $s];
                                } else {
                                    if (isset($value['shift_' . $s])) {
                                        $shift = $value['shift_' . $s];
                                        $totalShifts += $shift;
                                    }
                                }
                                ?>
                                <td class="table_cell">
                                    <div class="input_wrapper">
                                        <?php
                                        if (isset($value['shift_pk_' . $s])) {
                                            echo $this->Form->hidden($pk . '_shift_pk_' . $s, array(
                                                'value' => $value['shift_pk_' . $s]
                                            ));
                                        }
                                        ?>
                                        <?php
                                        echo $this->Form->input($pk . '.shift_value_' . $s, array(
                                            'type' => 'text',
                                            'class' => $record_tag,
                                            'computeType' => 'cell_subtotal',
                                            'default' => $shift,
                                            'maxlength' => 5,
                                            'onkeypress' => 'return utility.integerCheck(event)',
                                            'onkeyup' => 'jsTable.computeSubtotal(this)'
                                        ));
                                        ?>
                                    </div>
                                </td>
                                <?php
                            }
                            echo $this->Form->hidden($pk . '.shift_class_total', array(
                                'value' => $value['classes']
                            ));
                            ?>
                            <td class="table_cell cell_number cell_subtotal"><?php echo $totalShifts; ?></td>
                        </tr>	
        <?php
    }
    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="table_cell"></td>
                        <td class="table_cell cell_label"><?php echo __('Total'); ?></td>
                        <td class="table_cell cell_value cell_number"><?php echo $totalClasses; ?></td>
                    </tr>
                </tfoot>
            </table>
        </fieldset>

        <fieldset class="section_group">
            <legend><?php echo __('Multi Grade Classes Only'); ?></legend>
            <table class="table table-striped table-hover table-bordered page-controls">
                <thead>
                    <tr>
                        <th class="table_cell"><?php echo __('Programme'); ?></th>
                        <th class="table_cell cell_grade"><?php echo __('Grade'); ?></th>
                        <th class="table_cell"><?php echo __('Classes'); ?></th>
    <?php
    for ($i = 1; $i <= intval($noOfShifts); $i++) {
        echo '<td class="table_cell cell_shifts">' . __('Shift') . ' ' . $i . '</td>';
    }
    ?>
                        <td class="table_cell"><?php echo __('Total'); ?></td>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $totalClasses = 0;
                    $i = 0;

                    foreach ($multiGradeData as $name => $value) {
                        $record_tag = "";
                        foreach ($source_type as $k => $v) {
                            if (isset($value['shift_source'])) {
                                if ($value['shift_source'] == $v) {
                                    $record_tag = "row_" . $k;
                                }
                            }
                        }
                        $totalClasses += $value['classes'];
                        ?>
                        <tr>
                            <td class="table_cell <?php echo $record_tag; ?>">
                                <?php foreach ($value['programmes'] as $programmeId => $programmeName) { ?>
                                    <div class="table_cell_row"><?php echo $programmeName; ?></div>
                                <?php } ?>
                            </td>

                            <td class="table_cell <?php echo $record_tag; ?>">
                            <?php foreach ($value['grades'] as $gradeId => $gradeName) { ?>
                                    <div class="table_cell_row"><?php echo $gradeName; ?></div>
                            <?php } ?>
                            </td>
                            <td class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $value['classes']; ?></td>

                            <?php
                            $totalShifts = 0;
                            $pk = $name;
                            for ($s = 1; $s <= intval($noOfShifts); $s++) {
                                ?>
                                <?php
                                $shift = null;
                                if (isset($this->request->data['CensusShift'][$pk])) {
                                    $shift = $this->request->data['CensusShift'][$pk]['shift_value_' . $s];
                                    $totalShifts += $this->request->data['CensusShift'][$pk]['shift_value_' . $s];
                                } else {
                                    if (isset($value['shift_' . $s])) {
                                        $shift = $value['shift_' . $s];
                                        $totalShifts += $shift;
                                    }
                                }
                                ?>
                                <td class="table_cell">
                                    <div class="input_wrapper">
                                        <?php
                                        if (isset($value['shift_pk_' . $s])) {
                                            echo $this->Form->hidden($pk . '_shift_pk_' . $s, array(
                                                'value' => $value['shift_pk_' . $s]
                                            ));
                                        }
                                        ?>
                                        <?php
                                        echo $this->Form->input($pk . '.shift_value_' . $s, array(
                                            'type' => 'text',
                                            'class' => $record_tag,
                                            'computeType' => 'cell_subtotal',
                                            'default' => $shift,
                                            'maxlength' => 5,
                                            'onkeypress' => 'return utility.integerCheck(event)',
                                            'onkeyup' => 'jsTable.computeSubtotal(this)'
                                        ));
                                        ?>
                                    </div>
                                </td>
                            <?php
                        }
                        echo $this->Form->hidden($pk . '.shift_class_total', array(
                            'value' => $value['classes']
                        ));
                        ?>
                            <td class="table_cell cell_number cell_subtotal"><?php echo $totalShifts; ?></td>
                        </tr>	
        <?php
    }
    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="table_cell"></td>
                        <td class="table_cell cell_label"><?php echo __('Total'); ?></td>
                        <td class="table_cell cell_value cell_number"><?php echo $totalClasses; ?></td>
                    </tr>
                </tfoot>
            </table>
        </fieldset>

        <div class="controls">
            <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
    <?php echo $this->Html->link(__('Cancel'), array('action' => 'classes', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
        </div>
<?php } // end display content  ?>
<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>
