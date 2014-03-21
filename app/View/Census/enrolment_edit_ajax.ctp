<?php
$gradesCount = count($grades);
?>
<table class="table">
    <tbody>
        <tr class="th_bg">
            <td rowspan="2"><?php echo __('Age'); ?></td>
            <td rowspan="2"><?php echo __('Gender'); ?></td>
            <td colspan="<?php echo $gradesCount; ?>"><?php echo __('Grades'); ?></td>
            <td colspan="2"><?php echo __('Totals'); ?></td>
            <td rowspan="2" class="cell_delete"></td>
        </tr>
        <tr class="th_bg">
            <?php foreach ($grades AS $gradeName) { ?>
                <td><?php echo $gradeName; ?></td>
            <? } ?>
            <td></td>
            <td><?php echo __('Both'); ?></td>
        </tr>

        <?php foreach ($dataRowsArr AS $row) { ?>
            <?php if ($row['type'] == 'input') { ?>
                <tr age="<?php echo $row['age'] ?>" gender="<?php echo $row['gender'] == 'M' ? 'male' : 'female'; ?>" type="input">
                <?php } else { ?>
                <tr>
                <? } ?>
                <?php foreach ($row['data'] AS $dataKey => $dataValue) { ?>
                    <?php if ($dataKey == 'grades') { ?>
                        <?php foreach ($dataValue AS $gradeId => $censusValue) { ?>
                            <td>
                                <?php if ($row['type'] == 'input') { ?>
                                    <div class="input_wrapper" census_id="<?php echo $censusValue['censusId']; ?>" grade_id ="<?php echo $gradeId; ?>">
                                        <?php
                                        $record_tag = "";
                                        foreach ($source_type as $k => $v) {
                                            if (isset($censusValue['source']) && $censusValue['source'] == $v) {
                                                $record_tag = "row_" . $k;
                                            }
                                        }

                                        echo $this->Form->input($row['gender'] == 'M' ? 'male' : 'female', array(
                                            'type' => 'text',
                                            'class' => $record_tag,
                                            'label' => false,
                                            'div' => false,
                                            'value' => $censusValue['value'],
                                            'defaultValue' => $censusValue['value'],
                                            'maxlength' => 10,
                                            'autocomplete' => 'off',
                                            'onkeypress' => 'return utility.integerCheck(event);'
                                        ));
                                        ?>
                                    </div>
                                <?php } else { ?>
                                    <?php echo $censusValue['value']; ?>
                                <? } ?>
                            </td>
                        <?php } ?>
                    <? } else if ($dataKey == 'firstColumn' || $dataKey == 'lastColumn') { ?>
                        <td rowspan="2"><?php echo $dataValue; ?></td>
                    <? } else if ($dataKey == 'age') { ?>
                        <td rowspan="2">
                            <div class="input_wrapper">
                                            <?php
                                            $record_tag = "";
                                            foreach ($source_type as $k => $v) {
                                                if ($v == 0) {
                                                    $record_tag = "row_" . $k;
                                                    break;
                                                }
                                            }

                                            echo $this->Form->input('age', array(
                                                'type' => 'text',
                                                'class' => $record_tag,
                                                'label' => false,
                                                'div' => false,
                                                'value' => $dataValue,
                                                'defaultValue' => $dataValue,
                                                'maxlength' => 10,
                                                'autocomplete' => 'off',
                                                'onkeypress' => 'return utility.integerCheck(event);'
                                            ));
                                            ?>
                            </div>
                        </td>
                    <? } else if ($dataKey == 'colspan2') { ?>
                        <td colspan="2"><?php echo $dataValue; ?></td>
                    <?php } else { ?>
                        <td><?php echo $dataValue; ?></td>
                    <? } ?>
                <? } ?>
                <?php if ($row['type'] == 'input' && $row['gender'] == 'M') { ?>
                    <td rowspan="2" class="cell_delete">
                        <span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="CensusEnrolment.removeRow(this)"></span>
                    </td>
                <?php } else if ($row['type'] == 'read-only' && $row['gender'] == 'M') { ?>
                    <td rowspan="2" class="cell_delete"></td>
                <?php } else if ($row['type'] == 'read-only' && $row['gender'] == 'all') { ?>
                    <td class="cell_delete"></td>
                <? } ?>
            </tr>
        <? } ?>
    </tbody>
</table>