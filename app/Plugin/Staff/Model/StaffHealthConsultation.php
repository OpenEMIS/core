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

class StaffHealthConsultation extends StaffAppModel {
	public $actsAs = array(
        'Excel' => array('header' => array('Staff' => array('SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.last_name'))),
        'ControllerAction', 
        'DatePicker' => array('date')
    );
	
	public $belongsTo = array(
		'Staff.Staff',
		'HealthConsultationType',
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
		'health_consultation_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Consultation.'
			)
		)
	);
	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                //   array('field' => 'id', 'type' => 'hidden'),
                array('field' => 'date', 'type' => 'datepicker'),
                array('field' => 'name', 'model' => 'HealthConsultationType', 'labelKey' => 'general.type'),
                array('field' => 'description'),
                array('field' => 'treatment'),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }

    public function healthConsultation($controller, $params) {
        $controller->Navigation->addCrumb('Health - Consultations');
        $header = __('Health - Consultations');
        $this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
        $data = $this->findAllByStaffId($controller->Session->read('Staff.id'));
        $controller->set(compact('header', 'data'));
    }

    public function healthConsultationView($controller, $params) {
        $controller->Navigation->addCrumb('Health - View Consultation');
        $header = __('Health - View Consultation');

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $data = $this->findById($id);

        if (empty($data)) {
            $controller->Message->alert('general.noData');
            return $controller->redirect(array('action' => 'healthConsultation'));
        }

        $controller->Session->write('StaffHealthConsultation.id', $id);
        $fields = $this->getDisplayFields($controller);
        $controller->set(compact('header', 'data', 'fields', 'id'));
    }

    public function healthConsultationDelete($controller, $params) {
        return $this->remove($controller, 'healthConsultation');
    }

    public function healthConsultationAdd($controller, $params) {
        $controller->Navigation->addCrumb('Health - Add Consultation');
        $controller->set('header', __('Health - Add Consultation'));
        $this->setup_add_edit_form($controller, $params, 'add');
    }

    public function healthConsultationEdit($controller, $params) {
        $controller->Navigation->addCrumb('Health - Edit Consultation');
        $controller->set('header', __('Health - Edit Consultation'));
        $this->setup_add_edit_form($controller, $params, 'edit');
        $this->render = 'add';
    }

    function setup_add_edit_form($controller, $params, $type) {
        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        
        if ($controller->request->is('post') || $controller->request->is('put')) {
            $controller->request->data[$this->alias]['staff_id'] = $controller->Session->read('Staff.id');
            
            if ($this->save($controller->request->data)) {
                $controller->Message->alert('general.add.success');
                return $controller->redirect(array('action' => 'healthConsultation'));
            }
        }
        else{
            $this->recursive = -1;
            $data = $this->findById($id);
            if (!empty($data)) {
                $controller->request->data = $data;
            }
        }
        
        if (!empty($controller->request->data)) {
			$healthConsultationsOptions = $this->HealthConsultationType->getList(array('value' => $controller->request->data['StaffHealthConsultation']['health_consultation_type_id']));
		} else {
			$healthConsultationsOptions = $this->HealthConsultationType->getList(array('value' => 0));
		}
        $controller->set(compact('healthConsultationsOptions'));
    }
}
