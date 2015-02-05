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
	public $actsAs = array('ControllerAction2', 'Reorder');
	public $belongsTo = array(
		'EducationCycle', 
		'EducationFieldOfStudy', 
		'EducationCertification',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id'
		)
	);
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

	public $virtualFields = array(
		'_name' => "SELECT CONCAT(`EducationCycle`.`name`, ' - ', `EducationProgramme`.`name`) from `education_cycles` AS `EducationCycle` WHERE `EducationCycle`.`id` = `EducationProgramme.education_cycle_id`"
	);
	
	public $_condition = 'education_cycle_id';
	
	public function beforeAction() {
		parent::beforeAction();
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		$this->setVar('conditionId', $conditionId);
		
		$this->fields['order']['visible'] = false;
		$this->fields['education_cycle_id']['type'] = 'disabled';
		$this->fields['education_field_of_study_id']['type'] = 'select';
		$this->fields['education_field_of_study_id']['options'] = $this->EducationFieldOfStudy->getList();
		$this->fields['education_certification_id']['type'] = 'select';
		$this->fields['education_certification_id']['options'] = $this->EducationCertification->find('list', array('conditions' => array('visible' => 1), 'order' => 'order'));
		
		if ($this->action == 'add') {
			$this->fields['order']['type'] = 'hidden';
			$this->fields['order']['visible'] = true;
			$this->fields['order']['value'] = 0;
			$this->fields['visible']['type'] = 'hidden';
			$this->fields['visible']['value'] = 1;
			$this->fields['education_cycle_id']['type'] = 'hidden';
			$this->fields['education_cycle_id']['value'] = $conditionId;
			$this->fields['education_cycle'] = array(
				'visible' => true,
				'type' => 'disabled',
				'value' => $this->EducationCycle->field('name', array('EducationCycle.id' => $conditionId))
			);
			$this->setFieldOrder('education_cycle', 0);
		} else {
			$this->fields['visible']['type'] = 'select';
			$this->fields['visible']['options'] = $this->controller->Option->get('yesno');
			$this->fields['education_cycle_id']['dataModel'] = 'EducationCycle';
			$this->fields['education_cycle_id']['dataField'] = 'name';
		}
		$this->setFieldOrder('education_cycle_id', 1);
		
		$this->Navigation->addCrumb('Education Programmes');
		
		$this->setVar('selectedAction', 'EducationSystem');
		$this->setVar('_condition', $this->_condition);
	}
	
	public function index() {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationCycle->exists($conditionId)) {
			$data = $this->findAllByEducationCycleId($conditionId, array(), array($this->alias.'.order' => 'ASC'));
			$cycleObj = $this->EducationCycle->findById($conditionId);
			$systemObj = ClassRegistry::init('EducationSystem')->findById($cycleObj['EducationLevel']['education_system_id']);
			$paths = array();
			$paths[] = array(
				'name' => $systemObj['EducationSystem']['name'],
				'url' => array('action' => 'EducationSystem')
			);
			$paths[] = array(
				'name' => $cycleObj['EducationLevel']['name'],
				'url' => array('action' => 'EducationLevel', 'education_system_id' => $systemObj['EducationSystem']['id'])
			);
			$paths[] = array(
				'name' => $cycleObj['EducationCycle']['name'],
				'url' => array('action' => 'EducationCycle', 'education_level_id' => $cycleObj['EducationLevel']['id'])
			);
			$paths[] = array(
				'name' => '(' . __('Education Programmes') . ')'
			);
			$this->setVar(compact('data', 'paths'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function add() {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		$this->render = '../template/add';
		if($this->EducationCycle->exists($conditionId)) {
			if($this->request->is(array('post', 'put'))) {
				$this->request->data[$this->alias]['order'] = $this->field('order', array(), 'order DESC') + 1;
				if ($this->save($this->request->data)) {
					$this->Message->alert('general.add.success');
					return $this->redirect(array('action' => get_class($this), $this->_condition => $conditionId));
				}
			}
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function view($id) {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationCycle->exists($conditionId)) {
			$data = $this->findById($id);
			$this->setVar(compact('data'));
			$this->render = '../template/view';
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function edit($id) {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationCycle->exists($conditionId)) {
			$data = $this->findById($id);
			
			if(!empty($data)) {
				if($this->request->is(array('post', 'put'))) {
					if ($this->save($this->request->data)) {
						$this->Message->alert('general.edit.success');
						return $this->redirect(array('action' => get_class($this), 'view', $this->_condition => $conditionId, $id));
					}
				} else {
					$this->request->data = $data;
				}
				$this->render = '../template/edit';
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => get_class($this), $this->_condition => $conditionId));
			}
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	// Used by InstitutionSiteController->programmeAdd
	public function getAvailableProgrammeOptions($institutionSiteId, $academicPeriodId) {
		$table = 'institution_site_programmes';
		$notExists = 'NOT EXISTS (SELECT %s.id FROM %s WHERE %s.institution_site_id = %d AND %s.academic_period_id = %d AND %s.education_programme_id = EducationProgramme.id)';
		
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
				sprintf($notExists, $table, $table, $table, $institutionSiteId, $table, $academicPeriodId, $table),
				'EducationProgramme.visible' => 1
			),
			'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));
		return $data;
	}
	
	// Used by Assessment
	public function getProgrammeOptions($visible = true, $cycleName = true) {
		$data = $this->getProgrammeOptionsData($visible, $cycleName);
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
	
	public function getProgrammeOptionsData($visible = true, $cycleName = true) {
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
		return $data;
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

	public function getOptionsByEducationLevelId($educationLevelId) {
		$this->contain('EducationCycle');
		$list = $this->find('list', array(
			'fields' => array(
				'EducationProgramme.id', 'EducationProgramme._name'
			),
			'conditions' => array(
				'EducationCycle.education_level_id' => $educationLevelId
			),
			'order' => array(
				'EducationCycle.order', 'EducationProgramme.order'
			)
		));

		return $list;
	}
}
