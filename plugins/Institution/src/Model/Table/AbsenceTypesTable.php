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

class AbsenceTypesTable extends ControllerActionTable {
	use OptionsTrait;
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('StudentAbsences', ['className' => 'Institution.InstitutionStudentAbsences', 'foreignKey' =>'absence_type_id']);
		$this->hasMany('StaffAbsences', ['className' => 'Institution.InstitutionStaffAbsences', 'foreignKey' =>'absence_type_id']);
	}

	public function getCodeList() {
		return $this->find('list', [
				'keyField' => 'id',
				'valueField' => 'code'
			])
			->toArray();
	}

	public function getAbsenceTypeList() {
		return $this
			->find('list')
			->toArray();
	}
}
