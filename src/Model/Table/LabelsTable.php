<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LabelsTable extends AppTable {
	public function getLabel($module, $field, $language) {
		$label = false;
		$data = $this->findByModuleAndField($module, $field)->first();

		if ($data) {
			$label = ucfirst($data->$language);
			if (!empty($data->code)) {
				$label = '(' . $data->code . ') ' . $label;
			}
		}
		return $label;
	}
}
