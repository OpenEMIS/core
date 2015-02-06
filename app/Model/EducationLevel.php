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

class EducationLevel extends AppModel {
	public $actsAs = array('ControllerAction2', 'Reorder');
	public $belongsTo = array(
		'EducationSystem',
		'EducationLevelIsced',
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
	public $hasMany = array('EducationCycle');
	
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			)
		)
	);

	public $virtualFields = array(
		'_name' => "SELECT CONCAT(`EducationSystem`.`name`, ' - ', `EducationLevel`.`name`) from `education_systems` AS `EducationSystem` WHERE `EducationSystem`.`id` = `EducationLevel.education_system_id`"
	);
	
	public $_condition = 'education_system_id';
	
	public function beforeAction() {
		parent::beforeAction();
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		$this->setVar('conditionId', $conditionId);
		
		$this->fields['order']['visible'] = false;
		$this->fields['education_level_isced_id']['type'] = 'select';
		$this->fields['education_level_isced_id']['options'] = $this->EducationLevelIsced->find('list', array('order' => 'order'));
		$this->fields['education_system_id']['type'] = 'disabled';
		
		if ($this->action == 'add') {
			$this->fields['order']['type'] = 'hidden';
			$this->fields['order']['visible'] = true;
			$this->fields['order']['value'] = 0;
			$this->fields['visible']['type'] = 'hidden';
			$this->fields['visible']['value'] = 1;
			$this->fields['education_system_id']['type'] = 'hidden';
			$this->fields['education_system_id']['value'] = $conditionId;
			$this->fields['education_system'] = array(
				'visible' => true,
				'type' => 'disabled',
				'value' => $this->EducationSystem->field('name', array('EducationSystem.id' => $conditionId))
			);
		} else {
			$this->fields['visible']['type'] = 'select';
			$this->fields['visible']['options'] = $this->controller->Option->get('yesno');
			$this->fields['education_system_id']['dataModel'] = 'EducationSystem';
			$this->fields['education_system_id']['dataField'] = 'name';
		}
		$this->Navigation->addCrumb('Education Levels');
		
		$this->setVar('_condition', $this->_condition);
		$this->setVar('selectedAction', 'EducationSystem');
	}
	
	public function index() {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationSystem->exists($conditionId)) {
			$data = $this->findAllByEducationSystemId($conditionId, array(), array($this->alias.'.order' => 'ASC'));
			$paths = array();
			$paths[] = array(
				'name' => $this->EducationSystem->field('name', array('EducationSystem.id' => $conditionId)),
				'url' => array('action' => 'EducationSystem')
			);
			$paths[] = array(
				'name' => '(' . __('Education Levels') . ')'
			);
			$this->setVar(compact('data', 'paths', 'conditionId'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function view($id=0) {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		
		if($this->EducationSystem->exists($conditionId)) {
			$data = $this->findById($id);
			$this->setVar(compact('data'));
			$this->render = '../template/view';
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function add() {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		$this->render = '../template/add';
		if($this->EducationSystem->exists($conditionId)) {
			$iscedOptions = $this->EducationLevelIsced->find('list', array('order' => 'order'));
			$this->setVar(compact('iscedOptions'));
			if ($this->request->is(array('post', 'put'))) {
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
	
	public function edit($id) {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		
		if($this->EducationSystem->exists($conditionId)) {
			$data = $this->findById($id);
			
			if(!empty($data)) {
				if ($this->request->is(array('post', 'put'))) {
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
	
	public function getInstitutionLevelsByAcademicPeriod($institutionSiteId, $academicPeriodId){
		$list = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'EducationLevel.id AS education_level_id',
				'EducationLevel.name AS education_level_name'
			),
			'joins' => array(	
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array(
						'EducationLevel.id = EducationCycle.education_level_id',
						'EducationCycle.visible = 1'
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationCycle.id = EducationProgramme.education_cycle_id',
						'EducationProgramme.visible = 1'
					)
				),
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'EducationProgramme.id = InstitutionSiteProgramme.education_programme_id',
						'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
						//'InstitutionSiteProgramme.academic_period_id' => $academicPeriodId
					)
				)
			),
			'conditions' => array('EducationLevel.visible' => 1),
			'order' => array('EducationLevel.order')
		));

		$data = array();
		foreach($list AS $row){
			$levelId = $row['EducationLevel']['education_level_id'];
			$levelName = $row['EducationLevel']['education_level_name'];
			$data[$levelId] = $levelName;
		}
		
		return $data;
	}

	public function getOptions() {
		$this->contain('EducationSystem');
		$list = $this->find('list', array(
			'fields' => array(
				'EducationLevel.id', 'EducationLevel._name'
			),
			'order' => array(
				'EducationSystem.order', 'EducationLevel.order'
			)
		));	

		return $list;
	}
}
