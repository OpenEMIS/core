<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class ContactOptionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		//$this->hasMany('ContactTypes', ['className' => 'User.ContactTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsTo('ContactTypes', ['className' => 'FieldOption.ContactTypes']);
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

}
