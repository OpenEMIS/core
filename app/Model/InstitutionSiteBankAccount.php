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

class InstitutionSiteBankAccount extends AppModel {

    public $actsAs = array('ControllerAction');
    public $belongsTo = array(
        'BankBranch' => array('foreignKey' => 'bank_branch_id'),
        'InstitutionSite' => array('foreignKey' => 'institution_site_id'),
        'Institution' =>
        array(
            'className' => 'Institution',
            'joinTable' => 'institutions',
            'foreignKey' => false,
            'dependent' => false,
            'conditions' => array(' Institution.id = InstitutionSite.institution_id '),
        ),
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

        $data = $controller->InstitutionSiteBankAccount->find('all', array('conditions' => array('InstitutionSiteBankAccount.institution_site_id' => $controller->institutionSiteId)));
        $bank = $controller->Bank->find('all', array('conditions' => Array('Bank.visible' => 1)));
        $banklist = $controller->Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));

        $controller->set(compact('data', 'bank', 'banklist'));
    }

    public function bankAccountsView($controller, $params) {
        $bankAccountId = $controller->params['pass'][0];
        $bankAccountObj = $controller->InstitutionSiteBankAccount->find('all', array('conditions' => array('InstitutionSiteBankAccount.id' => $bankAccountId)));

        if (!empty($bankAccountObj)) {
            $controller->Navigation->addCrumb('Bank Account Details');

            $controller->Session->write('InstitutionSiteBankAccountId', $bankAccountId);
            $controller->set('bankAccountObj', $bankAccountObj);
        }
        $banklist = $controller->Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));
        
        $controller->set(compact('banklist'));
    }

    public function bankAccountsAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Bank Accounts');
        if ($controller->request->is('post')) { // save
            $controller->InstitutionSiteBankAccount->create();
            if ($controller->InstitutionSiteBankAccount->save($controller->request->data)) {
                $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                $controller->redirect(array('action' => 'bankAccounts'));
            }
        }
        $bank = $controller->Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));

        $bankId = isset($controller->request->data['InstitutionSiteBankAccount']['bank_id']) ? $controller->request->data['InstitutionSiteBankAccount']['bank_id'] : "";
        if (!empty($bankId)) {
            $bankBranches = $controller->BankBranch->find('list', array('conditions' => array('bank_id' => $bankId, 'visible' => 1), 'recursive' => -1));
        } else {
            $bankBranches = array();
        }
        
        $institutionSiteId = $controller->institutionSiteId;
        
        $controller->set(compact('bankBranches', 'bankId', 'institutionSiteId', 'bank'));
    }

    public function bankAccountsEdit($controller, $params) {
        $bankBranch = array();

        $bankAccountId = $controller->params['pass'][0];

        if ($controller->request->is('get')) {
            $bankAccountObj = $controller->InstitutionSiteBankAccount->find('first', array('conditions' => array('InstitutionSiteBankAccount.id' => $bankAccountId)));

            if (!empty($bankAccountObj)) {
                $controller->Navigation->addCrumb('Edit Bank Account Details');
                //$bankAccountObj['StaffQualification']['qualification_institution'] = $institutes[$staffQualificationObj['StaffQualification']['qualification_institution_id']];
                $controller->request->data = $bankAccountObj;
            }
        } else {
            $controller->request->data['InstitutionSiteBankAccount']['institution_site_id'] = $controller->institutionSiteId;
            if ($controller->InstitutionSiteBankAccount->save($controller->request->data)) {
                $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                $controller->redirect(array('action' => 'bankAccountsView', $controller->request->data['InstitutionSiteBankAccount']['id']));
            }
        }

        $bankId = isset($controller->request->data['InstitutionSiteBankAccount']['bank_id']) ? $controller->request->data['InstitutionSiteBankAccount']['bank_id'] : $bankAccountObj['BankBranch']['bank_id'];

        $bankBranch = $controller->BankBranch->find('list', array('conditions' => array('bank_id' => $bankId, 'visible' => 1), 'recursive' => -1));

        $bank = $controller->Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));
        
        $controller->set(compact('bankId', 'bankBranch', 'bank', 'bankAccountId'));
    }

    public function bankAccountsDelete($controller, $params) {
        if ($controller->Session->check('InstitutionSiteId') && $controller->Session->check('InstitutionSiteBankAccountId')) {
            $id = $controller->Session->read('InstitutionSiteBankAccountId');
            $institutionSiteId = $controller->Session->read('InstitutionSiteId');
            $name = $controller->InstitutionSiteBankAccount->field('account_number', array('InstitutionSiteBankAccount.id' => $id));
            $controller->InstitutionSiteBankAccount->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
            $controller->redirect(array('action' => 'bankAccounts'));
        }
    }

    public function bankAccountsBankBranches($controller, $params) {
        $this->render = false;
        $bank = $controller->Bank->find('all', array('conditions' => Array('Bank.visible' => 1)));
        echo json_encode($bank);
    }

}

?>
