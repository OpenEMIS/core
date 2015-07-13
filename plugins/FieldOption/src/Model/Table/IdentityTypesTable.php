<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class IdentityTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		
		$this->hasMany('Identities', ['className' => 'User.Identities', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsTo('FieldOptions', ['className' => 'FieldOptions']);
	}
}
