<?php
$displayDefault = array();
foreach($programmeGrades as $obj) {
	foreach($obj['education_grades'] as $gradeId => $grade) {
		if(sizeof($displayDefault) < 2) {
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

if($body==0) {
	echo '<div class="table_body">';
}
?>

<div class="table_row" row="<?php echo $i; ?>">
	<div class="table_cell programme_list">
		<div class="table_cell_row">
		<?php
			echo $this->Form->input('education_programme_id', array(
				'url' => 'Census/loadGradeList',
				'div' => false,
				'label' => false,
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
				'options' => $programmes,
				'default' => $displayDefault[1]['programme'],
				'onchange' => 'Census.loadGradeList(this)',
				'index' => 1
			));
		?>
		</div>
		<div class="row last">
			<a class="void icon_plus" url="Census/teachersAddMultiGrade" onclick="Census.addMultiGrade(this)"><?php echo __('Add').' '.__('Grade'); ?></a>
		</div>
	</div>
	
	<div class="table_cell grade_list">
		<div class="table_cell_row">
		<?php
			echo $this->Form->input('education_grade_id', array(
				'div' => false,
				'label' => false,
				'name' => sprintf('data[CensusTeacher][%d][CensusTeacherGrade][0]', $i),
				'options' => $displayDefault[0]['grades'],
				'default' => $displayDefault[0]['selectedGrade'],
				'index' => 0
			));
		?>
		</div>
		<div class="table_cell_row">
		<?php
			echo $this->Form->input('education_grade_id', array(
				'div' => false,
				'label' => false,
				'name' => sprintf('data[CensusTeacher][%d][CensusTeacherGrade][1]', $i),
				'options' => $displayDefault[1]['grades'],
				'default' => $displayDefault[1]['selectedGrade'],
				'index' => 1
			));
		?>
		</div>
	</div>
	
	<div class="table_cell">
		<div class="input_wrapper">
		<?php echo $this->Form->input('male', array(
				'div' => false,
				'label' => false,
				'type' => 'text',
				'name' => sprintf('data[CensusTeacher][%d][male]', $i),
				'computeType' => 'total_male',
				'value' => 0,
				'maxlength' => 10,
				'onkeypress' => 'return utility.integerCheck(event)',
				'onkeyup' => 'jsTable.computeTotal(this)'
			));
		?>
		</div>
	</div>
	<div class="table_cell">
		<div class="input_wrapper">
		<?php echo $this->Form->input('female', array(
				'div' => false,
				'label' => false,
				'type' => 'text',
				'name' => sprintf('data[CensusTeacher][%d][female]', $i),
				'computeType' => 'total_female',
				'value' => 0,
				'maxlength' => 10,
				'onkeypress' => 'return utility.integerCheck(event)',
				'onkeyup' => 'jsTable.computeTotal(this)'
			));
		?>
		</div>
	</div>
	<div class="table_cell">
		<?php echo $this->Utility->getDeleteControl(array('onclick' => "jsTable.computeAllTotal('.multi');")); ?>
	</div>
</div>

<?php
if($body==0) {
	echo '</div>';
}
?>