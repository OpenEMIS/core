<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Query;

class ExternalDataSourceAttributesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function getExternalDataSourceAttributeValues($typeName = null) {
		$list = $this->find('list', [
                'groupField' => 'external_data_source_type',
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])->toArray();

		if (!is_null($typeName)) {
			if (isset($list[$typeName])) {
				return $list[$typeName];
			} else {
				return [];
			}
		} else {
			return $list;
		}
	}
}
