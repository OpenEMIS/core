<?php
App::uses('AppModel', 'Model');

class EducationProgramme extends AppModel {
	/*
	public $validate = array(
		'name' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please enter a name for the programme.'
		),
		'education_field_of_study_id' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please choose a field of study.'
		),
		'education_certification_id' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please select a certification.'
		),
		'duration' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please enter the duration.'
		)
	);
	*/
	
	public $belongsTo = array('EducationCycle', 'EducationFieldOfStudy', 'EducationCertification');
	public $hasMany = array('EducationGrade', 'InstitutionSiteProgramme');
	
	// Used by InstitutionSiteController->programmeAdd
	public function getAvailableProgrammeOptions($institutionSiteId, $yearId) {
		$table = 'institution_site_programmes';
		$notExists = 'NOT EXISTS (SELECT %s.id FROM %s WHERE %s.institution_site_id = %d AND %s.school_year_id = %d AND %s.education_programme_id = EducationProgramme.id)';
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'EducationSystem.name', 'EducationLevel.name', 
				'EducationCycle.name', 'EducationProgramme.id', 'EducationProgramme.name'
			),
			'joins' => array(
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id', 'EducationCycle.visible = 1')
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id', 'EducationLevel.visible = 1')
				),
				array(
					'table' => 'education_systems',
					'alias' => 'EducationSystem',
					'conditions' => array('EducationSystem.id = EducationLevel.education_system_id', 'EducationSystem.visible = 1')
				)
			),
			'conditions' => array(
				sprintf($notExists, $table, $table, $table, $institutionSiteId, $table, $yearId, $table),
				'EducationProgramme.visible' => 1
			),
			'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));
		return $data;
	}
	
	public function getProgrammeOptions($visible = true, $cycleName = true) {
		$conditions = array();
		if($visible) {
			$conditions['EducationProgramme.visible'] = 1;
		}
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('EducationProgramme.id', 'EducationProgramme.name', 'EducationCycle.name'),
			'joins' => array(
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')
				)
			),
			'conditions' => $conditions,
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationLevel.order')
		));
		
		$options = array();
		foreach($data as $obj) {
			$programme = $obj['EducationProgramme'];
			$cycle = $obj['EducationCycle'];
			if($cycleName) {
				$options[$programme['id']] = $cycle['name'] . ' - ' . $programme['name'];
			} else {
				$options[$programme['id']] = $programme['name'];
			}
		}
		return $options;
	}
}
