<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;

class QualityVisitTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('InstitutionQualityVisits', ['className' => 'Institution.InstitutionQualityVisits', 'foreignKey' => 'institution_site_quality_visit_id']);
	}
}
