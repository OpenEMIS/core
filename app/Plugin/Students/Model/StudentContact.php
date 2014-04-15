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

class StudentContact extends StudentsAppModel {

    public $actsAs = array('ControllerAction');
    public $belongsTo = array(
        'Student',
        'ContactType',
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
        'contact_type_id' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a Contact Type'
            )
        ),
        'value' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid value'
            )
        ),
        'preferred' => array(
            'comparison' => array(
                'rule' => array('validatePreferred', 'preferred'),
                'allowEmpty' => true,
                'message' => 'Please select a preferred for the selected contact type'
            )
        ),
    );

    function validatePreferred($check1, $field2) {
        $flag = false;
        foreach ($check1 as $key => $value1) {
            $preferred = $this->data[$this->alias][$field2];
            $contactOption = $this->data[$this->alias]['contact_option_id'];
            if ($preferred == "0" && $contactOption != "5") {
                if (isset($this->data[$this->alias]['id'])) {
                    $contactId = $this->data[$this->alias]['id'];
                    $count = $this->find('count', array('conditions' => array('ContactType.contact_option_id' => $contactOption, array('NOT' => array('StudentContact.id' => array($contactId))))));
                    if ($count != 0) {
                        $flag = true;
                    }
                } else {
                    $count = $this->find('count', array('conditions' => array('ContactType.contact_option_id' => $contactOption)));
                    if ($count != 0) {
                        $flag = true;
                    }
                }
            } else {
                $flag = true;
            }
        }
        return $flag;
    }

    public function beforeValidate($options = array()) {
        if (isset($this->data[$this->alias]['contact_option_id'])) {
            $contactOption = $this->data[$this->alias]['contact_option_id'];
            switch ($contactOption) {
                case 1:
                case 2:
                case 3:
                    $this->validate['value'] = array('customVal' => array(
                            'rule' => 'numeric',
                            'required' => true,
                            'message' => 'Please enter a valid Numeric value'
                    ));
                    break;
                case 4:
                    $this->validate['value'] = array('customVal' => array(
                            'rule' => 'email',
                            'required' => true,
                            'message' => 'Please enter a valid Email'
                    ));
                    break;
                case 5:
                    $this->validate['value'] = array('customVal' => array(
                            'rule' => 'notEmpty',
                            'required' => true,
                            'message' => 'Please enter a valid Value'
                    ));
                    break;
                default:
                    break;
            }
        }
        return true;
    }
    
    public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
    }
    
    public function getDisplayFields($controller) {
        
        $ContactOption = ClassRegistry::init('ContactOption');
        $contactOptions = $ContactOption->findList();
        
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'id', 'type' => 'hidden'),
                array('field' => 'contact_option_id', 'model' => 'ContactType', 'labelKey' => 'general.type', 'type' => 'select', 'options' => $contactOptions),
                array('field' => 'name', 'model' => 'ContactType', 'labelKey' => 'general.description'),
                array('field' => 'value'),
                array('field' => 'preferred', 'type' => 'select', 'options' => $controller->Option->get('yesno')),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }

    public function contacts($controller, $params) {
        $controller->Navigation->addCrumb('Contacts');
        $data = $this->find('all', array('conditions' => array('StudentContact.student_id' => $controller->studentId), 'order' => array('ContactType.contact_option_id', 'StudentContact.preferred DESC')));

        $ContactOption = ClassRegistry::init('ContactOption');
        $contactOptions = $ContactOption->getOptions();
        $controller->set('contactOptions', $contactOptions);

        $controller->set('list', $data);
    }

    public function contactsAdd($controller, $params) {
        $controller->Navigation->addCrumb(__('Add Contacts'));
        $header = __('Add Contacts');
        $studentId = $controller->studentId;
        if ($controller->request->is('post')) {
            $contactData = $controller->request->data['StudentContact'];
            $addMore = false;
            if (isset($controller->request->data['submit']) && $controller->request->data['submit'] == __('Skip')) {
                $controller->Navigation->skipWizardLink($controller->request->action);
            } else if (isset($controller->request->data['submit']) && $controller->request->data['submit'] == __('Previous')) {
                $controller->Navigation->previousWizardLink($controller->request->action);
            } elseif (isset($controller->request->data['submit']) && $controller->request->data['submit'] == __('Add More')) {
                $addMore = true;
            } else {
                $controller->Navigation->validateModel($controller->request->action, 'StudentContact');
            }
            $this->create();
            

            if ($this->save($contactData)) {
                if ($contactData['preferred'] == '1') {
                    $this->updateAll(array('StudentContact.preferred' => '0'), array('ContactType.contact_option_id' => $contactData['contact_option_id'], array('NOT' => array('StudentContact.id' => array($this->getLastInsertId())))));
                }
                $id = $this->getLastInsertId();
                if ($addMore) {
                    $controller->Message->alert('general.add.success');
                }
                $controller->Navigation->updateWizard($controller->request->action, $id, $addMore);
                $controller->Message->alert('general.add.success');
                return $controller->redirect(array('action' => 'contacts'));
            }
        }

        $ContactOption = ClassRegistry::init('ContactOption');
        $ContactType = ClassRegistry::init('ContactType');
        $contactOptions = $ContactOption->getOptions();

        $contactOptionId = isset($params['pass'][0]) ? $params['pass'][0] : key($contactOptions);
        $contactTypeOptions = $ContactType->find('list', array('conditions' => array('contact_option_id' => $contactOptionId, 'visible' => 1), 'recursive' => -1));
        $yesnoOptions = $controller->Option->get('yesno');
        
        $controller->set(compact('header','contactOptions','contactTypeOptions','contactOptionId','yesnoOptions','studentId'));

        $controller->UserSession->readStatusSession($controller->request->action);
    }

    public function contactsView($controller, $params) {
        $contactId = isset($params['pass'][0])?$params['pass'][0]:0;
        $data = $this->findById($contactId);

        if (empty($data)) {
            $controller->Message->alert('general.noData');
            return $controller->redirect(array('action' => 'contacts'));
        }

        $controller->Navigation->addCrumb('Contact Details');
        $header = __('Details');
        
        $controller->Session->write('StudentContactId', $contactId);
        
        $fields = $this->getDisplayFields($controller);
        $controller->set(compact('header', 'data', 'fields'));
    }

    public function contactsEdit($controller, $params) {
        $controller->Navigation->addCrumb('Edit Contact');
        $header = __('Edit Contacts');
        $id = isset($params['pass'][0])?$params['pass'][0]:0; //<<-- contact id
        $data = array();
        if ($controller->request->is('get')) {
            $data = $this->findById($id);

            if (!empty($data)) {
                $controller->request->data = $data;
            }
        } else {
            $contactData = $controller->request->data['StudentContact'];
            if (isset($controller->request->data['submit']) && $controller->request->data['submit'] == __('Skip')) {
                $controller->Navigation->skipWizardLink($controller->request->action);
            } else if (isset($controller->request->data['submit']) && $controller->request->data['submit'] == __('Previous')) {
                $controller->Navigation->previousWizardLink($controller->request->action);
            }
            $contactData['student_id'] = $controller->studentId;

            if ($this->save($contactData)) {
                if ($contactData['preferred'] == '1') {
                    $this->updateAll(array('StudentContact.preferred' => '0'), array('ContactType.contact_option_id' => $contactData['contact_option_id'], array('NOT' => array('StudentContact.id' => array($id)))));
                }
                $controller->Navigation->updateWizard($controller->request->action, $id);
                $controller->Message->alert('general.add.success');
                return $controller->redirect(array('action' => 'contactsView', $contactData['id']));
            }
        }

        $ContactOption = ClassRegistry::init('ContactOption');
        $ContactType = ClassRegistry::init('ContactType');
        
        $contactOptions = $ContactOption->getOptions();
        $controller->set('contactOptions', $contactOptions);

        $contactOptionId = isset($params['pass'][1]) ? $params['pass'][1] : $data['ContactType']['contact_option_id'];
        $contactTypeOptions = $ContactType->find('list', array('conditions' => array('contact_option_id' => $contactOptionId, 'visible' => 1), 'recursive' => -1));
        $yesnoOptions = $controller->Option->get('yesno');
        $controller->set(compact('id' ,'header','contactOptions','contactTypeOptions','contactOptionId','yesnoOptions'));
    }

    public function contactsDelete($controller, $params) {
        if ($controller->Session->check('StudentId') && $controller->Session->check('StudentContactId')) {
            $id = $controller->Session->read('StudentContactId');
            $studentId = $controller->Session->read('StudentId');

            if($this->delete($id)) {
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }
            
            $controller->Session->delete('StudentContactId');
            return $controller->redirect(array('action' => 'contacts', $studentId));
        }
    }

}
