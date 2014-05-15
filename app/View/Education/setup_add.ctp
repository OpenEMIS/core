<?php
$category = $params['category']; unset($params['category']);
$index = $params['count']+1; unset($params['count']);

$models = array(
	'System' => 'EducationSystem',
	'Level' => 'EducationLevel',
	'Cycle' => 'EducationCycle',
	'Programme' => 'EducationProgramme',
	'Grade' => 'EducationGrade',
	'GradeSubject' => 'EducationGradeSubject',
	'FieldOfStudy' => 'EducationFieldOfStudy',
	'Certification' => 'EducationCertification',
	'Subject' => 'EducationSubject'
);
$fieldName = $fieldName = sprintf('data[%s][%s][%%s]', $models[$category], $index);
$nameField = $this->Form->input('name', array(
				'name' => sprintf($fieldName, 'name'),
				'type' => 'text',
				'label' => false,
				'class'=> 'form-control',
				'div' => false,
				'maxlength' => 50,
			));
?>

<tr data-id="<?php echo $index; ?>" class="new_row">
	<?php
	//echo $this->Utility->getIdInput($this->Form, $fieldName, 0);
	//echo $this->Utility->getOrderInput($this->Form, $fieldName, $index);
	
	foreach($params as $key => $val) {
		echo $this->Form->hidden($key, array('name' => sprintf($fieldName, $key), 'value' => $val));
	}
	
	//echo $this->Utility->getVisibleInput($this->Form, $fieldName, true, true);

	echo '<td class="center">';
	
	$options = array(
		'name' => sprintf($fieldName, 'visible'),
		'type' => 'checkbox',
		'value' => 1,
		'autocomplete' => 'off',
		'onchange' => 'jsList.activate(this)',
	);
	
	$options['checked'] = 'checked';
	$options['label'] = false;
	$options['div'] = false;

	echo $this->form->input('visible', $options);

	echo '</td>';
	
	if($category === 'Subject' || $category === 'GradeSubject' || $category === 'Grade') {
		echo '<td class="cell cell_subject_code">';
		if($category === 'Subject' || $category === 'Grade') {
			echo $this->Form->input('code', array(
				'name' => sprintf($fieldName, 'code'),
				'label' => false,
				'div' => false,
				'maxlength' => '30'
			));
		} else {
			echo '&nbsp;';
		}
		echo '</td>';
	}
	
	if($category !== 'GradeSubject') {
		echo sprintf('<td>%s</td>', $nameField);
	}
	
	if($category === 'Level') {
		echo '<td class="cell cell_isced">';
		echo $this->Form->select('education_level_isced_id', $isced,
			array(
				'name' => sprintf($fieldName, 'education_level_isced_id'),
				'onchange' => 'jsList.attachSelectedEvent(this)',
				'empty' => false,
				'label' => false,
				'div' => false
			)
		);
		echo '</td>';
	}
	
	else if($category === 'Cycle') {
		echo '<td class="cell cell_admission_age">';
		echo $this->Form->input('admission_age', array(
			'name' => sprintf($fieldName, 'admission_age'),
			'label' => false,
			'div' => false,
			'maxlength' => 2
		));
		echo '</td>';
	} else if($category === 'Grade') {
		echo '<td class="cell cell_subject_link">&nbsp;</td>';
	} else if($category === 'GradeSubject') {
		echo '<td class="cell cell_grade_subject">';
		echo $this->Form->select('education_subject_id', $subjects,
			array(
				'class' => 'EducationSubjectId',
				'name' => sprintf($fieldName, 'education_subject_id'),
				'empty' => false,
				'label' => false,
				'div' => false
			)
		);
		echo '</td>';
		echo $this->Form->input('hours_required', array(
			'div' => false,
			'label' => false,
			'name' => sprintf($fieldName, 'hours_required'),
			'type' => 'text',
			'value' => 0,
			'before' => '<div class="cell cell_hours"><div class="input_wrapper">',
			'after' => '</div></div>',
			'maxlength' => '5',
			'onkeypress' => 'return utility.integerCheck(event)'
		));
	} else if($category === 'FieldOfStudy') {
		echo '<td class="cell cell_orientation">';
		echo $this->Form->select('education_programme_orientation_id', $orientation,
			array(
				'name' => sprintf($fieldName, 'education_programme_orientation_id'),
				'onchange' => 'jsList.attachSelectedEvent(this)',
				'empty' => false,
				'label' => false,
				'div' => false
			)
		);
		echo '</td>';
	} 
	?>

	<td class="action">
	<?php
	$size = $index;
	echo $this->element('layout/reorder', compact('index', 'size'));
	
	?>
	</td>
</tr>