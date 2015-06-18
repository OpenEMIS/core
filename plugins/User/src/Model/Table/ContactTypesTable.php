<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\Query;

class ContactTypesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('ContactOptions', ['className' => 'User.ContactOptions']);
		$this->hasMany('Contacts', ['className' => 'User.Contacts']);
	}

	public function findWithContactOptions(Query $query, array $options) {
		return $query
			->contain(['ContactOptions'])
			->order([$this->aliasField('order') => 'ASC']);
	}

	public function beforeAction() {
		$this->fields['contact_type_id']['type'] = 'select';
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}
}
