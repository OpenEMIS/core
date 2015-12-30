<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class IdentityTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		
		$this->hasMany('Identities', ['className' => 'User.Identities', 'foreignKey' => 'identity_type_id']);
		$this->belongsTo('FieldOptions', ['className' => 'FieldOptions']);
	}
}
