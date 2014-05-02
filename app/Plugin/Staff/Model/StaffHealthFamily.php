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

class StaffHealthFamily extends StaffAppModel {
	//public $useTable = 'staff_health_histories';
	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
		//'Staff',
		'HealthCondition' => array('foreignKey' => 'health_condition_id'),
        'HealthRelationships' => array('foreignKey' => 'health_relationship_id'),
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
		'health_relationship_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Relationship.'
			)
		),
		'health_condition_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Condition.'
			)
		)
	);
	
	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'name', 'model' => 'HealthRelationships'),
                array('field' => 'name', 'model' => 'HealthCondition'),
                array('field' => 'current','type' => 'select', 'options' => $controller->Option->get('yesno')),
                array('field' => 'comment'),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }

    public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
    }

    public function healthFamily($controller, $params) {
        $controller->Navigation->addCrumb('Health - Family');
        $header = __('Health - Family');
        $this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
        $data = $this->findAllByStaffId($controller->staffId); 

        $controller->set(compact('header', 'data'));
    }

	public function healthFamilyView($controller, $params) {
        $controller->Navigation->addCrumb('Health - View Family');

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $header = __('Health - View Family');
        $data = $this->findById($id); //('first',array('conditions' => array($this->name.'.id' => $id)));

        if (empty($data)) {
            $controller->Message->alert('general.noData');
            return $controller->redirect(array('action' => 'healthFamily'));
        }

        $controller->Session->write('StaffHealthFamilyId', $id);
        $fields = $this->getDisplayFields($controller);
        $controller->set(compact('header', 'data', 'fields', 'id'));
    }
	
	public function healthFamilyDelete($controller, $params) {
        if($controller->Session->check('StaffId') && $controller->Session->check('StaffHealthFamilyId')) {
            $id = $controller->Session->read('StaffHealthFamilyId');
            if ($this->delete($id)) {
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }
			$controller->Session->delete('StaffHealthFamilyId');
            $controller->redirect(array('action' => 'healthFamily'));
        }
    }
	
	public function healthFamilyAdd($controller, $params) {
		$controller->Navigation->addCrumb('Health - Add Family');
		$controller->set('header', 'Health - Add Family');
		$this->setup_add_edit_form($controller, $params);
	}
	
	public function healthFamilyEdit($controller, $params) {
		$controller->Navigation->addCrumb('Health - Edit Family');
		$controller->set('header', 'Health - Edit Family');
		$this->setup_add_edit_form($controller, $params);
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params){
		
		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
            
        if ($controller->request->is('post') || $controller->request->is('put')) {
            $controller->request->data[$this->name]['staff_id'] = $controller->staffId;
            if ($this->save($controller->request->data)) {
                $controller->Message->alert('general.add.success');
                return $controller->redirect(array('action' => 'healthFamily'));
            }
        }
        else{
            $this->recursive = -1;
            $data = $this->findById($id);
            if (!empty($data)) {
                $controller->request->data = $data;
            }
        }
        
        $healthConditionsOptions = $this->HealthCondition->find('list', array('fields' => array('id', 'name')));
        $healthRelationshipsOptions = $this->HealthRelationships->find('list', array('fields' => array('id', 'name')));
        $yesnoOptions = $controller->Option->get('yesno');
        
        $controller->set(compact('healthConditionsOptions', 'healthRelationshipsOptions','yesnoOptions'));
	}
}
