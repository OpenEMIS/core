<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;

class QualityVisitTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('quality_visit_types');
		parent::initialize($config);
		$this->hasMany('InstitutionQualityVisits', ['className' => 'Institution.InstitutionQualityVisits', 'foreignKey' => 'quality_visit_type_id']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
	}
}
