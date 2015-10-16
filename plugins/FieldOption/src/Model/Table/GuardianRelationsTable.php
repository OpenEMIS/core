<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class GuardianRelationsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);

		$this->hasMany('Guardians', ['className' => 'Student.Guardians', 'foreignKey' => 'guardian_relation_id']);
	}
}
