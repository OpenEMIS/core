<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class EmploymentTypesTable extends AppTable {
	public $CAVersion = '4.0';
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('employment_types');
		parent::initialize($config);
		$this->hasMany('Employments', ['className' => 'Staff.Employments', 'foreignKey' => 'employment_type_id']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
	}
}
