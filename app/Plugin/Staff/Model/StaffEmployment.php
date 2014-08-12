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

class StaffEmployment extends StaffAppModel {
	public $actsAs = array('ControllerAction', 'DatePicker' => array('employment_date'));
	public $belongsTo = array(
		'Staff.Staff',
		'EmploymentType',
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
		'employment_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Type'
			)
		),
		'employment_date' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Date'
			)
		)
	);

	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'name', 'model' => 'EmploymentType', 'labelKey' => 'general.type'),
				array('field' => 'employment_date'),
				array('field' => 'comment'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}

	public function employments($controller, $params) {
        $controller->Navigation->addCrumb(__('Employment'));
        $header = __('Employment');
		$this->unbindModel(array('belongsTo' => array('Staff', 'ModifiedUser','CreatedUser')));
        $data = $this->findAllByStaffId($controller->Session->read('Staff.id'));
        $controller->set(compact('header', 'data'));
	}

	public function employmentsAdd($controller, $params) {
		$controller->Navigation->addCrumb(__('Add Employment'));
        $controller->set('header' , __('Add Employment'));
		$this->setup_add_edit_form($controller, $params, 'add');
	}

	public function employmentsEdit($controller, $params) {
		$controller->Navigation->addCrumb(__('Edit Employment'));
        $controller->set('header' , __('Edit Employment'));
		$this->setup_add_edit_form($controller, $params, 'edit');
		$this->render = 'add';
	}
	
	public function employmentsView($controller, $params) {
		$controller->Navigation->addCrumb('Employment Details');
        $header = __('Employment Details');

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $data = $this->findById($id);

        if (empty($data)) {
            $controller->Message->alert('general.noData');
            $controller->redirect(array('action' => 'employments'));
        }

        $controller->Session->write('StaffEmployment.id', $id);
        $fields = $this->getDisplayFields($controller);
        $controller->set(compact('header', 'data', 'fields', 'id'));
	}
	

	function setup_add_edit_form($controller, $params, $type){
		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];

        if ($controller->request->is('post') || $controller->request->is('put')) {
            $controller->request->data['StaffEmployment']['staff_id'] = $controller->Session->read('Staff.id');

			$data = $controller->request->data['StaffEmployment'];

			if ($this->save($data)) {
				$controller->Message->alert('general.' . $type . '.success');
				return $controller->redirect(array('action' => 'employments'));
			}
        } else {
            $this->recursive = -1;
            $data = $this->findById($id);
            if (!empty($data)) {
                $controller->request->data = $data;
            }
        }

        $employmentTypeOptions = $this->EmploymentType->getOptions();
		$controller->set(compact('employmentTypeOptions'));
	}
	
	public function employmentsDelete($controller, $params) {
		return $this->remove($controller, 'employments');
	}
}
