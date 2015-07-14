<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InfrastructureOwnershipsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('InstitutionInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'institution_site_infrastructure_id', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
