<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;

class QualityVisitTypesTable extends ControllerActionTable {
	public function initialize(array $config)
	{
		$this->addBehavior('FieldOption.FieldOption');
		$this->table('quality_visit_types');
		parent::initialize($config);
		$this->hasMany('InstitutionQualityVisits', ['className' => 'Institution.InstitutionQualityVisits', 'foreignKey' => 'quality_visit_type_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
