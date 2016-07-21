<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;

class FeeTypesTable extends ControllerActionTable {
	public function initialize(array $config)
    {
        $this->addBehavior('FieldOption.FieldOption');
        $this->table('fee_types');
        parent::initialize($config);

		$this->hasMany('InstitutionFeeTypes', ['className' => 'Institution.InstitutionFeeTypes', 'foreignKey' => 'fee_type_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
