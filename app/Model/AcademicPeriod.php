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

class AcademicPeriod extends AppModel {
	public $actsAs = array(
		'Tree',
		'Reorder',
		'CustomReport',
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'AcademicPeriodLevel',
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
	
	public $hasMany = array(
	);
	
	public $validate = array(
		'code' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter the code for the Academic Period.'
			),
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'There are duplicate Academic Period code.'
			)
		),
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				 'message' => 'Please enter the name for the Academic Period.'
			)
		)
		// ,
		// 'start_date' => array(
		// 	'ruleCheckStartDate' => array(
		// 		'rule' => 'checkStartDate',
		// 		'required' => true,
		// 		'message' => 'Please enter a valid start date.'
		// 	)
		// ),
		// 'end_date' => array(
		// 	'ruleCheckEndDate' => array(
		// 		'rule' => 'checkEndDate',
		// 		'required' => true,
		// 		'message' => 'Please enter a valid end date.'
		// 	)
		// )
	);


// 	public function checkStartDate() {
// 		$startDateExists = array_key_exists('start_date', $this->data[$this->alias])&&$this->data[$this->alias]['start_date']&&$this->data[$this->alias]['start_date']!='';
// 		if
// 		pr($startDateExists);
// // need to check the parent

		
// 		return true;
// 	}

