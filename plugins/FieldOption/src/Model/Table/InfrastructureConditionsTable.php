<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureConditionsTable extends ControllerActionTable {
	public function initialize(array $config)
    {
		$this->addBehavior('FieldOption.FieldOption');
		$this->table('infrastructure_conditions');
		parent::initialize($config);
		$this->hasMany('InstitutionInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'infrastructure_condition_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
