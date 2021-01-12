<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class InstitutionCommitteeMeetingTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'foreignKey' =>'institution_committee_id']);
		$this->addBehavior('FieldOption.FieldOption');
	}
}
