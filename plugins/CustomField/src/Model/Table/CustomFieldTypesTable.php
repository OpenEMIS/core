<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;

class CustomFieldTypesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function getFieldTypeList($format=[], $types=[]) {
		$query = $this
			->find('list', ['keyField' => 'code', 'valueField' => 'name'])
        	->find('visible');

        if (!empty($format)) {
			$query->where([
                $this->aliasField('format IN ') => $format
            ]);
		}

		if (!empty($types)) {
			$query->where([
				$this->aliasField('code IN ') => $types
            ]);
        }

        return $query->toArray();
    }
}
