<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureOwnershipsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('infrastructure_ownerships');
		parent::initialize($config);
		$this->hasMany('InstitutionInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'infrastructure_ownership_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
