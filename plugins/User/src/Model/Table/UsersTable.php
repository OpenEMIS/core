<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;

class UsersTable extends AppTable {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);
		$this->addBehavior('ControllerAction.FileUpload');

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		// 'AddressArea' => array(
		// 	'className' => 'AreaAdministrative',
		// 	'foreignKey' => 'address_area_id'
		// ),
		// 'BirthplaceArea' => array(
		// 	'className' => 'AreaAdministrative',
		// 	'foreignKey' => 'birthplace_area_id'
		// ),

		$this->hasMany('InstitutionSiteStudents', ['className' => 'Institution.InstitutionSiteStudents', 'foreignKey' => 'security_user_id']);
		$this->hasMany('UserIdentities', ['className' => 'User.UserIdentities', 'foreignKey' => 'security_user_id']);
		$this->hasMany('UserNationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'security_user_id']);
		$this->hasMany('UserSpecialNeeds', ['className' => 'User.UserSpecialNeeds', 'foreignKey' => 'security_user_id']);
		$this->hasMany('UserContacts', ['className' => 'User.UserContacts', 'foreignKey' => 'security_user_id']);
	}

	public function addEditBeforePatch(Event $event, Entity $entity, array $data, array $options) {
		$options['associated'] = ['InstitutionSiteStudents', 'UserIdentities', 'UserNationalities', 'UserSpecialNeeds', 'UserContacts'];
		return compact('entity', 'data', 'options');
	}

	public function getUniqueOpenemisId($options = []) {
		$prefix = '';
		
		if (array_key_exists('model', $options)) {
			switch ($options['model']) {
				case 'Student': case 'Staff':
					$prefix = TableRegistry::get('ConfigItems')->value(strtolower($options['model']).'_prefix');
					$prefix = explode(",", $prefix);
					$prefix = ($prefix[1] > 0)? $prefix[0]: '';
					break;
			}
		}
		
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