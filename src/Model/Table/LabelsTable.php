<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LabelsTable extends AppTable {
	public function getLabel($module, $field, $language) {
		$label = false;
		$data = $this->findByModuleAndField($module, $field)->first();
		
		// Default language
		$default = 'en';
		
		if ($data) {
			if (!empty($data->$language)) {
				$label = ucfirst($data->$language);
			}else{
				$label = ucfirst($data->$default);
			}
			if (!empty($data->code)) {
				$label = '(' . $data->code . ') ' . $label;
			}		
		}
		return $label;
	}
}
