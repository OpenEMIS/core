<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;

class UsersTable extends AppTable {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);
		$this->addBehavior('ControllerAction.FileUpload');

		// 'Gender',
		// 'AddressArea' => array(
		// 	'className' => 'AreaAdministrative',
		// 	'foreignKey' => 'address_area_id'
		// ),
		// 'BirthplaceArea' => array(
		// 	'className' => 'AreaAdministrative',
		// 	'foreignKey' => 'birthplace_area_id'
		// ),
	}

	public function getUniqueOpenemisId($options = []) {
		$prefix = '';
		// todo-mlee: implement with config item
		// if (array_key_exists('model', $options)) {
		// 	switch ($options['model']) {
		// 		case 'Student': case 'Staff':
		// 			$prefix = TableRegistry::get('ConfigItem')->find('first', array('limit' => 1,
		// 				'fields' => 'ConfigItem.value',
		// 				'conditions' => array(
		// 					'ConfigItem.name' => strtolower($options['model']).'_prefix'
		// 				)
		// 			));
		// 			$prefix = explode(",", $prefix['ConfigItem']['value']);
		// 			$prefix = ($prefix[1] > 0)? $prefix[0]: '';

		// 			break;
		// 	}
		// }
		
		$latest = $this->find()
			->order('Users.id DESC')
			->first();

		$latestOpenemisNo = $latest['SecurityUser']['openemis_no'];
		if(empty($prefix)){
			$latestDbStamp = $latestOpenemisNo;
		}else{
			$latestDbStamp = substr($latestOpenemisNo, strlen($prefix));
		}
		
		$currentStamp = time();
		if($latestDbStamp >= $currentStamp){
			$newStamp = $latestDbStamp + 1;
		}else{
			$newStamp = $currentStamp;
		}

		return $prefix.$newStamp;
	}

	public function getStatus() {
		return array(0 => __('Inactive', true), 1 => __('Active', true));
	}

	public function validationDefault(Validator $validator) {
		$validator
			->notEmpty('username')
			->notEmpty('first_name');

		return $validator;
	}
}