// 	public function checkEndDate() {
// 		$endDateExists = array_key_exists('end_date', $this->data[$this->alias])&&$this->data[$this->alias]['end_date']&&$this->data[$this->alias]['end_date']!='';
// 	}
	
	public function beforeAction() {
        parent::beforeAction();
		
		$this->fields['parent_id']['type'] = 'hidden';
		$this->fields['lft']['visible'] = false;
		$this->fields['rght']['visible'] = false;
		$this->fields['order']['visible'] = false;
		$this->fields['available']['type'] = 'select';
		$this->fields['available']['options'] = $this->controller->Option->get('yesno');
		
		if ($this->action == 'view') {
			$this->fields['academic_period_level_id']['dataModel'] = 'AcademicPeriodLevel';
			$this->fields['academic_period_level_id']['dataField'] = 'name';
		}
		
		$this->Navigation->addCrumb('Academic Periods');
		$this->setVar('contentHeader', __('Academic Periods'));
    }

    public function getOptionFields() {
		parent::getOptionFields();

		$this->fields['start_year']['type'] = 'hidden';
		$this->fields['end_year']['type'] = 'hidden';

		$this->fields['current']['labelKey'] = 'FieldOption';
		$this->fields['current']['type'] = 'select';
		$this->fields['current']['default'] = 1;
		$this->fields['current']['options'] = array(1 => __('Yes'), 0 => __('No'));

		$this->fields['available']['labelKey'] = 'FieldOption';
		$this->fields['available']['type'] = 'select';
		$this->fields['available']['default'] = 1;
		$this->fields['available']['options'] = array(1 => __('Yes'), 0 => __('No'));

		return $this->fields;
	}
	
	public function index() {
		$this->recover();

		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$paths = $parentId != 0 ? $this->getPath($parentId) : $this->findAllByParentId(0);
		$academicPeriod = end($paths);
		$data = array();
		$maxLevel = $this->AcademicPeriodLevel->field('level', null, 'level DESC');
		
		if($academicPeriod !== false) {
			$data = $this->find('all', array(
				'recursive'=>0,
				'conditions' => array('parent_id' => $academicPeriod[$this->alias]['id']),
				'order' => array('order')
			));
		}
		$this->setVar(compact('paths', 'data', 'parentId', 'maxLevel'));
	}
	
	public function reorder() {
		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$paths = $parentId != 0 ? $this->getPath($parentId) : $this->findAllByParentId(0);
		$academicPeriod = end($paths);
		$data = array();
		
		if($academicPeriod !== false) {
			$data = $this->find('all', array(
				'conditions' => array('parent_id' => $academicPeriod[$this->alias]['id']),
				'order' => array('order')
			));
		}
		$this->setVar(compact('paths', 'data', 'parentId'));
	}
	
	public function move() {
		if ($this->request->is(array('post', 'put'))) {
			$params = $this->controller->params;
			$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
			$data = $this->request->data;
			if ($parentId == 0) {
				$parentId = 1;
			}
			$conditions = array('parent_id' => $parentId);
			$this->moveOrder($data, $conditions);
			$redirect = array('action' => get_class($this), 'reorder', 'parent' => $parentId);
			return $this->redirect($redirect);
		}
	}
	
	public function add() {
		$this->fields['visible']['visible'] = false;
		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$paths = $parentId != 0 ? $this->getPath($parentId) : $this->findAllByParentId(0);
		$academicPeriod = end($paths);
		
		$pathList = array();
		foreach($paths as $item) {
			$pathList[] = $item[$this->alias]['name'];
		}
		$pathToString = implode(' / ', $pathList);
		$parentId = $academicPeriod[$this->alias]['id'];
		$level = $this->AcademicPeriodLevel->field('level', array('id' => $academicPeriod[$this->alias]['academic_period_level_id']));
		$academicPeriodLevelOptions = $this->AcademicPeriodLevel->find('list', array('conditions' => array('level >' => $level)));
		
		if($this->request->is(array('post', 'put'))) {
			$this->request->data[$this->alias]['parent_id'] = $parentId;
			$this->request->data[$this->alias]['order'] = $this->field('order', array('parent_id' => $parentId), 'order DESC') + 1;
			if ($this->save($this->request->data)) {
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => get_class($this), 'view', 'parent' => $parentId, $this->id));
			}
		}
		$this->setVar(compact('data', 'fields', 'parentId', 'pathToString', 'academicPeriodLevelOptions'));
	}
	
	public function view($id=0) {
		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$data = $this->findById($id);
		
		$this->setVar(compact('data', 'parentId'));
	}
	
	public function edit($id=0) {
		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$data = $this->findById($id);
		
		$yesnoOptions = $this->controller->Option->get('yesno');

		if(!empty($data)) {
			$this->setVar(compact('yesnoOptions', 'parentId'));
			if($this->request->is(array('post', 'put'))) {
				if ($this->save($this->request->data)) {
					$this->Message->alert('general.edit.success');
					return $this->redirect(array('action' => get_class($this), 'view', 'parent' => $parentId, $id));
				}
			} else {
				$this->request->data = $data;
			}
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => get_class($this), 'parent' => $parentId));
		}
	}

	public function beforeSave($options=array()) {
		if (array_key_exists($this->alias, $this->data)) {
			if (array_key_exists('start_date', $this->data[$this->alias])) {
				$this->data[$this->alias]['start_date'] = date("Y-m-d", strtotime($this->data[$this->alias]['start_date']));
				$this->data[$this->alias]['start_year'] = date("Y",strtotime($this->data[$this->alias]['start_date']));
			}
			if (array_key_exists('end_date', $this->data[$this->alias])) {
				$this->data[$this->alias]['end_date'] = date("Y-m-d", strtotime($this->data[$this->alias]['end_date']));
				$this->data[$this->alias]['end_year'] = date("Y",strtotime($this->data[$this->alias]['end_date']));
			}

			$datediff = strtotime($this->data[$this->alias]['end_date']) - strtotime($this->data[$this->alias]['start_date']);
			$this->data[$this->alias]['school_days'] = floor($datediff/(60*60*24))+1;
		}
	}

	public function getName($id) {
		$data = $this->findById($id);	
		return $data['AcademicPeriod']['name'];
	}

	public function getAvailableAcademicPeriods($list = true, $order='ASC') {
		if($list) {
			$result = $this->find('list', array(
				'fields' => array('AcademicPeriod.id', 'AcademicPeriod.name'),
				'conditions' => array(
					'AcademicPeriod.available' => 1,
					'AcademicPeriod.parent_id >' => 0
				),
				'order' => array('AcademicPeriod.name ' . $order)
			));
		} else {
			$result = $this->find('all', array(
				'conditions' => array(
					'AcademicPeriod.available' => 1,
					'AcademicPeriod.parent_id >' => 0
				),
				'order' => array('AcademicPeriod.name ' . $order)
			));
		}
		return $result;
	}

	//getYearList
	public function getAcademicPeriodList($type='name', $order='DESC') {
		$value = 'AcademicPeriod.' . $type;
		$result = $this->find('list', array(
			'fields' => array('AcademicPeriod.id', $value),
			'conditions' => array('AcademicPeriod.parent_id > ' => 0),
			'order' => array($value . ' ' . $order)
		));
		return $result;
	}

	public function getAcademicPeriodObjectById($academicPeriodId) {
		$data = $this->findById($academicPeriodId);	
		return $data['AcademicPeriod'];
	}

	public function getAcademicPeriodListForVerification($institutionSiteId, $validate=true) {
		$CensusVerification = ClassRegistry::init('CensusVerification');
		$academicPeriodIds = $CensusVerification->find('list', array(
			'fields' => array('CensusVerification.academic_period_id'),
			'joins' => array(
				array(
					'table' => 'census_verifications',
					'alias' => 'CensusVerification2',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusVerification2.academic_period_id = CensusVerification.academic_period_id',
						'CensusVerification2.institution_site_id = CensusVerification.institution_site_id',
						'CensusVerification2.created > CensusVerification.created'
					)
				)
			),
			'conditions' => array(
				'CensusVerification.status' => 1,
				'CensusVerification.institution_site_id' => $institutionSiteId,
				'CensusVerification2.id IS NULL'
			)
		));
		
		$conditions = array();
		if($validate) {
			$conditions['id NOT'] = array_values($academicPeriodIds);
		} else {
			$conditions['id'] = array_values($academicPeriodIds);
		}
		$data = $this->find('list', array(
			'fields' => array('AcademicPeriod.id', 'AcademicPeriod.name'),
			'conditions' => $conditions,
			'order' => 'AcademicPeriod.name'
		));
		return $data;
	}	

	public function getAcademicPeriodIdByDate($date) {
		if(empty($date)){
			return '';
		}
		
		$result = $this->find('first', array(
			'fields' => array('AcademicPeriod.id'),
			'conditions' => array(
				'AcademicPeriod.available' => 1,
				'AcademicPeriod.start_date <=' => $date,
				'AcademicPeriod.end_date >=' => $date
			)
		));
		
		if(!empty($result['AcademicPeriod']['id'])){
			return $result['AcademicPeriod']['id'];
		}else{
			return '';
		}
	}

	public function getAcademicPeriodById($periodId) {
		$data = $this->findById($periodId);	
		return $data['AcademicPeriod']['name'];
	}

	public function getCurrent() {
		$result = $this->find('first', array(
			'fields' => array('AcademicPeriod.id'),
			'conditions' => array(
				'AcademicPeriod.available = 1',
				'AcademicPeriod.current = 1'
			)
		));
		
		if(!empty($result['AcademicPeriod']['id'])){
			return $result['AcademicPeriod']['id'];
		}else{
			return '';
		}
	}

	public function getAcademicPeriodListValues($type='name', $order='DESC') {
		$value = 'AcademicPeriod.' . $type;
		$result = $this->find('list', array(
			'fields' => array($value, $value),
			'order' => array($value . ' ' . $order)
		));
		return $result;
	}

	public function getAcademicPeriodId($academicPeriod) {
		$data = $this->findByName($academicPeriod);	
		return $data['AcademicPeriod']['id'];
	}
}