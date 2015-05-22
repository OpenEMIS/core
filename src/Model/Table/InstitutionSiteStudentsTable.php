<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class InstitutionSiteStudentsTable extends Table {
	public function initialize(array $config) {
		// $this->table('security_users');

		// $this->belongsTo('Users',[
		// 	'foreignKey' => 'security_user_id',
		// ]);

		// $testdata = $this->get(1);
		// pr((array)$testdata);
		// die;
		// 
		// 
		
		$this->belongsTo('InstitutionSites');
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}


}
