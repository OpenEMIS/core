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

class EducationGrade extends AppModel {
	public $actsAs = array(
		'ControllerAction2',
		'Reorder'
	);
	public $hasMany = array('EducationGradeSubject');
	public $belongsTo = array(
		'EducationProgramme',
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
	
	public $validate = array(
		'code' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a code'
			)
		),
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			)
		)
	);

	public $virtualFields = array(
		'programme_grade_name' => "SELECT CONCAT(`EducationProgramme`.`name`, ' - ', `EducationGrade`.`name`) from `education_programmes` AS `EducationProgramme` WHERE `EducationProgramme`.`id` = `EducationGrade.education_programme_id`"
	);
	
	public $_condition = 'education_programme_id';
	
	public function beforeAction() {
		parent::beforeAction();
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		$this->setVar('conditionId', $conditionId);
		
		$this->fields['order']['visible'] = false;
		$this->fields['education_programme_id']['type'] = 'disabled';
		
		if ($this->action == 'add') {
			$this->fields['order']['type'] = 'hidden';
			$this->fields['order']['visible'] = true;
			$this->fields['order']['value'] = 0;
			$this->fields['visible']['type'] = 'hidden';
			$this->fields['visible']['value'] = 1;
			$this->fields['education_programme_id']['type'] = 'hidden';
			$this->fields['education_programme_id']['value'] = $conditionId;
			$this->fields['education_programme'] = array(
				'visible' => true,
				'type' => 'disabled',
				'value' => $this->EducationProgramme->field('name', array('EducationProgramme.id' => $conditionId))
			);
			$this->setFieldOrder('education_programme', 0);
		} else {
			$this->fields['visible']['type'] = 'select';
			$this->fields['visible']['options'] = $this->controller->Option->get('yesno');
			$this->fields['education_programme_id']['dataModel'] = 'EducationProgramme';
			$this->fields['education_programme_id']['dataField'] = 'name';
		}
		$this->setFieldOrder('education_programme_id', 1);
		
		$this->Navigation->addCrumb('Education Grades');
		
		$this->setVar('selectedAction', 'EducationSystem');
		$this->setVar('_condition', $this->_condition);
	}
	
	public function index() {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationProgramme->exists($conditionId)) {
			$data = $this->findAllByEducationProgrammeId($conditionId, array(), array($this->alias.'.order' => 'ASC'));
			$programmeObj = $this->EducationProgramme->findById($conditionId);
			$levelObj = ClassRegistry::init('EducationLevel')->findById($programmeObj['EducationCycle']['education_level_id']);
			$paths = array();
			$paths[] = array(
				'name' => $levelObj['EducationSystem']['name'],
				'url' => array('action' => 'EducationSystem')
			);
			$paths[] = array(
				'name' => $levelObj['EducationLevel']['name'],
				'url' => array('action' => 'EducationLevel', 'education_system_id' => $levelObj['EducationSystem']['id'])
			);
			$paths[] = array(
				'name' => $programmeObj['EducationCycle']['name'],
				'url' => array('action' => 'EducationCycle', 'education_level_id' => $levelObj['EducationLevel']['id'])
			);
			$paths[] = array(
				'name' => $programmeObj['EducationProgramme']['name'],
				'url' => array('action' => 'EducationProgramme', 'education_cycle_id' => $programmeObj['EducationCycle']['id'])
			);
			$paths[] = array(
				'name' => '(' . __('Education Grades') . ')'
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
		if($this->EducationProgramme->exists($conditionId)) {
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
	
	public function view($id=0) {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationProgramme->exists($conditionId)) {
			$data = $this->findById($id);
			$this->setVar(compact('data'));
			$this->render = '../template/view';
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function edit($id=0) {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationProgramme->exists($conditionId)) {
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
	
	// Used by InstitutionSiteFee
	public function getGradeOptionsByInstitutionAndAcademicPeriod($institutionSiteId, $academicPeriodId, $visible = false) {
		$institutionSiteProgrammeConditions = array(
			'InstitutionSiteProgramme.education_programme_id = EducationGrade.education_programme_id',
			'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId
		);
		$institutionSiteProgrammeConditions = ClassRegistry::init('InstitutionSiteProgramme')->getConditionsByAcademicPeriodId($academicPeriodId, $institutionSiteProgrammeConditions);

		$conditions = array();
		if ($visible !== false) {
			$conditions['EducationProgramme.visible'] = 1;
			$conditions['EducationGrade.visible'] = 1;
		}
		
		$list = $this->find('all', array(
			'recursive' => 0,
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => $institutionSiteProgrammeConditions
				)
			),
			'conditions' => $conditions,
			'order' => array('EducationProgramme.order', 'EducationGrade.order')
		));
		
		$data = array();
		foreach ($list as $obj) {
			$grade = $obj['EducationGrade'];
			$data[$grade['id']] = $obj['EducationProgramme']['name'] . ' - ' . $grade['name'];
		}
		
		return $data;
	}
	
	public function findListAsSubgroups() {
		return $this->findList(true);
	}
	
	public function getGradeOptions($programmeId, $exclude=array(), $onlyVisible=false) {
		$conditions = array('EducationGrade.education_programme_id' => $programmeId);
		
		if(!empty($exclude)) {
			$conditions['EducationGrade.id NOT'] = $exclude;
		}
		
		if($onlyVisible) {
			$conditions['EducationGrade.visible'] = 1;
		}
		
		$options = array(
			'recursive' => -1,
			'fields' => array('EducationGrade.id', 'EducationGrade.name'),
			'conditions' => $conditions,
			'order' => array('EducationGrade.order')
		);
		$data = $this->find('list', $options);
		return $data;
	}
}
