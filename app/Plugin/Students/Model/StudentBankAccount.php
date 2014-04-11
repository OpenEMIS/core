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
    public $actsAs = array('ControllerAction');
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
    
    
    
    
    public function bankAccounts($controller, $params) {
        $controller->Navigation->addCrumb('Bank Accounts');

        $Bank = ClassRegistry::init('Bank');
        
        $data = $this->find('all', array('conditions' => array('StudentBankAccount.student_id' => $controller->studentId)));
        $bank = $Bank->find('all', array('conditions' => Array('Bank.visible' => 1)));
        $banklist = $Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));
        
        $controller->set(compact('data', 'bank', 'banklist'));
    }

    public function bankAccountsView($controller, $params) {
        $bankAccountId = $params['pass'][0];
        $bankAccountObj = $this->find('all', array('conditions' => array('StudentBankAccount.id' => $bankAccountId)));

        if (!empty($bankAccountObj)) {
            $controller->Navigation->addCrumb('Bank Account Details');

            $controller->Session->write('StudentBankAccountId', $bankAccountId);
            $controller->set('bankAccountObj', $bankAccountObj);
        }
        $Bank = ClassRegistry::init('Bank');
        $banklist = $Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));
        $controller->set('banklist', $banklist);
    }

    public function bankAccountsAdd($controller, $params) {
        $controller->Navigation->addCrumb(__('Add Bank Accounts'));
        if ($controller->request->is('post') || $controller->request->is('put')) { // save
           // pr($controller->request->data);die;
            $addMore = false;
            if(isset($controller->data['submit']) && $controller->data['submit']==__('Skip')){
                $controller->Navigation->skipWizardLink($this->action);
            }else if(isset($controller->data['submit']) && $controller->data['submit']==__('Previous')){
                $controller->Navigation->previousWizardLink($this->action);
            }elseif(isset($controller->data['submit']) && $controller->data['submit']==__('Add More')){
                $addMore = true;
            }else{
                $controller->Navigation->validateModel($this->action,'StudentBankAccount');
            }
            
            //$controller->request->data['StudentBankAccount']['student_id'] = $this->studentId;
            $this->create();
            if ($this->save($controller->request->data)) {
                $id = $this->getLastInsertId();
                if($addMore){
                    $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                }
                $controller->Navigation->updateWizard($controller->action,$id,$addMore);
                $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                $controller->redirect(array('action' => 'bankAccounts'));
            }
            
           // pr($this->data);
            /*$addMore = false;
            if(isset($this->data['submit']) && $this->data['submit']==__('Skip')){
                $this->Navigation->skipWizardLink($this->action);
            }else if(isset($this->data['submit']) && $this->data['submit']==__('Previous')){
                $this->Navigation->previousWizardLink($this->action);
            }elseif(isset($this->data['submit']) && $this->data['submit']==__('Add More')){
                $addMore = true;
            }else{
                $this->Navigation->validateModel($this->action,'StudentBankAccount');
            }
            $this->request->data['StudentBankAccount']['student_id'] = $this->studentId;
            $this->StudentBankAccount->create();
            if ($this->StudentBankAccount->save($this->request->data)) {
                $id = $this->StudentBankAccount->getLastInsertId();
                if($addMore){
                    $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                }
                $this->Navigation->updateWizard($this->action,$id,$addMore);
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'bankAccounts'));
            }*/
        }
        $Bank = ClassRegistry::init('Bank');
        $BankBranch = ClassRegistry::init('BankBranch');
        
        $bank = $Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));

        $selectedBankId = isset($controller->request->data['StudentBankAccount']['bank_id']) ? $controller->request->data['StudentBankAccount']['bank_id'] : "";
        if (!empty($selectedBankId)) {
            $bankBranches = $BankBranch->find('list', array('conditions' => array('bank_id' => $selectedBankId, 'visible' => 1), 'recursive' => -1));
        } else {
            $bankBranches = array();
        }

        $studentId = $controller->studentId;
        $controller->set(compact('selectedBankId', 'studentId','bank','bankBranches'));
    }

    public function bankAccountsEdit($controller, $params) {
        $bankBranch = array();

        $id = $params['pass'][0]; //Bank Accound Id
        
        $controller->Navigation->addCrumb('Edit Bank Account Details');
        if ($controller->request->is('get')) {
            $bankAccountObj = $this->find('first', array('conditions' => array('StudentBankAccount.id' => $id)));

            if (!empty($bankAccountObj)) {
                $controller->request->data = $bankAccountObj;
            }
        } else {
            if(isset($controller->data['submit']) && $controller->data['submit']==__('Skip')){
                $controller->Navigation->skipWizardLink($controller->action);
            }else if(isset($controller->data['submit']) && $controller->data['submit']==__('Previous')){
                $controller->Navigation->previousWizardLink($controller->action);
            }
            $controller->request->data['StudentBankAccount']['student_id'] = $this->studentId;
            if ($this->save($controller->request->data)) {
                $controller->Navigation->updateWizard($controller->action,$id);
                $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                $controller->redirect(array('action' => 'bankAccountsView', $controller->request->data['StudentBankAccount']['id']));
            }
        }

        $Bank = ClassRegistry::init('Bank');
        $BankBranch = ClassRegistry::init('BankBranch');
        $selectedBank = isset($controller->request->data['StudentBankAccount']['bank_id']) ? $controller->request->data['StudentBankAccount']['bank_id'] : $bankAccountObj['BankBranch']['bank_id'];
        $bankBranch = $BankBranch->find('list', array('conditions' => array('bank_id' => $selectedBank, 'visible' => 1), 'recursive' => -1));
        $bank = $Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));
        
        $controller->set(compact('selectedBank','bankBranch','bank','id'));
    }

    public function bankAccountsDelete($controller, $params){//$id) {
        if ($controller->Session->check('StudentId') && $controller->Session->check('StudentBankAccountId')) {
            $id = $controller->Session->read('StudentBankAccountId');

            $studentId = $controller->Session->read('StudentId');
            $name = $this->field('account_number', array('StudentBankAccount.id' => $id));
            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
            $controller->redirect(array('action' => 'bankAccounts'));
        }
    }

    public function bankAccountsBankBranches($controller, $params) {
        //$controller->autoRender = false;
        $this->render = false;
        $Bank = ClassRegistry::init('Bank');
        $bank = $Bank->find('all', array('conditions' => Array('Bank.visible' => 1)));
        echo json_encode($bank);
    }

}

?>
