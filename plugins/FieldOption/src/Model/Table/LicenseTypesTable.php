<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class LicenseTypesTable extends AppTable {
	public $CAVersion = '4.0';
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('license_types');
		parent::initialize($config);
		$this->hasMany('Licenses', ['className' => 'Staff.Licenses', 'foreignKey' => 'license_type_id']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
	}
}
