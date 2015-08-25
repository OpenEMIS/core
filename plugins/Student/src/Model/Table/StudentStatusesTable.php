<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;

class StudentStatusesTable extends AppTable {
	
	public function initialize(array $config) {
		parent::initialize($config);
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
