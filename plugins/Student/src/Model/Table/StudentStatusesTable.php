<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;

class StudentStatusesTable extends AppTable {
	public $PENDING_TRANSFER = -2;
	public $PENDING_ADMISSION = -3;
	public $PENDING_DROPOUT = -4;

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

		if ($entity) {
			return $entity->id;
		} else {
			return '';
		}
	}

	public function getThresholdOptions()
    {
    	// options only dropout and repeated
    	$options = ['DROPOUT', 'REPEATED'];

        return $this
            ->find('list')
            ->where([$this->aliasField('code') . ' IN ' => $options])
            ->toArray()
        ;
    }
}
