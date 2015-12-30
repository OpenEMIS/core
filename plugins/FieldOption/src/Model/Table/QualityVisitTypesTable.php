<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;

class QualityVisitTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('InstitutionQualityVisits', ['className' => 'Institution.InstitutionQualityVisits', 'foreignKey' => 'quality_visit_type_id']);
	}
}
