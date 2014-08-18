<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

App::uses('AppModel', 'Model');

class EducationProgramme extends AppModel {
	public $actsAs = array('ControllerAction', 'Reorder');
	public $belongsTo = array('EducationCycle', 'EducationFieldOfStudy', 'EducationCertification');
	public $hasMany = array('EducationGrade', 'InstitutionSiteProgramme');
	
	public $validate = array(
		'code' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please enter a code'
		),
		'name' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please enter a name'
		),
		'education_field_of_study_id' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please choose a field of study'
		),
		'education_certification_id' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please select a certification'
		),
		'duration' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter the duration'
			),
			'numeric' => array(
				'rule' => 'numeric',
				'message' => 'Please enter a numeric value'
			)
		)
	);
	
	public $_action = 'programmes';
	public $_header = 'Education Programmes';
	public $_condition = 'education_cycle_id';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$controller->Navigation->addCrumb($this->_header);
		$controller->set('header', __($this->_header));
		$controller->set('_action', $this->_action);
		$controller->set('selectedAction', $this->_action);
		$controller->set('_condition', $this->_condition);
	}
	
	public function getDisplayFields($controller) {
		$yesnoOptions = $controller->Option->get('yesno');
		$cycleOptions = $this->EducationCycle->find('list', array('order' => 'order'));
		$fieldOfStudyOptions = $this->EducationFieldOfStudy->getList();
		$certificateOptions = $this->EducationCertification->find('list', array('order' => 'order'));
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'code'),
				array('field' => 'name'),
				array('field' => 'duration'),
				array('field' => 'education_cycle_id', 'type' => 'select', 'options' => $cycleOptions, 'edit' => false),
				array('field' => 'education_field_of_study_id', 'type' => 'select', 'options' => $fieldOfStudyOptions),
				array('field' => 'education_certification_id', 'type' => 'select', 'options' => $certificateOptions),
				array('field' => 'visible', 'type' => 'select', 'options' => $yesnoOptions),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function programmes($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationCycle->exists($conditionId)) {
			$data = $this->findAllByEducationCycleId($conditionId, array(), array($this->alias.'.order' => 'ASC'));
			$cycleObj = $this->EducationCycle->findById($conditionId);
			$systemObj = ClassRegistry::init('EducationSystem')->findById($cycleObj['EducationLevel']['education_system_id']);
			$paths = array();
			$paths[] = array(
				'name' => $systemObj['EducationSystem']['name'],
				'url' => array('action' => 'systems')
			);
			$paths[] = array(
				'name' => $cycleObj['EducationLevel']['name'],
				'url' => array('action' => 'levels', 'education_system_id' => $systemObj['EducationSystem']['id'])
			);
			$paths[] = array(
				'name' => $cycleObj['EducationCycle']['name'],
				'url' => array('action' => 'cycles', 'education_level_id' => $cycleObj['EducationLevel']['id'])
			);
			$paths[] = array(
				'name' => '(' . __($this->_header) . ')'
			);
			$controller->set(compact('data', 'paths', 'conditionId'));
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'systems'));
		}
	}
	
	public function programmesAdd($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationCycle->exists($conditionId)) {
			$cycleName = $this->EducationCycle->field('name', array('EducationCycle.id' => $conditionId));
			$cycleOptions = array($conditionId => $cycleName);
			$fieldOfStudyOptions = $this->EducationFieldOfStudy->getList(1);
			$certificateOptions = $this->EducationCertification->find('list', array('conditions' => array('visible' => 1), 'order' => 'order'));
			$controller->set(compact('cycleOptions', 'conditionId', 'fieldOfStudyOptions', 'certificateOptions', 'orientationOptions'));
			if($controller->request->is('post') || $controller->request->is('put')) {
				$controller->request->data[$this->alias]['education_cycle_id'] = $conditionId;
				$controller->request->data[$this->alias]['order'] = $this->field('order', array(), 'order DESC') + 1;
				if ($this->save($controller->request->data)) {
					$controller->Message->alert('general.add.success');
					return $controller->redirect(array('action' => $this->_action, $this->_condition => $conditionId));
				}
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'systems'));
		}
	}
	
	public function programmesView($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationCycle->exists($conditionId)) {
			$id = isset($params->pass[0]) ? $params->pass[0] : 0;
			$data = $this->findById($id);
			$fields = $this->getDisplayFields($controller);
			$controller->set(compact('data', 'fields', 'conditionId'));
			$this->render = '../template/view';
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'systems'));
		}
	}
	
	public function programmesEdit($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationCycle->exists($conditionId)) {
			$id = isset($params->pass[0]) ? $params->pass[0] : 0;
			$data = $this->findById($id);
			
			if(!empty($data)) {
				$fields = $this->getDisplayFields($controller);
				$controller->set(compact('fields', 'conditionId'));
				if($controller->request->is('post') || $controller->request->is('put')) {
					if ($this->save($controller->request->data)) {
						$controller->Message->alert('general.edit.success');
						return $controller->redirect(array('action' => $this->_action.'View', $this->_condition => $conditionId, $id));
					}
				} else {
					$controller->request->data = $data;
				}
				$this->render = '../template/edit';
			} else {
				$controller->Message->alert('general.notExists');
				return $controller->redirect(array('action' => $this->_action, $this->_condition => $conditionId));
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'systems'));
		}
	}
	
	public function getDurationBySiteProgramme($siteProgrammeId) {
		$obj = $this->find('first', array(
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.education_programme_id = EducationProgramme.id',
						'InstitutionSiteProgramme.id = ' . $siteProgrammeId
					)
				)
			)
		));
		return $obj['EducationProgramme']['duration'];
	}
	
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
	
	// Used by Assessment
	public function getProgrammeOptions($visible = true, $cycleName = true) {
		$conditions = array();
		$cycleConditions = array('EducationCycle.id = EducationProgramme.education_cycle_id');
		$levelConditions = array('EducationLevel.id = EducationCycle.education_level_id');
		$systemConditions = array('EducationSystem.id = EducationLevel.education_system_id');
		if($visible) {
			$conditions['EducationProgramme.visible'] = 1;
			$cycleConditions[] = 'EducationCycle.visible = 1';
			$levelConditions[] = 'EducationLevel.visible = 1';
			$systemConditions[] = 'EducationSystem.visible = 1';
		}
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('EducationProgramme.id', 'EducationProgramme.name', 'EducationCycle.name'),
			'joins' => array(
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => $cycleConditions
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => $levelConditions
				),
				array(
					'table' => 'education_systems',
					'alias' => 'EducationSystem',
					'conditions' => $systemConditions
				)
			),
			'conditions' => $conditions,
			'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationLevel.order')
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
	
	// used by InstitutionSiteStudent
	public function getProgrammeOptionsByInstitution($institutionSiteId, $yearId, $visible = false) {
		$conditions = array(
			'InstitutionSiteProgramme.education_programme_id = EducationProgramme.id',
			'InstitutionSiteProgramme.school_year_id = ' . $yearId,
			'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId
		);
		
		if ($visible !== false) {
			$conditions[] = 'InstitutionSiteProgramme.status = ' . $visible;
		}
		
		$data = $this->find('all', array(
			'fields' => array('EducationProgramme.id', 'EducationCycle.name', 'EducationCycle.id', 'EducationProgramme.name'),
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => $conditions
				)
			),
			'order' => array('EducationCycle.order', 'EducationProgramme.order')
		));
		
		$list = array();
		foreach($data as $obj) {
			if (!empty($obj['EducationCycle']['id'])) {
				$id = $obj['EducationProgramme']['id'];
				$cycle = $obj['EducationCycle']['name'];
				$programme = $obj['EducationProgramme']['name'];
				$list[$id] = $cycle . ' - ' . $programme;
			}
		}
		return $list;
	}
	
	// Used by Yearbook
	public function getEducationStructure() {
		$list = $this->find('all', array(
			'fields' => array(
				'EducationSystem.id', 'EducationSystem.name', 'EducationLevel.name', 
				'EducationCycle.name', 'EducationProgramme.id', 'EducationProgramme.name', 
				'EducationFieldOfStudy.name', 'EducationProgrammeOrientation.name', 'EducationCertification.name'
			),
			'recursive' => -1,
			'joins' => array(
				array('table' => 'education_cycles', 'alias' => 'EducationCycle', 
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')),
				array('table' => 'education_levels', 'alias' => 'EducationLevel', 
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')),
				array('table' => 'education_systems', 'alias' => 'EducationSystem', 
					'conditions' => array('EducationSystem.id = EducationLevel.education_system_id')),
				array('table' => 'education_field_of_studies', 'alias' => 'EducationFieldOfStudy', 
					'conditions' => array('EducationFieldOfStudy.id = EducationProgramme.education_field_of_study_id')),
				array('table' => 'education_programme_orientations', 'alias' => 'EducationProgrammeOrientation', 
					'conditions' => array('EducationProgrammeOrientation.id = EducationFieldOfStudy.education_programme_orientation_id')),
				array('table' => 'education_certifications', 'alias' => 'EducationCertification', 
					'conditions' => array('EducationCertification.id = EducationProgramme.education_certification_id'))
			),
			'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));
		$EducationGrade = ClassRegistry::init('EducationGrade');
		$data = array();
		foreach($list as $item) {
			$system = $item['EducationSystem'];
			if(!array_key_exists($system['id'], $data)) {
				$data[$system['id']] = array();
			}
			$grades = $EducationGrade->getGradeOptions($item['EducationProgramme']['id'], null, true);
			$data[$system['id']][] = array(
				'system' => $system['name'],
				'level' => $item['EducationLevel']['name'],
				$this->_condition => $item['EducationCycle']['name'],
				'programme' => $item['EducationProgramme']['name'],
				'field' => $item['EducationFieldOfStudy']['name'],
				'orientation' => $item['EducationProgrammeOrientation']['name'],
				'certification' => $item['EducationCertification']['name'],
				'grades' => $grades
			);
		}
		return $data;
	}
		
	public function getProgrammeById($programmeId) {
		$this->formatResult = true;
		$data = $this->find('first', array(
			'recursive' => -1,
			'fields' => array(
				'EducationSystem.name AS education_system_name',
				'EducationCycle.name AS education_cycle_name',
				'EducationCycle.admission_age AS admission_age',
				'EducationProgramme.name AS education_programme_name'
			),
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
				),
				array(
					'table' => 'education_systems',
					'alias' => 'EducationSystem',
					'conditions' => array('EducationSystem.id = EducationLevel.education_system_id')
				)
			),
			'conditions' => array(
				'EducationProgramme.id' => $programmeId
			)
		));
		return $data;
	}
}
