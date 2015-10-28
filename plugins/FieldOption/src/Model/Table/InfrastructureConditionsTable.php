<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InfrastructureConditionsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('InstitutionInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'infrastructure_condition_id']);
	}
}
