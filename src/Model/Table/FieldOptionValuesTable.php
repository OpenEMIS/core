<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FieldOptionValuesTable extends AppTable {
	public function initialize(array $config) {
		// public $belongsTo = array(
		// 	'FieldOptions',
		// 	'ModifiedUser' => array(
		// 		'className' => 'SecurityUser',
		// 		'fields' => array('first_name', 'last_name'),
		// 		'foreignKey' => 'modified_user_id'
		// 	),
		// 	'CreatedUser' => array(
		// 		'className' => 'SecurityUser',
		// 		'fields' => array('first_name', 'last_name'),
		// 		'foreignKey' => 'created_user_id'
		// 	)
		// );

		$this->belongsTo('FieldOptions', ['className' => 'FieldOptions']);

	}

	public function getList($customOptions=[]) {
		// pr($this);
		$alias = $this->table();
		$options = [
			'recursive' => -1,
			'joins' => [
				[
					'table' => 'field_options',
					'alias' => 'FieldOption',
					'conditions' => [
						'FieldOption.id = ' . $this->alias() . '.field_option_id',
						"FieldOption.code = '" . $this->alias() . "'"
					]
				]
			],
			'order' => [$this->alias().'.order']
		];


		$options['conditions'] = [];

		if (array_key_exists('visibleOnly', $customOptions)) {
			$options['conditions'][$alias.'.visible >'] = 0;
		}

		if (array_key_exists('conditions', $customOptions)) {
			$options['conditions'] = array_merge($options['conditions'], $customOptions['conditions']);
		}
		
		if (array_key_exists('value', $customOptions)) {
			$selected = $customOptions['value'];
		} else {
			$selected = false;
		}

		$query = $this->find('all')
				->join($options['joins'])
				->order($options['order'])
				->where($options['conditions']);

		$result = array();
		if (array_key_exists('listOnly', $customOptions) && $customOptions['listOnly']) {
			foreach ($query as $key => $value) {
				$name = __($value->name);
				$result[$value->id] = $name;
			}
		} else {
			foreach ($query as $key => $value) {
				array_push($result, 
					array(
						'id' => $value->id,
						'text' => __($value->name), 
						'national_code' => $value->national_code, 
						'value' => $value->id,
						'obsolete' => ($value->visible!='0') ? false : true,
						'selected' => ($selected && $selected==$value->id) ? true : ((!$selected && $value->default!='0') ? true : false)
					)
				);
			}
			if (array_key_exists('value', $customOptions)) {
				$value = $customOptions['value'];

				if (is_array($value)) {
					foreach ($result as $okey => $ovalue) {
					if ($ovalue['obsolete'] == '1' && !in_array($ovalue['value'], $value)) {
						unset($result[$okey]);
					}
				}
				} else {
					foreach ($result as $okey => $ovalue) {
						if ($ovalue['obsolete'] == '1' && $ovalue['value']!=$value) {
							unset($result[$okey]);
						}
					}	
				}
			}
		}
		// pr($result);
		return $result;
	}
}
