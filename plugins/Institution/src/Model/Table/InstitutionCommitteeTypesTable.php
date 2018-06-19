<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class InstitutionCommitteeTypesTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'foreignKey' =>'institution_committee_type_id']);
		$this->addBehavior('FieldOption.FieldOption');
	}
}
