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
	public $actsAs = array('ControllerAction', 'Reorder');
	public $hasMany = array('EducationGradeSubject');
	public $belongsTo = array('EducationProgramme');
	
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
	
	public $_action = 'grades';
	public $_header = 'Education Grades';
	public $_condition = 'education_programme_id';
	
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
		$programmeOptions = $this->EducationProgramme->find('list', array('order' => 'order'));
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'code'),
				array('field' => 'name'),
				array('field' => 'education_programme_id', 'type' => 'select', 'options' => $programmeOptions, 'edit' => false),
				array('field' => 'visible', 'type' => 'select', 'options' => $yesnoOptions),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function grades($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationProgramme->exists($conditionId)) {
			$data = $this->findAllByEducationProgrammeId($conditionId, array(), array($this->alias.'.order' => 'ASC'));
			$programmeObj = $this->EducationProgramme->findById($conditionId);
			$levelObj = ClassRegistry::init('EducationLevel')->findById($programmeObj['EducationCycle']['education_level_id']);
			$paths = array();
			$paths[] = array(
				'name' => $levelObj['EducationSystem']['name'],
				'url' => array('action' => 'systems')
			);
			$paths[] = array(
				'name' => $levelObj['EducationLevel']['name'],
				'url' => array('action' => 'levels', 'education_system_id' => $levelObj['EducationSystem']['id'])
			);
			$paths[] = array(
				'name' => $programmeObj['EducationCycle']['name'],
				'url' => array('action' => 'cycles', 'education_level_id' => $levelObj['EducationLevel']['id'])
			);
			$paths[] = array(
				'name' => $programmeObj['EducationProgramme']['name'],
				'url' => array('action' => 'programmes', 'education_cycle_id' => $programmeObj['EducationCycle']['id'])
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
	
	public function gradesAdd($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationProgramme->exists($conditionId)) {
			$programmeName = $this->EducationProgramme->field('name', array('EducationProgramme.id' => $conditionId));
			$programmeOptions = array($conditionId => $programmeName);
			$controller->set(compact('programmeOptions', 'conditionId'));
			if($controller->request->is('post') || $controller->request->is('put')) {
				$controller->request->data[$this->alias]['education_programme_id'] = $conditionId;
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
	
	public function gradesView($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationProgramme->exists($conditionId)) {
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
	
	public function gradesEdit($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationProgramme->exists($conditionId)) {
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
	
	// Used by InstitutionSiteFee
	public function getGradeOptionsByInstitutionAndSchoolYear($institutionSiteId, $yearId, $visible = false) {
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
					'conditions' => array(
						'InstitutionSiteProgramme.education_programme_id = EducationGrade.education_programme_id',
						'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId,
						'InstitutionSiteProgramme.school_year_id = ' . $yearId,
						'InstitutionSiteProgramme.status = 1'
					)
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
