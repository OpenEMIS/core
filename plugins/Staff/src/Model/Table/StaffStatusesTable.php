<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class StaffStatusesTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('Staff', ['className' => 'Institution.Staff', 'foreignKey' => 'staff_status_id']);
	}

	public function findCodeList() {
		return $this->find('list', ['keyField' => 'code', 'valueField' => 'id'])->toArray();
	}

	public function getIdByCode($code) {
		$entity = $this->find()
			->where([$this->aliasField('code') => $code])
			->first();
		return $entity->id;
	}
}
