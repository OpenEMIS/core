<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class TypesTable extends AppTable {
	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        $this->table('institution_types');
        parent::initialize($config);
		
		$this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_type_id']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);		
	}
}
