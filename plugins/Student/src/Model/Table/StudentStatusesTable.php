<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentStatusesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('Identities', ['className' => 'Identities']);
	}
}