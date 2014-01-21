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

class TeacherHealthConsultation extends TeachersAppModel {
	//public $useTable = 'teacher_health_histories';
	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
		//'Teacher',
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
	public $booleanOptions = array('No', 'Yes');
	
	public function healthConsultation($controller, $params) {
	//	pr('aas');
		$controller->Navigation->addCrumb('Health - Consultations');
		$controller->set('modelName', $this->name);
		$data = $this->find('all', array('conditions'=> array('teacher_id'=> $controller->teacherId)));
		
		$HealthConsultationType = ClassRegistry::init('HealthConsultationType');
		$healthConsultationsOptions = $HealthConsultationType->find('list', array('fields'=> array('id', 'name')));
		
		
		$controller->set('subheader', 'Health - Consultations');
		$controller->set('data', $data);
		$controller->set('healthConsultationsOptions', $healthConsultationsOptions);
		
	}

	public function healthConsultationView($controller, $params){
		$controller->Navigation->addCrumb('Health - View Consultation');
		$controller->set('subheader', 'Health - View Consultation');
		$controller->set('modelName', $this->name);
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
		
		if(empty($data)){
			$controller->redirect(array('action'=>'healthConsultation'));
		}
		
		$controller->Session->write('TeacherHealthConsultationId', $id);
		$HealthConsultationType = ClassRegistry::init('HealthConsultationType');
		$healthConsultationsOptions = $HealthConsultationType->find('list', array('fields'=> array('id', 'name')));
		
		$controller->set('data', $data);
		$controller->set('healthConsultationsOptions', $healthConsultationsOptions);
	}
	
	public function healthConsultationDelete($controller, $params) {
        if($controller->Session->check('TeacherId') && $controller->Session->check('TeacherHealthConsultationId')) {
            $id = $controller->Session->read('TeacherHealthConsultationId');
            $teacherId = $controller->Session->read('TeacherId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			
			$HealthConsultationType = ClassRegistry::init('HealthConsultationType');
			$healthConsultationsOptions = $HealthConsultationType->find('first', array('conditions'=> array('id' => $data[$this->name]['health_consultation_type_id'])));
		
            $name = !empty($healthConsultationsOptions['HealthConsultationType']['name'])?$healthConsultationsOptions['HealthConsultationType']['name']:'';
			
            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
			$controller->Session->delete('TeacherHealthConsultationId');
            $controller->redirect(array('action' => 'healthConsultation'));
        }
    }
	
	public function healthConsultationAdd($controller, $params) {
		$controller->Navigation->addCrumb('Health - Add Consultation');
		$controller->set('subheader', 'Health - Add Consultation');
		$this->setup_add_edit_form($controller, $params);
	}
	
	public function healthConsultationEdit($controller, $params) {
		$controller->Navigation->addCrumb('Health - Edit Consultation');
		$controller->set('subheader', 'Health - Edit Consultation');
		$this->setup_add_edit_form($controller, $params);
		
		
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params){
		$controller->set('modelName', $this->name);
		
		$HealthConsultationType = ClassRegistry::init('HealthConsultationType');
		$healthConsultationsOptions = $HealthConsultationType->find('list', array('fields'=> array('id', 'name')));
		$controller->set('healthConsultationsOptions', $healthConsultationsOptions);
		
		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			$this->recursive = -1;
			$data = $this->findById($id);
			if(!empty($data)){
				$controller->request->data = $data;
			}
		}
		else{
			$controller->request->data[$this->name]['teacher_id'] = $controller->teacherId;
			if($this->save($controller->request->data)){
				if(empty($controller->request->data[$this->name]['id'])){
					$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));	
				}
				else{
					$controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));	
				}
				return $controller->redirect(array('action' => 'healthConsultation'));
			}
		}
	}
}
