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
            <?php } ?>
            <td></td>
            <td><?php echo __('Both'); ?></td>
        </tr>

        <?php foreach ($dataRowsArr AS $row) { ?>
            <?php if ($row['type'] == 'input') { ?>
                <tr age="<?php echo $row['age'] ?>" gender="<?php echo $row['gender'] == 'M' ? 'male' : 'female'; ?>" type="input">
                <?php } else { ?>
                <tr>
                <?php } ?>
                <?php foreach ($row['data'] AS $dataKey => $dataValue) { ?>
                    <?php if ($dataKey == 'grades') { ?>
                        <?php foreach ($dataValue AS $gradeId => $censusValue) { ?>
                            <td class="inputField">
                                <?php if ($row['type'] == 'input') { ?>
                                    <div class="input_wrapper" census_id="<?php echo $censusValue['censusId']; ?>" grade_id ="<?php echo $gradeId; ?>">
                                        <?php
                                        $record_tag = "";
                                        foreach ($source_type as $k => $v) {
                                            if (isset($censusValue['source']) && $censusValue['source'] == $v) {
                                                $record_tag = "row_" . $k;
                                            }
                                        }

                                        echo $this->Form->input($row['gender'] == 'M' ? 'CensusStudentMale' : 'CensusStudentFemale', array(
                                            'type' => 'text',
                                            'class' => $record_tag,
                                            'label' => false,
                                            'div' => false,
                                            'value' => $censusValue['value'],
                                            'defaultValue' => $censusValue['value'],
                                            'maxlength' => 10,
                                            'autocomplete' => 'off',
                                            'onkeypress' => 'return utility.integerCheck(event);',
                                            'onkeyup' => 'CensusEnrolment.computeByAgeGender(this);'
                                        ));
                                        ?>
                                    </div>
                                <?php } else { ?>
                                    <?php echo $censusValue['value']; ?>
                                <?php } ?>
                            </td>
                        <?php } ?>
                    <?php } else if ($dataKey == 'firstColumn' || $dataKey == 'lastColumn') { ?>
                        <td rowspan="2"><?php echo $dataValue; ?></td>
                    <?php } else if ($dataKey == 'age') { ?>
                        <?php if(isset($row['ageEditable']) && $row['ageEditable'] == 'yes'){?>
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

                                                echo $this->Form->input('CensusStudentAge', array(
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
                        <?php }else{?>
                            <td rowspan="2"><?php echo $dataValue; ?></td>
                        <?php }?>
                        
                    <?php } else if ($dataKey == 'colspan2') { ?>
                        <td colspan="2"><?php echo $dataValue; ?></td>
                    <?php }else if($dataKey == 'firstHalf'){?>
                        <td colspan="<?php echo $row['colspan']; ?>" class="rowTotalLeftCol"><?php echo $dataValue; ?></td>
                    <?php }else if($dataKey == 'totalAllGrades'){?>
                        <td colspan="2" class="rowTotalRightCol"><?php echo $dataValue; ?></td>
                    <?php }else if($dataKey == 'totalByAgeMale' || $dataKey == 'totalByAgeFemale'){?>
                        <td class="<?php echo $dataKey; ?>"><?php echo $dataValue; ?></td>
                    <?php }else if($dataKey == 'totalByAgeAllGender'){?>
                        <td rowspan="2" class="<?php echo $dataKey; ?>"><?php echo $dataValue; ?></td>
                    <?php } else { ?>
                        <td><?php echo $dataValue; ?></td>
                    <?php } ?>
                <?php } ?>
                <?php if ($row['type'] == 'input' && $row['gender'] == 'M') { ?>
                    <?php if(isset($row['ageEditable']) && $row['ageEditable'] == 'yes'){?>
                        <td rowspan="2" class="cell_delete">
                            <span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="CensusEnrolment.removeRow(this)"></span>
                        </td>
                    <?php }else{?>
                        <td rowspan="2" class="cell_delete"></td>
                    <?php }?>
                <?php } else if ($row['type'] == 'read-only' && $row['gender'] == 'M') { ?>
                    <td rowspan="2" class="cell_delete"></td>
                <?php } else if ($row['type'] == 'read-only' && $row['gender'] == 'all') { ?>
                    <td class="cell_delete"></td>
                <?php } ?>
            </tr>
        <?php } ?>
    </tbody>
</table>