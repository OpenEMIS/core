<?php
$displayDefault = array();
foreach ($programmeGrades as $obj) {
    foreach ($obj['education_grades'] as $gradeId => $grade) {
        if (sizeof($displayDefault) < 2) {
            $displayDefault[] = array(
                'programme' => $obj['education_programme_id'],
                'selectedGrade' => $gradeId,
                'grades' => $obj['education_grades']
            );
        } else {
            break;
        }
    }
}
?>

<?php if (sizeof($displayDefault) >= 2) { ?>

    <?php if ($body == 0) {
        echo '<tbody>';
    } ?>
    <tr class="table_row" row="<?php echo $i; ?>">
        <td class="table_cell programme_list">
            <div class="table_cell_row">
                <?php
                echo $this->Form->input('education_programme_id', array(
                    'url' => 'Census/loadGradeList',
                    'div' => false,
                    'label' => false,
                    'class' => 'form-control',
                    'options' => $programmes,
                    'default' => $displayDefault[0]['programme'],
                    'onchange' => 'Census.loadGradeList(this)',
                    'index' => 0
                ));
                ?>
            </div>
            <div class="table_cell_row">
                <?php
                echo $this->Form->input('education_programme_id', array(
                    'url' => 'Census/loadGradeList',
                    'div' => false,
                    'label' => false,
                    'class' => 'form-control',
                    'options' => $programmes,
                    'default' => $displayDefault[1]['programme'],
                    'onchange' => 'Census.loadGradeList(this)',
                    'index' => 1
                ));
                ?>
            </div>
            <div class="row last">
                <a class="void icon_plus" url="Census/teachersAddMultiGrade/<?php echo $yearId; ?>" onclick="Census.addMultiGrade(this)"><?php echo __('Add') . ' ' . __('Grade'); ?></a>
            </div>
        </td>

        <td class="table_cell grade_list">
            <div class="table_cell_row">
                <?php 
				$firstGradeOptions = array();
				foreach($displayDefault[0]['grades'] AS $gradeId => $gradeData){
					$firstGradeOptions[$gradeId] = $gradeData['gradeName'];
				}
                echo $this->Form->input('education_grade_id', array(
                    'div' => false,
                    'label' => false,
                    'class' => 'form-control',
                    'name' => sprintf('data[CensusTeacher][%d][CensusTeacherGrade][0]', $i),
                    'options' => $firstGradeOptions,
                    'default' => $displayDefault[0]['selectedGrade'],
                    'index' => 0,
					'onchange' => 'CensusTeachers.updateHiddenGradeId(this)'
                ));
				echo $this->Form->hidden('education_grade_id', array(
                    'name' => sprintf('data[CensusTeacher][%d][CensusTeacherGrade][0]', $i+1),
                    'default' => $displayDefault[0]['selectedGrade'],
					'class' => 'hiddenGradeId'
                ));
                ?>
            </div>
            <div class="table_cell_row">
                <?php 
				$secondGradeOptions = array();
				foreach($displayDefault[1]['grades'] AS $gradeId => $gradeData){
					$secondGradeOptions[$gradeId] = $gradeData['gradeName'];
				}
                echo $this->Form->input('education_grade_id', array(
                    'div' => false,
                    'label' => false,
                    'class' => 'form-control',
                    'name' => sprintf('data[CensusTeacher][%d][CensusTeacherGrade][1]', $i),
                    'options' => $secondGradeOptions,
                    'default' => $displayDefault[1]['selectedGrade'],
                    'index' => 1,
					'onchange' => 'CensusTeachers.updateHiddenGradeId(this)'
                ));
				echo $this->Form->hidden('education_grade_id', array(
                    'name' => sprintf('data[CensusTeacher][%d][CensusTeacherGrade][1]', $i+1),
                    'default' => $displayDefault[1]['selectedGrade'],
					'class' => 'hiddenGradeId'
                ));
                ?>
            </div>
        </td>

        <td class="table_cell">
            <div class="input_wrapper">
                <?php 
				echo $this->Form->hidden('gender_id', array('name' => sprintf('data[CensusTeacher][%d][gender_id]', $i), 'value' => $maleGenderId));
                echo $this->Form->input('value', array(
                    'div' => false,
                    'label' => false,
                    'type' => 'text',
                    'name' => sprintf('data[CensusTeacher][%d][value]', $i),
                    'computeType' => 'total_male',
                    'value' => 0,
                    'maxlength' => 10,
                    'onkeypress' => 'return utility.integerCheck(event)',
                    'onkeyup' => 'jsTable.computeTotal(this)'
                ));
                ?>
            </div>
        </td>
        <td class="table_cell">
            <div class="input_wrapper">
                <?php 
				echo $this->Form->hidden('gender_id', array('name' => sprintf('data[CensusTeacher][%d][gender_id]', $i+1), 'value' => $femaleGenderId));
                echo $this->Form->input('value', array(
                    'div' => false,
                    'label' => false,
                    'type' => 'text',
                    'name' => sprintf('data[CensusTeacher][%d][value]', $i+1),
                    'computeType' => 'total_female',
                    'value' => 0,
                    'maxlength' => 10,
                    'onkeypress' => 'return utility.integerCheck(event)',
                    'onkeyup' => 'jsTable.computeTotal(this)'
                ));
                ?>
            </div>
        </td>
        <td class="table_cell">
    <?php echo $this->Utility->getDeleteControl(array('onclick' => "jsTable.computeAllTotal('.multi');")); ?>
        </td>
    </tr>
    <?php if ($body == 0) {
        echo '</tbody>';
    } ?>

<?php } else { ?>

    <div class="alert none" type="0"><?php echo __("There are not enough grades."); ?></div>

<?php } ?>