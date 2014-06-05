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

App::uses('AppModel', 'Model');

class StudentBankAccount extends AppModel {

    public $actsAs = array('ControllerAction','Containable');
    public $belongsTo = array(
        'BankBranch' => array('foreignKey' => 'bank_branch_id'),
        'Student' => array('foreignKey' => 'student_id'),
        'ModifiedUser' => array('foreignKey' => 'modified_user_id', 'className' => 'SecurityUser'),
        'CreatedUser' => array('foreignKey' => 'created_user_id', 'className' => 'SecurityUser'),
    );
    public $validate = array(
        'account_name' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter an Account name'
            )
        ),
        'account_number' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter an Account number'
            )
        ),
        'bank_id' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please select a Bank'
            )
        ),
        'bank_branch_id' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please select a Bank Branch'
            )
        )
    );

    public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
    }

    public function getDisplayFields($controller) {
        $Bank = ClassRegistry::init('Bank');
        $bankOptions = $Bank->findList();
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'id', 'type' => 'hidden'),
                array('field' => 'bank_id', 'model' => 'BankBranch', 'type' => 'select', 'options' => $bankOptions),
                array('field' => 'name', 'model' => 'BankBranch'),
                array('field' => 'account_name'),
                array('field' => 'account_number'),
                array('field' => 'active', 'type' => 'select', 'options' => $controller->Option->get('yesno')),
                array('field' => 'remarks', 'type' => 'textarea'),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }

    public function bankAccounts($controller, $params) {
        $controller->Navigation->addCrumb('Bank Accounts');
        $header = __('Bank Accounts');
        $this->recursive = 2;
        $this->unbindModel(array('belongsTo' => array('Student', 'ModifiedUser', 'CreatedUser')));
        $this->contain(array('BankBranch' => array(
            'Bank' => array(
                'name'
            ),
            'fields' => array( 'name')
        )));
        $data = $this->findAllByStudentId($controller->studentId);
       
        $controller->set(compact('data', 'header'));
    }

    public function bankAccountsView($controller, $params) {
        $bankAccountId = $params['pass'][0];
        $data = $this->findById($bankAccountId);//('first', array('conditions' => array('StudentBankAccount.id' => $bankAccountId)));
        
        $header = __('Bank Accounts');
        $controller->Navigation->addCrumb('Bank Account Details');

        if (empty($data)) {
			$controller->Message->alert('general.noData');
            return $controller->redirect(array('action' => 'bankAccounts'));
        }

        $controller->Session->write('StudentBankAccountId', $bankAccountId);
        $fields = $this->getDisplayFields($controller);
        $controller->set(compact('header', 'data', 'fields'));
    }

    public function bankAccountsAdd($controller, $params) {
        $header = __('Add Bank Accounts');
        $controller->Navigation->addCrumb(__('Add Bank Accounts'));
        if ($controller->request->is('post') || $controller->request->is('put')) { // save
            $addMore = false;
            if (isset($controller->data['submit']) && $controller->data['submit'] == __('Skip')) {
                $controller->Navigation->skipWizardLink($controller->action);
            } else if (isset($controller->data['submit']) && $controller->data['submit'] == __('Previous')) {
                $controller->Navigation->previousWizardLink($controller->action);
            } elseif (isset($controller->data['submit']) && $controller->data['submit'] == __('Add More')) {
                $addMore = true;
            } else {
                $controller->Navigation->validateModel($controller->action, 'StudentBankAccount');
            }

            $controller->request->data['StudentBankAccount']['student_id'] = $this->studentId;
            $this->create();
            if ($this->save($controller->request->data)) {
                $id = $this->getLastInsertId();
                if ($addMore) {
                    //$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                    $controller->Message->alert('general.add.success');
                }
                $controller->Navigation->updateWizard($controller->action, $id, $addMore);
                $controller->Message->alert('general.add.success');
                //$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                return $controller->redirect(array('action' => 'bankAccounts'));
            }
            else{
                $bankId = $controller->request->data[$this->alias]['bank_id'];
            }

            // pr($this->data);
            /* $addMore = false;
              if(isset($this->data['submit']) && $this->data['submit']==__('Skip')){
              $this->Navigation->skipWizardLink($controller->request->action);
              }else if(isset($this->data['submit']) && $this->data['submit']==__('Previous')){
              $this->Navigation->previousWizardLink($controller->request->action);
              }elseif(isset($this->data['submit']) && $this->data['submit']==__('Add More')){
              $addMore = true;
              }else{
              $this->Navigation->validateModel($controller->request->action,'StudentBankAccount');
              }
              $this->request->data['StudentBankAccount']['student_id'] = $this->studentId;
              $this->StudentBankAccount->create();
              if ($this->StudentBankAccount->save($this->request->data)) {
              $id = $this->StudentBankAccount->getLastInsertId();
              if($addMore){
              $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
              }
              $this->Navigation->updateWizard($controller->request->action,$id,$addMore);
              $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
              $this->redirect(array('action' => 'bankAccounts'));
              } */
        }
        $Bank = ClassRegistry::init('Bank');
        //$BankBranch = ClassRegistry::init('BankBranch');

        $bankOptions = $Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));

        $bankId = empty($bankId)?key($bankOptions): $bankId;
        $bankId = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : $bankId;

        if (!empty($bankId)) {
            $bankBranchesOptions = $this->BankBranch->find('list', array('conditions' => array('bank_id' => $bankId, 'visible' => 1), 'recursive' => -1));
        } else {
            $bankBranchesOptions = array();
        }

        $studentId = $controller->studentId;
        $yesnoOptions = $controller->Option->get('yesno');
        $controller->set(compact('bankId', 'studentId', 'bankOptions', 'bankBranchesOptions', 'yesnoOptions', 'header'));
    }

    public function bankAccountsEdit($controller, $params) {
        $header = __('Bank Account Details');
        $controller->Navigation->addCrumb('Edit Bank Account Details');
        $yesnoOptions = $controller->Option->get('yesno');

        $bankAccountId = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : 0;
        $data = $this->findById($bankAccountId);

        if (!empty($data)) {
            if ($controller->request->is('post') || $controller->request->is('put')) {
                if (isset($controller->data['submit']) && $controller->data['submit'] == __('Skip')) {
                    $controller->Navigation->skipWizardLink($controller->action);
                } else if (isset($controller->data['submit']) && $controller->data['submit'] == __('Previous')) {
                    $controller->Navigation->previousWizardLink($controller->action);
                }
                $controller->request->data['StudentBankAccount']['student_id'] = $controller->studentId;
                
                $this->unbindModel(array('validate' => array('bank_id')));
                if ($this->save($controller->request->data)) {
                    $controller->Navigation->updateWizard($controller->action, $bankAccountId);
                    $controller->Message->alert('general.add.success');
                    return $controller->redirect(array('action' => 'bankAccountsView', $bankAccountId));
                }
                else{
                   // pr($this->invalidFields());
                }
            } else {
                $controller->request->data = $data;
            }
            $Bank = ClassRegistry::init('Bank');
            //$BankBranch = ClassRegistry::init('BankBranch');
            $bankBranchOptions = $this->BankBranch->find('list', array('conditions' => array('bank_id' => $data['BankBranch']['bank_id'], 'visible' => 1), 'recursive' => -1));
            $bankObj = $Bank->findById($data['BankBranch']['bank_id']);

            $controller->set(compact('bankObj', 'bankBranchOptions', 'header', 'yesnoOptions'));
        } else {
            return $controller->redirect(array('action' => 'bankAccounts'));
        }
    }

    public function bankAccountsDelete($controller, $params) {//$id) {
        if ($controller->Session->check('StudentId') && $controller->Session->check('StudentBankAccountId')) {
            $id = $controller->Session->read('StudentBankAccountId');

            if($this->delete($id)) {
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }
            $controller->Session->delete('StudentBankAccountId');
            return $controller->redirect(array('action' => 'bankAccounts'));
        }
    }
/*
    public function bankAccountsBankBranches($controller, $params) {
        //$controller->autoRender = false;
        $this->render = false;
        $Bank = ClassRegistry::init('Bank');
        $bank = $Bank->find('all', array('conditions' => Array('Bank.visible' => 1)));
        echo json_encode($bank);
    }*/

}

?>
