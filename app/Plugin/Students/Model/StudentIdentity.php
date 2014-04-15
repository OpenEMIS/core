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

class StudentIdentity extends StudentsAppModel {
        public $actsAs = array('ControllerAction');
	public $belongsTo = array(
		'Student',
		'IdentityType',
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
		'identity_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Type'
			)
		),
		'number' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Number'
			)
		),
		'issue_location' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Issue Location'
			)
		),
		'expiry_date' => array(
            'comparison' => array(
            	'rule'=>array('field_comparison', '>', 'issue_date'), 
            	'allowEmpty'=>true,
            	'message' => 'Expiry Date must be greater than Issue Date'
            )
        )
	);



  	function field_comparison($check1, $operator, $field2) {
        foreach($check1 as $key=>$value1) {
            $value2 = $this->data[$this->alias][$field2];
            if (!Validation::comparison($value1, $operator, $value2))
                return false;
        }
        return true;
    }
    
    public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
    }
    
    public function identities($controller, $action) {
        $controller->Navigation->addCrumb(__('Identities'));
        $header = __('Identities');
        $data = $this->find('all', array('conditions' => array('StudentIdentity.student_id' => $controller->studentId)));
        
        $controller->set(compact('header', 'data'));
    }

    public function identitiesAdd($controller, $action) {
        $controller->Navigation->addCrumb(__('Add Identities'));
        if ($controller->request->is('post')) {
            $data = $this->data['StudentIdentity'];
            $addMore = false;
            if(isset($this->data['submit']) && $this->data['submit']==__('Skip')){
                $controller->Navigation->skipWizardLink($this->action);
            }else if(isset($this->data['submit']) && $this->data['submit']==__('Previous')){
                $controller->Navigation->previousWizardLink($this->action);
            }elseif(isset($this->data['submit']) && $this->data['submit']==__('Add More')){
                $addMore = true;
            }else{
                $controller->Navigation->validateModel($this->action,'StudentIdentity');
            }

            $this->create();
            $data['student_id'] = $controller->studentId;

            if ($this->save($data)) {
                $id = $this->getLastInsertId();
                if($addMore){
                  //  $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                }
                $controller->Navigation->updateWizard($this->action,$id,$addMore);
             //   $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $controller->redirect(array('action' => 'identities'));
            }
        }

        pr($this->identityType);
        //$identityTypeOptions = $this->IdentityType->getOptions();
       // $controller->set('identityTypeOptions', $identityTypeOptions);
       // $controller->UserSession->readStatusSession($this->request->action);
    }

    public function identitiesView($controller, $action) {
        $identityId = $this->params['pass'][0];
        $identityObj = $this->StudentIdentity->find('all', array('conditions' => array('StudentIdentity.id' => $identityId)));

        if (!empty($identityObj)) {
            $this->Navigation->addCrumb(__('Identity Details'));

            $this->Session->write('StudentIdentityId', $identityId);
            $this->set('identityObj', $identityObj);
        }
    }

    public function identitiesEdit($controller, $action) {
        $identityId = $this->params['pass'][0];
        if ($this->request->is('get')) {
            $identityObj = $this->StudentIdentity->find('first', array('conditions' => array('StudentIdentity.id' => $identityId)));

            if (!empty($identityObj)) {
                $this->Navigation->addCrumb(__('Edit Identity Details'));
                $this->request->data = $identityObj;
            }
        } else {
            $identityData = $this->data['StudentIdentity'];

            if(isset($this->data['submit']) && $this->data['submit']==__('Skip')){
                $this->Navigation->skipWizardLink($this->action);
            }else if(isset($this->data['submit']) && $this->data['submit']==__('Previous')){
                $this->Navigation->previousWizardLink($this->action);
            }
            $identityData['student_id'] = $this->studentId;

            if ($this->StudentIdentity->save($identityData)) {
                $this->Navigation->updateWizard($this->action,$identityId);
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'identitiesView', $identityData['id']));
            }
        }

        $identityTypeOptions = $this->IdentityType->getOptions();
        $this->set('identityTypeOptions', $identityTypeOptions);

        $this->set('id', $identityId);
    }

    public function identitiesDelete($controller, $action) {
        if ($this->Session->check('StudentId') && $this->Session->check('StudentIdentityId')) {
            $id = $this->Session->read('StudentIdentityId');
            $studentId = $this->Session->read('StudentId');
            $name = $this->StudentIdentity->field('number', array('StudentIdentity.id' => $id));
            $this->StudentIdentity->delete($id);
            $this->Utility->alert($name . __(' have been deleted successfully.'));
            $this->redirect(array('action' => 'identities', $studentId));
        }
    }
    
}
