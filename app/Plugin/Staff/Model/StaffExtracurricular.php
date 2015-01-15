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

class StaffExtracurricular extends StaffAppModel {
	public $actsAs = array('ControllerAction','DatePicker' => 'start_date');
	public $belongsTo = array(
		'Staff.Staff',
		'SchoolYear',
		'ExtracurricularType',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);
	
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Title.'
			)
		),
		'hours' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Hours.'
			)
		),
		'start_date' => array(
			'ruleNotLater' => array(
				'rule' => array('compareDate', 'end_date'),
				'message' => 'Start Date cannot be later than End Date'
			),
		)
	);
	
	public function compareDate($field = array(), $compareField = null) {
		$startDate = new DateTime(current($field));
		$endDate = new DateTime($this->data[$this->name][$compareField]);
		return $endDate > $startDate;
	}
	
	public function autocomplete($search) {
		$search = sprintf('%%%s%%', $search);
		$data = $this->find('list', array(
			'recursive' => -1,
			'fields' => array('StaffExtracurricular.id', 'StaffExtracurricular.name'),
			'conditions' => array(
				'OR' => array(
					'StaffExtracurricular.name LIKE' => $search,
				)
			),
			'order' => array('StaffExtracurricular.name'),
			'group' => array('StaffExtracurricular.name')
		));
		return $data;
	}
	
	public function getDisplayFields($controller) {
		
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'name', 'model' => 'SchoolYear'),
				array('field' => 'name', 'model' => 'ExtracurricularType', 'labelKey' => 'general.type'),
				array('field' => 'name', 'labelKey' => 'general.title'),
				array('field' => 'start_date', 'type' => 'datepicker'),
				array('field' => 'end_date', 'type' => 'datepicker'),
				array('field' => 'hours'),
				array('field' => 'points'),
				array('field' => 'location'),
				array('field' => 'comment'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function extracurricular($controller, $params) {
		$controller->Navigation->addCrumb('Extracurricular');
		$header = __('Extracurricular');
		$this->unbindModel(array('belongsTo' => array('Staff', 'ModifiedUser', 'CreatedUser')));
		$data = $this->find('all', array('conditions' => array('staff_id' => $controller->Session->read('Staff.id')), 'order' => 'SchoolYear.start_date'));
	  
		$controller->set(compact('data', 'header'));
	}

	public function extracurricularView($controller, $params) {
		$id = isset($params['pass'][0])?$params['pass'][0]:0;
		$data = $this->findById($id);

		if (empty($data)) {
			$controller->Message->alert('general.noData');
			return $controller->redirect(array('action' => 'extracurricular'));
		}
		
		$controller->Navigation->addCrumb('Extracurricular Details');
		$header = __('Details');

		$controller->Session->write('StaffExtracurricular.id', $id);
		$fields = $this->getDisplayFields($controller);

		$controller->set(compact('header', 'data', 'fields'));
	}

	public function extracurricularAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add Extracurricular');
		$header = __('Add Extracurricular');
		
		if ($controller->request->is('post') || $controller->request->is('put')) {
			$data = $controller->request->data;
			
			$data[$this->alias]['start_date'] = date('Y-m-d', strtotime($data[$this->alias]['start_date']));
			$data[$this->alias]['end_date'] = date('Y-m-d', strtotime($data[$this->alias]['end_date']));
			$data[$this->alias]['staff_id'] = $controller->Session->read('Staff.id');
			if ($this->save($data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'extracurricular'));
			}
		}
		
		$yearOptions = $this->SchoolYear->getYearList();
		$yearId = isset($params['pass'][0])?$params['pass'][0] : key($yearOptions);
		$typeOptions = $this->ExtracurricularType->getList(array('value' => 0));

		$controller->set(compact('header','yearOptions','yearId', 'typeOptions'));
	}

	public function extracurricularEdit($controller, $params) {
		$id = isset($params['pass'][0])? $params['pass'][0] : 0;
		$controller->Navigation->addCrumb('Edit Extracurricular');
		$header = __('Edit Extracurricular');
	   
		if ($controller->request->is('post') || $controller->request->is('put')) {
			$data = $controller->data;
			$data[$this->alias]['staff_id'] = $controller->Session->read('Staff.id');
			$data[$this->alias]['start_date'] = date('Y-m-d', strtotime($data[$this->alias]['start_date']));
			$data[$this->alias]['end_date'] = date('Y-m-d', strtotime($data[$this->alias]['end_date']));
			if ($this->save($data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'extracurricularView', $data['StaffExtracurricular']['id']));
			}
		}
		else{
			$data = $this->findById($id);
			
			if (empty($data)) {
				$controller->Message->alert('general.noData');
				return $controller->redirect(array('action' => 'extracurricular'));
			}
			$controller->request->data = $data;
		}
		
		$yearOptions = $this->SchoolYear->getYearList();
		$yearId = isset($params['pass'][0])?$params['pass'][0] : key($yearOptions);

		$typeOptions = $this->ExtracurricularType->getList(array('value' => $controller->request->data['StaffExtracurricular']['extracurricular_type_id']));
		
		$controller->set(compact('header','yearOptions','yearId', 'typeOptions'));
	}

	public function extracurricularDelete($controller, $params) {
		return $this->remove($controller, 'extracurricular');
	}

	public function extracurricularSearchAutoComplete($controller, $params) {
		$this->render = false;
		if ($controller->request->is('get')) {
			if ($controller->request->is('ajax')) {
				
				$search = $params->query['term'];
				$result = $this->autocomplete($search);
				return json_encode($result);
			}
		}
	}
}
?>
