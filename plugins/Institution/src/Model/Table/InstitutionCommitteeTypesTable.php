<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class InstitutionCommitteeTypesTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'foreignKey' =>'institution_committee_type_id']);
	}
}
