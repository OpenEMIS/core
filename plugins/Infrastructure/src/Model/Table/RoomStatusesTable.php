<?php
namespace Infrastructure\Model\Table;

use App\Model\Table\ControllerActionTable;

class RoomStatusesTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('InstitutionRooms', ['className' => 'Institution.InstitutionRooms', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function findCodeList() {
		return $this->find('list', ['keyField' => 'code', 'valueField' => 'id'])->toArray();
	}

	public function getIdByCode($code) {
		$entity = $this->find()->where([$this->aliasField('code') => $code])->first();
		
		if ($entity) {
			return $entity->id;
		} else {
			return '';
		}
	}
}
