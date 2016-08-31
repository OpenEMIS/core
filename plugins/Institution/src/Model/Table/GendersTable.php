<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class GendersTable extends ControllerActionTable {
	public function initialize(array $config)
    {
        $this->addBehavior('FieldOption.FieldOption');
        $this->table('institution_genders');
        parent::initialize($config);

		$this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_gender_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
