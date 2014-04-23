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
	public $actsAs = array('ControllerAction', 'Datepicker' => array('special_need_date'));
	
	public $belongsTo = array(
		'SpecialNeedType',
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
	public $headerDefault = 'Special Needs';
	public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
    }
	
	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'special_need_date','labelKey' => 'general.date'),
                array('field' => 'name', 'model' => 'SpecialNeedType','labelKey' => 'general.type' ),
				array('field' => 'comment'),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
	public function specialNeed($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);
		$header = $this->headerDefault;
		$this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
		$data = $this->findAllByStaffId($controller->staffId);
		$controller->set(compact('header', 'data'));
	}

	public function specialNeedView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$header = __($this->headerDefault . ' Details');
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->findById($id);
		
		if(empty($data)){
			$controller->Message->alert('general.noData');
			$controller->redirect(array('action'=>'specialNeed'));
		}
		
		$controller->Session->write('StaffSpecialNeedId', $id);
		$fields = $this->getDisplayFields($controller);
        $controller->set(compact('header', 'data', 'fields', 'id'));
	}
	
	/*public function specialNeedDelete($controller, $params) {
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
			$addMore = false;
			if(isset($controller->data['submit']) && $controller->data['submit']==__('Skip')){
                $controller->Navigation->skipWizardLink($controller->action);
            }else if(isset($controller->data['submit']) && $controller->data['submit']==__('Previous')){
                $controller->Navigation->previousWizardLink($controller->action);
            }elseif(isset($controller->data['submit']) && $controller->data['submit']==__('Add More')){
                $addMore = true;
            }else{
                $controller->Navigation->validateModel($controller->action,$this->name);
            }
			$controller->request->data[$this->name]['staff_id'] = $controller->staffId;
			if($this->save($controller->request->data)){
				if(empty($controller->request->data[$this->name]['id'])){
					$id = $this->getLastInsertId();
                	if($addMore){
						$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));	
					}
					$controller->Navigation->updateWizard($controller->action,$id,$addMore);
					$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
				}
				else{
					$controller->Navigation->updateWizard($controller->action,$controller->request->data[$this->name]['id']);
					$controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));	
				}
				return $controller->redirect(array('action' => 'specialNeed'));
			}
		}
	}*/
	
	public function specialNeedDelete($controller, $params) {
        if($controller->Session->check('StaffId') && $controller->Session->check('StaffSpecialNeedId')) {
            $id = $controller->Session->read('StaffSpecialNeedId');
            if($this->delete($id)) {
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }
			$controller->Session->delete('StaffSpecialNeedId');
            $controller->redirect(array('action' => 'specialNeed'));
        }
    }
	
	public function specialNeedAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add ' . $this->headerDefault);
		$controller->set('header', __('Add ' . $this->headerDefault));
		$this->setup_add_edit_form($controller, $params);
	}
	
	public function specialNeedEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault);
		$controller->set('header', __('Edit ' . $this->headerDefault));
		$this->setup_add_edit_form($controller, $params);
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params){
		$specialNeedTypeOptions = $this->SpecialNeedType->find('list', array('fields'=> array('id', 'name')));
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
			$addMore = false;
			if(isset($controller->data['submit']) && $controller->data['submit']==__('Skip')){
                $controller->Navigation->skipWizardLink($controller->action);
            }else if(isset($controller->data['submit']) && $controller->data['submit']==__('Previous')){
                $controller->Navigation->previousWizardLink($controller->action);
            }elseif(isset($controller->data['submit']) && $controller->data['submit']==__('Add More')){
                $addMore = true;
            }else{
                $controller->Navigation->validateModel($controller->action,$this->name);
            }
			$controller->request->data[$this->name]['staff_id'] = $controller->staffId;
			if($this->save($controller->request->data)){
				$controller->Message->alert('general.add.success');
				if(empty($controller->request->data[$this->name]['id'])){
					$id = $this->getLastInsertId();
					/*if($addMore){
						$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));	
					}*/
                	$controller->Navigation->updateWizard($controller->action,$id,$addMore);
				}
				else{
               		$controller->Navigation->updateWizard($controller->action,$controller->request->data[$this->name]['id']);
				}
				return $controller->redirect(array('action' => 'specialNeed'));
			}
		}
	}
}
