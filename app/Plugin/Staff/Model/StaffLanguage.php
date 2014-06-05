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

class StaffLanguage extends StaffAppModel {
	public $actsAs = array('ControllerAction', 'DatePicker' => array('evaluation_date'));
	public $belongsTo = array(
		'Staff',
		'Language',
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
		'language_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Language'
			)
		),
		'listening' => array(
			'ruleRequired' => array(
				'rule'    => array('range', -1, 6),
				'allowEmpty' => true,
				'message' => 'Please enter a number between 0 and 5'
			)
		),
		'speaking' => array(
			'ruleRequired' => array(
				'rule'    => array('range', -1, 6),
				'allowEmpty' => true,
				'message' => 'Please enter a number between 0 and 5'
			)
		),
		'reading' => array(
			'ruleRequired' => array(
				'rule'    => array('range', -1, 6),
				'allowEmpty' => true,
				'message' => 'Please enter a number between 0 and 5'
			)
		),
		'writing' => array(
			'ruleRequired' => array(
				'rule'    => array('range', -1, 6),
				'allowEmpty' => true,
				'message' => 'Please enter a number between 0 and 5'
			)
		),
	);

	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'id', 'type' => 'hidden'),
                array('field' => 'evaluation_date'),
                array('field' => 'name', 'model' => 'Language', 'labelKey' => 'general.type'),
                array('field' => 'listening'),
                array('field' => 'speaking'),
                array('field' => 'reading'),
                array('field' => 'writing'),
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

    public function languages($controller, $params) {
        $controller->Navigation->addCrumb('Languages');
        $header = __('Languages');
        $this->unbindModel(array('belongsTo' => array('Staff', 'ModifiedUser','CreatedUser')));
        $data = $this->findAllByStaffId($controller->staffId);//('all', array('conditions' => array('StudentLanguage.student_id' => $controller->studentId)));
        $controller->set(compact('data', 'header'));
    }

	public function languagesAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Languages');
        $header = __('Add Languages');
        if ($controller->request->is('post')) {
            $addMore = false;
            $data = $controller->request->data['StaffLanguage'];
            if (isset($controller->data['submit']) && $controller->data['submit'] == __('Skip')) {
                $controller->Navigation->skipWizardLink($controller->action);
            } else if (isset($controller->data['submit']) && $controller->data['submit'] == __('Previous')) {
                $controller->Navigation->previousWizardLink($controller->action);
            } elseif (isset($controller->data['submit']) && $controller->data['submit'] == __('Add More')) {
                $addMore = true;
            } else {
                $controller->Navigation->validateModel($controller->action, 'StaffLanguage');
            }

            $this->create();
            $data['staff_id'] = $controller->staffId;

            if ($this->save($data)) {
                $id = $this->getLastInsertId();
                if ($addMore) {
                    $controller->Message->alert('general.add.success');
                }
                $controller->Navigation->updateWizard($controller->action, $id, $addMore);
                $controller->Message->alert('general.add.success');
                return $controller->redirect(array('action' => 'languages'));
            }
        }

        $gradeOptions = array();
        for ($i = 0; $i < 6; $i++) {
            $gradeOptions[$i] = $i;
        }

        $languageOptions = $this->Language->getOptions();
        $controller->UserSession->readStatusSession($controller->request->action);
        
        $controller->set(compact('header', 'gradeOptions','languageOptions'));
    }
    
	public function languagesView($controller, $params) {
        $id = isset($params['pass'][0]) ? $params['pass'][0] : 0;
        $controller->Navigation->addCrumb('Language Details');
        $header = __('Language Details');
        $data = $this->findById($id);

        if (empty($data)) {
            $controller->Message->alert('general.noData');
            return $controller->redirect(array('action' => 'languages'));
        }
        $controller->Session->write('StaffLanguageId', $id);

        $fields = $this->getDisplayFields($controller);
        $controller->set(compact('header', 'data', 'fields', 'id'));
    }
   

	public function languagesEdit($controller, $params) {
        $id = isset($params['pass'][0]) ? $params['pass'][0] : 0;
        $controller->Navigation->addCrumb('Edit Language');
        $header = __('Edit Language');

        if ($controller->request->is('post') || $controller->request->is('put')) {
            $languageData = $controller->request->data['StaffLanguage'];
            if (isset($controller->data['submit']) && $controller->data['submit'] == __('Skip')) {
                $controller->Navigation->skipWizardLink($controller->action);
            } else if (isset($controller->data['submit']) && $controller->data['submit'] == __('Previous')) {
                $controller->Navigation->previousWizardLink($controller->action);
            }
			$languageData['staff_id'] = $controller->staffId;
            if ($this->save($languageData)) {
                $controller->Navigation->updateWizard($controller->action, $id);
                return $controller->redirect(array('action' => 'languagesView', $id));
            }
        } else {
            $data = $this->findById($id);

            if (empty($data)) {
                return $controller->redirect(array('action' => 'languages'));
            }

            $controller->request->data = $data;
        }

        $gradeOptions = array();
        for ($i = 0; $i < 6; $i++) {
            $gradeOptions[$i] = $i;
        }
        $languageOptions = $this->Language->getOptions();
        $controller->set(compact('id', 'header', 'gradeOptions', 'languageOptions'));
    }
	
    public function languagesDelete($controller, $params) {
        if ($controller->Session->check('StaffId') && $controller->Session->check('StaffLanguageId')) {
            $id = $controller->Session->read('StaffLanguageId');
            if ($this->delete($id)) {
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }

            $controller->Session->delete('StaffLanguageId');
            return $controller->redirect(array('action' => 'languages'));
        }
    }

}
