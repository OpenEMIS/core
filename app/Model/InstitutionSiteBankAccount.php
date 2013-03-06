<?php
App::uses('AppModel', 'Model');

class InstitutionSiteBankAccount extends AppModel {
       public $belongsTo = array(
		   'BankBranch'=> array('foreignKey' => 'bank_branch_id'),
           'InstitutionSite' => array('foreignKey' => 'institution_site_id'),
		   'Institution' =>
            array(
                'className'              => 'Institution',
                'joinTable'              => 'institutions',
				'foreignKey' => false,
				'dependent'    => false,
                'conditions' => array(' Institution.id = InstitutionSite.institution_id '),
            )
        );
	
}
?>