// FieldOptionValuesBehavior: No longer used. Use field_option plugin<br><?php 
// namespace App\Model\Behavior;

// use Cake\ORM\Behavior;

// class FieldOptionValuesBehavior extends Behavior {
// 	public function initialize(array $config) {
// 	}


// 	public function getList($customOptions=[]) {
// 		// pr($this);
// 		// pr($this->_table);
// 		// pr($this->_table->alias());
// 		// return $this->_table->find('list')->toArray();
// 		// $this->find('list');

// 		$alias = $this->_table->alias();
// 		$options = [
// 			'recursive' => -1,
// 			'joins' => [
// 				[
// 					'table' => 'field_options',
// 					'alias' => 'FieldOption',
// 					'conditions' => [
// 						'FieldOption.id = ' . $alias . '.field_option_id',
// 						"FieldOption.code = '" . $alias . "'"
// 					]
// 				]
// 			],
// 			'order' => [$alias.'.order']
// 		];

// 		$options['conditions'] = [];

// 		if (array_key_exists('visibleOnly', $customOptions)) {
// 			$options['conditions'][$alias.'.visible >'] = 0;
// 		}

// 		if (array_key_exists('conditions', $customOptions)) {
// 			$options['conditions'] = array_merge($options['conditions'], $customOptions['conditions']);
// 		}
		
// 		if (array_key_exists('value', $customOptions)) {
// 			$selected = $customOptions['value'];
// 		} else {
// 			$selected = false;
// 		}

// 		$query = $this->_table->find('all')
// 				->join($options['joins'])
// 				->order($options['order'])
// 				->where($options['conditions']);

// 		$result = array();
// 		if (array_key_exists('listOnly', $customOptions) && $customOptions['listOnly']) {
// 			foreach ($query as $key => $value) {
// 				$name = __($value->name);
// 				$result[$value->id] = $name;
// 			}
// 		} else {
// 			foreach ($query as $key => $value) {
// 				array_push($result, 
// 					array(
// 						'id' => $value->id,
// 						'text' => __($value->name), 
// 						'national_code' => $value->national_code, 
// 						'value' => $value->id,
// 						'obsolete' => ($value->visible!='0') ? false : true,
// 						'selected' => ($selected && $selected==$value->id) ? true : ((!$selected && $value->default!='0') ? true : false)
// 					)
// 				);
// 			}
// 			if (array_key_exists('value', $customOptions)) {
// 				$value = $customOptions['value'];

// 				if (is_array($value)) {
// 					foreach ($result as $okey => $ovalue) {
// 					if ($ovalue['obsolete'] == '1' && !in_array($ovalue['value'], $value)) {
// 						unset($result[$okey]);
// 					}
// 				}
// 				} else {
// 					foreach ($result as $okey => $ovalue) {
// 						if ($ovalue['obsolete'] == '1' && $ovalue['value']!=$value) {
// 							unset($result[$okey]);
// 						}
// 					}	
// 				}
// 			}
// 		}
// 		// pr($result);
// 		return $result;
// 	}

// }

// ?>