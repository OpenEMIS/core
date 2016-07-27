<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class ProvidersTable extends ControllerActionTable {
	public function initialize(array $config)
    {
        $this->addBehavior('FieldOption.FieldOption');
        $this->table('institution_providers');
        parent::initialize($config);

		$this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_provider_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
