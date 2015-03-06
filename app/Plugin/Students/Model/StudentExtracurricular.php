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

class StudentExtracurricular extends StudentsAppModel {
	public $actsAs = array(
		'Excel' => array('header' => array('Student' => array('identification_no', 'first_name', 'last_name'))),
		'ControllerAction', 'DatePicker' => array('start_date', 'end_date')
	);
	public $belongsTo = array(
		'Student',
		'AcademicPeriod',
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

	public function autocomplete($search) {
		$search = sprintf('%%%s%%', $search);
		$data = $this->find('list', array(
			'recursive' => -1,
			'fields' => array('StudentExtracurricular.id', 'StudentExtracurricular.name'),
			'conditions' => array(
				'OR' => array(
					'StudentExtracurricular.name LIKE' => $search,
				)
			),
			'order' => array('StudentExtracurricular.name'),
			'group' => array('StudentExtracurricular.name')
		));
		return $data;
	}

	public function beforeAction($controller, $params) {
		parent::beforeAction($controller, $params);
		if (!$controller->Session->check('Student.id')) {
			return $controller->redirect(array('action' => 'index'));
		}
	}
	
	public function getDisplayFields($controller) {
		
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'name', 'model' => 'AcademicPeriod'),
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
		$this->unbindModel(array('belongsTo' => array('Student', 'ModifiedUser', 'CreatedUser')));
		$data = $this->find('all', array('conditions' => array('student_id' => $controller->Session->read('Student.id')), 'order' => 'AcademicPeriod.start_date'));
	  
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

		$controller->Session->write('StudentExtracurricular.id', $id);
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
			$data[$this->alias]['student_id'] = $controller->Session->read('Student.id');
			if ($this->save($data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'extracurricular'));
			}
		}

		$academicPeriodOptions = $this->AcademicPeriod->getAcademicPeriodList();
		$academicPeriodId = isset($params['pass'][0])?$params['pass'][0] : key($academicPeriodOptions);
		$typeOptions = $this->ExtracurricularType->getList(array('value' => 0));

		$controller->set(compact('header','academicPeriodOptions','academicPeriodId', 'typeOptions'));
	}

	public function extracurricularEdit($controller, $params) {
		$id = isset($params['pass'][0])? $params['pass'][0] : 0;
		$controller->Navigation->addCrumb('Edit Extracurricular');
		$header = __('Edit Extracurricular');
	   
		if ($controller->request->is('post') || $controller->request->is('put')) {
			$data = $controller->data;
			$data[$this->alias]['student_id'] = $controller->Session->read('Student.id');
			$data[$this->alias]['start_date'] = date('Y-m-d', strtotime($data[$this->alias]['start_date']));
			$data[$this->alias]['end_date'] = date('Y-m-d', strtotime($data[$this->alias]['end_date']));
			if ($this->save($data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'extracurricularView', $data['StudentExtracurricular']['id']));
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

		$academicPeriodOptions = $this->AcademicPeriod->getAcademicPeriodList();
		$academicPeriodId = isset($params['pass'][0])?$params['pass'][0] : key($academicPeriodOptions);
		$typeOptions = $this->ExtracurricularType->getList(array('value' => $data['StudentExtracurricular']['extracurricular_type_id']));

		$controller->set(compact('header','academicPeriodOptions','academicPeriodId', 'typeOptions'));
	}

	public function extracurricularDelete($controller, $params) {
		if ($controller->Session->check('StudentExtracurricular.id')) {
			$id = $controller->Session->read('StudentExtracurricular.id');
		   
			if($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			
			$controller->Session->delete('StudentExtracurricular.id');
			$controller->redirect(array('action' => 'extracurricular'));
		}
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
