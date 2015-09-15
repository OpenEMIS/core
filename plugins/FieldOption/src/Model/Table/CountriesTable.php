<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class CountriesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('countries');
		parent::initialize($config);
		$this->hasMany('Nationalities', ['className' => 'User.Nationalities', 'foreignKey' => 'country_id']);
		$this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->notEmpty('name', 'Please enter a name.');

		return $validator;
	}
}
