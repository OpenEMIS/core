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

class StaffSpecialNeed extends StaffAppModel {
	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
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
		'special_need_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Special Need Type.'
			)
		)
	);
	public $booleanOptions = array('No', 'Yes');

	public $headerDefault = 'Special Needs';
	
	public function specialNeed($controller, $params) {
	//	pr('aas');
		$controller->Navigation->addCrumb($this->headerDefault);
		$controller->set('modelName', $this->name);
		$data = $this->find('all', array('conditions'=> array('staff_id'=> $controller->staffId)));
		
		$specialNeedType = ClassRegistry::init('SpecialNeedType');
		$specialNeedTypeOptions = $specialNeedType->find('list', array('fields'=> array('id', 'name')));
		
		
		$controller->set('subheader', $this->headerDefault);
		$controller->set('data', $data);
		$controller->set('specialNeedTypeOptions', $specialNeedTypeOptions);
		
	}

	public function specialNeedView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$controller->set('modelName', $this->name);
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
		
		if(empty($data)){
			$controller->redirect(array('action'=>'specialNeed'));
		}
		
		$controller->Session->write('StaffSpecialNeedId', $id);
		$specialNeedType = ClassRegistry::init('SpecialNeedType');
		$specialNeedTypeOptions = $specialNeedType->find('list', array('fields'=> array('id', 'name')));
		
		$controller->set('data', $data);
		$controller->set('specialNeedTypeOptions', $specialNeedTypeOptions);
	}
	
	public function specialNeedDelete($controller, $params) {
        if($controller->Session->check('StaffId') && $controller->Session->check('StaffSpecialNeedId')) {
            $id = $controller->Session->read('StaffSpecialNeedId');
            $staffId = $controller->Session->read('StaffId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			
			$specialNeedType = ClassRegistry::init('SpecialNeedType');
			$specialNeedTypeOptions = $specialNeedType->find('list', array('fields'=> array('id', 'name')));

            $name = $specialNeedTypeOptions[$data['StaffSpecialNeed']['special_need_type_id']];
			
            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
			$controller->Session->delete('StaffSpecialNeedId');
            $controller->redirect(array('action' => 'specialNeed'));
        }
    }
	
	public function specialNeedAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add ' . $this->headerDefault);
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
	}
	
	public function specialNeedEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
		
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params){
		$controller->set('modelName', $this->name);
		
		$specialNeedType = ClassRegistry::init('SpecialNeedType');
		$specialNeedTypeOptions = $specialNeedType->find('list', array('fields'=> array('id', 'name')));
		
		$controller->set('specialNeedTypeOptions', $specialNeedTypeOptions);
		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			$this->recursive = -1;
			$data = $this->findById($id);
			if(!empty($data)){
				$controller->request->data = $data;
			}
		}
		else{
			if(isset($controller->data['submit']) && $controller->data['submit']=='Skip'){
               $nextLink = $controller->data[$this->name]['nextLink'];
                $controller->Navigation->skipWizardLink($controller->action, $nextLink);
            }
			$controller->request->data[$this->name]['staff_id'] = $controller->staffId;
			if($this->save($controller->request->data)){
				if(empty($controller->request->data[$this->name]['id'])){
					$id = $this->getLastInsertId();
                	$controller->Navigation->updateWizard($controller->action,$id);
					$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));	
				}
				else{
					$controller->Navigation->updateWizard($controller->action,$controller->request->data[$this->name]['id']);
					$controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));	
				}
				return $controller->redirect(array('action' => 'specialNeed'));
			}
		}
	}
}
