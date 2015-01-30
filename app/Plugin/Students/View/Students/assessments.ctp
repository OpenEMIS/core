<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
if (count($academicPeriods) != 0 && count($programmeGrades) != 0) {
	echo $this->Form->create('Institution', array('style' => 'margin-bottom:20px;'));
	?>
	<div class="row myyear">
		<div class="col-md-2"><?php echo __('Academic Period'); ?></div>
		<div class="col-md-3">
			<?php
			echo $this->Form->input('academic_period_id', array(
				'label' => false,
				'class' => 'form-control',
				'div' => false,
				'options' => $academicPeriods,
				'default' => $selectedAcademicPeriod,
				'onchange' => '',
				'name' => "data[academicPeriod]"
			));
			?>
		</div>
	</div>

	<div class="row school_days">
		<div class="col-md-2"><?php echo __('Programme - Grade'); ?></div>
		<div class="col-md-6">
			<?php
			echo $this->Form->input('education_grade_id', array(
				'label' => false,
				'class' => 'form-control',
				'div' => false,
				'options' => $programmeGrades,
				'default' => $selectedProgrammeGrade,
				'name' => "data[programmeGrade]"
			));
			echo $this->Form->hidden('programme_grade_count', array(
				'value' => count($programmeGrades),
				'name' => "data[programmeGradeCount]"
			));
			?>
		</div>
	</div>
	<?php
	echo $this->Form->end();
}

if (isset($data) && !empty($data)) {
	foreach ($data as $institutionKey => $institutionRow) {
		echo '<fieldset class="section_group">';
		echo '<legend>' . $institutionKey . '</legend>';

		foreach ($institutionRow as $subjectKKey => $subjectRow) {
			echo '<fieldset class="custom_section_break"><legend>' . $subjectKKey . '</legend></fieldset>';
			$tableHeaders = array(__('Code'), __('Assessment'), __('Marks'), __('Grading'));
			$tableData = array();
			foreach ($subjectRow as $assessmentRow) {
				$row = array();
				$row[] = empty($assessmentRow['assessment']['code']) ? 0 : $assessmentRow['assessment']['code'];
				$row[] = empty($assessmentRow['assessment']['name']) ? 0 : $assessmentRow['assessment']['name'];
				$row[] = array(empty($assessmentRow['marks']['value']) ? 0 : $assessmentRow['marks']['value'], array('class' => (intval($assessmentRow['marks']['value']) >= intval($assessmentRow['marks']['min']))? : "red"));
				$row[] = empty($assessmentRow['grading']) ? 0 : $assessmentRow['grading']['name'];
				$tableData[] = $row;
			}
			echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
		}

		echo '</fieldset>';
	}
}
?>
<script type="text/javascript">
	$(document).ready(function(){
		$('#InstitutionSchoolAcademicPeriodId,#InstitutionEducationGradeId').change(function(e){
			$(this).closest('form').submit();
		})
	});
</script>
<?php
$this->end();
?>
