<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_teachers', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Teachers'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'teachers', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('CensusTeacher', array(
    'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
    'url' => array('controller' => 'Census', 'action' => 'teachersEdit')
));
echo $this->element('census/year_options');
?>

<div id="teachers" class="content_wrapper edit page-controls">

    <?php if ($displayContent) { ?>
        <fieldset class="section_group">
            <legend><?php echo __('Full Time Equivalent Teachers'); ?></legend>

            <table class="table table-striped table-hover table-bordered">
                <thead>
                    <?php echo $this->Html->tableHeaders(array(__('Education Level'), __('Male'), __('Female'), __('Total'))); ?>
                </thead>

                <tbody>
                    <?php
                    $total = 0;
                    $i = 0;
                    $fieldName = 'data[CensusTeacherFte][%d][%s]';
					
                    foreach ($eduLevelOptions as $eduLevelId => $eduLevelName):
						$subTotal = 0;
                        ?>
                        <tr>
                            <?php
                            
                            ?>
                            <td class=" <?php echo $record_tag; ?>"><?php echo $record['education_level_name']; ?></td>
							<?php
							foreach ($genderOptions AS $genderId => $genderName):
							?>
                            <td>
                                <div class="input_wrapper">
                                    <?php 
									echo $this->Form->hidden('id', array('name' => sprintf($fieldName, $index, 'id'), 'value' => $record['id']));
									echo $this->Form->hidden('education_level_id', array('name' => sprintf($fieldName, $index, 'education_level_id'), 'value' => $record['education_level_id']));
									
									$record_tag = '';
									foreach ($source_type as $k => $v):
										if (isset($fte[$eduLevelId][$genderId]['source']) && $fte[$eduLevelId][$genderId]['source'] == $v) {
											$record_tag = "row_" . $k;
										}
									endforeach;

									if (!empty($fte[$eduLevelId][$genderId]['value'])) {
										$value = $fte[$eduLevelId][$genderId]['value'];
										$subTotal += $value;
									} else {
										$value = 0;
									}
									
                                    echo $this->Form->input('value', array(
                                        'type' => 'text',
                                        'class' => $record_tag,
                                        'name' => sprintf($fieldName, $index, 'value'),
                                        'computeType' => 'cell_value',
                                        'value' => $value,
                                        'maxlength' => 7,
                                        'onkeypress' => 'return CensusTeachers.decimalCheck(event,1)',
                                        'onkeyup' => 'CensusTeachers.computeSubtotal(this)',
                                        'onblur' => 'CensusTeachers.clearBlank(this)'
                                    ));
                                    ?>
                                </div>
                            </td>
							<?php 
							$index++;
							endforeach;
							?>
                            <td class=" cell_number cell_subtotal"><?php echo $subTotal; ?></td>
                        </tr>
                        <?php
						$total += $subTotal;
                    endforeach;
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><?php echo __('Total'); ?></td>
                        <td class="cell_value cell-number"><?php echo $total; ?></td>
                    </tr>
                </tfoot>
            </table>
        </fieldset>

        <fieldset class="section_group">
            <legend><?php echo __('Trained Teachers'); ?></legend>

            <table class="table table-striped table-hover table-bordered page-controls">
                <thead>
                    <?php echo $this->Html->tableHeaders(array(__('Education Level'), __('Male'), __('Female'), __('Total'))); ?>
                </thead>


                <tbody>
                    <?php
                    $total = 0;
                    $i = 0;
                    $fieldName = 'data[CensusTeacherTraining][%d][%s]';
                    foreach ($training as $record) {
                        $total += $record['male'] + $record['female'];
                        $record_tag = "";
                        foreach ($source_type as $k => $v) {
                            if ($record['source'] == $v) {
                                $record_tag = "row_" . $k;
                            }
                        }
                        ?>
                        <tr>
                            <?php
                            echo $this->Form->hidden('id', array('name' => sprintf($fieldName, $i, 'id'), 'value' => $record['id']));
                            echo $this->Form->hidden('education_level_id', array('name' => sprintf($fieldName, $i, 'education_level_id'), 'value' => $record['education_level_id']));
                            ?>
                            <td><?php echo $record['education_level_name']; ?></td>
                            <td>
                                <div class="input_wrapper">
                                    <?php
                                    echo $this->Form->input('male', array(
                                        'type' => 'text',
                                        'class' => $record_tag,
                                        'name' => sprintf($fieldName, $i, 'male'),
                                        'computeType' => 'cell_value',
                                        'value' => is_null($record['male']) ? 0 : $record['male'],
                                        'maxlength' => 10,
                                        'onkeypress' => 'return utility.integerCheck(event)',
                                        'onkeyup' => 'CensusTeachers.computeSubtotal(this)'
                                    ));
                                    ?>
                                </div>
                            </td>
                            <td>
                                <div class="input_wrapper">
                                    <?php
                                    echo $this->Form->input('female', array(
                                        'type' => 'text',
                                        'class' => $record_tag,
                                        'name' => sprintf($fieldName, $i, 'female'),
                                        'computeType' => 'cell_value',
                                        'value' => is_null($record['female']) ? 0 : $record['female'],
                                        'maxlength' => 10,
                                        'onkeypress' => 'return utility.integerCheck(event)',
                                        'onkeyup' => 'CensusTeachers.computeSubtotal(this)'
                                    ));
                                    ?>
                                </div>
                            </td>
                            <td class=" cell_number cell_subtotal"><?php echo $record['male'] + $record['female']; ?></td>
                        </tr>
                        <?php
                        $i = $i + 1;
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td class=" cell_label"><?php echo __('Total'); ?></td>
                        <td class=" cell_value cell_number"><?php echo $total; ?></td>
                    </tr>
                </tfoot>
            </table>
        </fieldset>

        <fieldset class="section_group">
            <legend><?php echo __('Single Grade Teachers Only'); ?></legend>
            <table class="table table-striped table-hover table-bordered page-controls">
                <thead>
    <?php echo $this->Html->tableHeaders(array(__('Programme'), __('Grade'), __('Male'), __('Female'))); ?>
                </thead>

                <tbody>
                    <?php
                    $totalMale = 0;
                    $totalFemale = 0;
                    $i = 0;
                    $fieldName = 'data[CensusTeacher][%d][%s]';

                    foreach ($singleGradeData as $name => $programme) {
                        foreach ($programme['education_grades'] as $gradeId => $grade) {
                            $totalMale += $grade['male'];
                            $totalFemale += $grade['female'];
                            $record_tag = "";
                            foreach ($source_type as $k => $v) {
                                if ($grade['source'] == $v) {
                                    $record_tag = "row_" . $k;
                                }
                            }
                            ?>

                            <tr>
                                <?php
                                echo $this->Form->hidden('education_grade_id', array(
                                    'name' => sprintf('data[CensusTeacher][%d][CensusTeacherGrade][0]', $i),
                                    'value' => $gradeId
                                ));
                                ?>
                                <td class=" <?php echo $record_tag; ?>"><?php echo $name; ?></td>
                                <td class=" <?php echo $record_tag; ?>"><?php echo $grade['name']; ?></td>
                                <td>
                                    <div class="input_wrapper">
                                        <?php
                                        echo $this->Form->input('male', array(
                                            'type' => 'text',
                                            'class' => $record_tag,
                                            'name' => sprintf($fieldName, $i, 'male'),
                                            'computeType' => 'total_male',
                                            'value' => $grade['male'],
                                            'maxlength' => 10,
                                            'onkeypress' => 'return utility.integerCheck(event)',
                                            'onkeyup' => 'jsTable.computeTotal(this)'
                                        ));
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="input_wrapper">
                                        <?php
                                        echo $this->Form->input('female', array(
                                            'type' => 'text',
                                            'class' => $record_tag,
                                            'name' => sprintf($fieldName, $i++, 'female'),
                                            'computeType' => 'total_female',
                                            'value' => $grade['female'],
                                            'maxlength' => 10,
                                            'onkeypress' => 'return utility.integerCheck(event)',
                                            'onkeyup' => 'jsTable.computeTotal(this)'
                                        ));
                                        ?>
                                    </div>
                                </td>
                            </tr>

                            <?php
                        }
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td class=" cell_label"><?php echo __('Total'); ?></td>
                        <td class=" cell_value cell_number total_male"><?php echo $totalMale; ?></td>
                        <td class=" cell_value cell_number total_female"><?php echo $totalFemale; ?></td>
                    </tr>
                </tfoot>
            </table>
        </fieldset>

        <?php
        $totalMale = 0;
        $totalFemale = 0;
        ?>

        <fieldset class="section_group multi">
            <legend><?php echo __('Multi Grade Teachers'); ?></legend>

            <table class="table table-striped table-hover table-bordered page-controls">
                <thead>
    <?php echo $this->Html->tableHeaders(array(__('Programme'), __('Grade'), __('Male'), __('Female'), '')); ?>
                </thead>

                    <?php if (!empty($multiGradeData)) { ?>
                    <tbody>
                            <?php foreach ($multiGradeData as $obj) { ?>
                            <tr>
                                <?php
                                $totalMale += $obj['male'];
                                $totalFemale += $obj['female'];
                                $gradeIndex = 0;
                                $record_tag = "";
                                foreach ($source_type as $k => $v) {
                                    if ($obj['source'] == $v) {
                                        $record_tag = "row_" . $k;
                                    }
                                }
                                ?>
                                <td>
                                    <?php foreach ($obj['programmes'] as $programmeId => $programmeName) { ?>
                                        <div class="table_cell_row <?php echo $record_tag; ?>"><?php echo $programmeName; ?></div>
            <?php } ?>
                                </td>

                                <td>
                                        <?php foreach ($obj['grades'] as $gradeId => $gradeName) { ?>
                                        <div class="table_cell_row">
                                            <?php
                                            echo $gradeName;
                                            echo $this->Form->hidden('education_grade_id', array(
                                                'name' => sprintf('data[CensusTeacher][%d][CensusTeacherGrade][%d]', $i, $gradeIndex++),
                                                'value' => $gradeId
                                            ));
                                            ?>
                                        </div>
            <?php } ?>
                                </td>

                                <td>
                                    <div class="input_wrapper">
                                        <?php
                                        echo $this->Form->input('male', array(
                                            'type' => 'text',
                                            'class' => $record_tag,
                                            'name' => sprintf($fieldName, $i, 'male'),
                                            'computeType' => 'total_male',
                                            'value' => $obj['male'],
                                            'maxlength' => 10,
                                            'onkeypress' => 'return utility.integerCheck(event)',
                                            'onkeyup' => 'jsTable.computeTotal(this)'
                                        ));
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="input_wrapper">
                                        <?php
                                        echo $this->Form->input('female', array(
                                            'type' => 'text',
                                            'class' => $record_tag,
                                            'name' => sprintf($fieldName, $i++, 'female'),
                                            'computeType' => 'total_female',
                                            'value' => $obj['female'],
                                            'maxlength' => 10,
                                            'onkeypress' => 'return utility.integerCheck(event)',
                                            'onkeyup' => 'jsTable.computeTotal(this)'
                                        ));
                                        ?>
                                    </div>
                                </td>
                                <td>
            <?php echo $this->Utility->getDeleteControl(array('onclick' => "jsTable.computeAllTotal('.multi');")); ?>
                                </td>
                            </tr>
                    <?php } // end for (multigrade)   ?>
                    </tbody>
    <?php } // end if empty(multigrade)   ?>

                <tfoot>
                    <tr>
                        <td></td>
                        <td class=" cell_label"><?php echo __('Total'); ?></td>
                        <td class=" cell_value cell_number total_male"><?php echo $totalMale; ?></td>
                        <td class=" cell_value cell_number total_female"><?php echo $totalFemale; ?></td>
                    </tr>
                </tfoot>
            </table>

    <?php if ($_add) { ?>
                <div class="row">
                    <a class="void icon_plus" id="add_multi_teacher" url="Census/teachersAddMultiTeacher/<?php echo $selectedYear; ?>"><?php echo __('Add') . ' ' . __('Multi Grade Teacher'); ?></a>
                </div>
    <?php } ?>
        </fieldset>

        <div class="controls">
            <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
    <?php echo $this->Html->link(__('Cancel'), array('action' => 'teachers', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
        </div>

    <?php } // end display content   ?>
<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>