<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class ContactTypesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('ContactOptions', ['className' => 'User.ContactOptions']);
		$this->hasMany('UserContacts', ['className' => 'User.UserContacts']);
	}

	public function beforeAction() {
		$this->fields['contact_type_id']['type'] = 'select';
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}
}
