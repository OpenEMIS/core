<?php 
namespace User\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

class UserBehavior extends Behavior {
	private $defaultStudentProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-students'></i></div></div>";
	private $defaultStaffProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-staff'></i></div></div>";
	private $defaultGuardianProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='fa fa-user'></i></div></div>";
	private $defaultUserProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='fa fa-user'></i></div></div>";

	private $defaultStudentProfileView = "<div class='profile-image'><i class='kd-students'></i></div>";
	private $defaultStaffProfileView = "<div class='profile-image'><i class='kd-staff'></i></div>";
	private $defaultGuardianProfileView = "<div class='profile-image'><i class='fa fa-user'></i></div>";
	private $defaultUserProfileView = "<div class='profile-image'><i class='fa fa-user'></i></div>";

	private $defaultImgIndexClass = "profile-image-thumbnail";
	private $defaultImgViewClass= "profile-image";
	private $defaultImgMsg = "<p>* Advisable photo dimension 90 by 115px<br>* Format Supported: .jpg, .jpeg, .png, .gif </p>";

	public function initialize(array $config) {
		if ($this->_table->table() == 'security_users') {
			$this->_table->addBehavior('ControllerAction.FileUpload', [
				'name' => 'photo_name',
				'content' => 'photo_content',
				'size' => '2MB',
				'contentEditable' => true,
				'allowable_file_types' => 'image'
			]);
		}
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.beforeAction'] = 'beforeAction';
		$events['ControllerAction.Model.index.beforePaginate'] = ['callable' => 'indexBeforePaginate', 'priority' => 0];
		$events['ControllerAction.Model.index.beforeAction'] = ['callable' => 'indexBeforeAction', 'priority' => 50];
		$events['ControllerAction.Model.onGetFieldLabel'] = ['callable' => 'onGetFieldLabel', 'priority' => 50];
		return $events;
	}

	public function beforeAction(Event $event) {
		if ($this->_table->table() == 'security_users') {
			$this->_table->addBehavior('Area.Areapicker');
			$this->_table->fields['photo_name']['visible'] = false;
			$this->_table->fields['super_admin']['visible'] = false;
			$this->_table->fields['date_of_death']['visible'] = false;
			$this->_table->fields['status']['visible'] = false;
			$this->_table->fields['address_area_id']['type'] = 'areapicker';
			$this->_table->fields['address_area_id']['source_model'] = 'Area.AreaAdministratives';
			$this->_table->fields['birthplace_area_id']['type'] = 'areapicker';
			$this->_table->fields['birthplace_area_id']['source_model'] = 'Area.AreaAdministratives';
			$this->_table->fields['gender_id']['type'] = 'select';

			$i = 10;
			$this->_table->fields['first_name']['order'] = $i++;
			$this->_table->fields['middle_name']['order'] = $i++;
			$this->_table->fields['third_name']['order'] = $i++;
			$this->_table->fields['last_name']['order'] = $i++;
			$this->_table->fields['preferred_name']['order'] = $i++;
			$this->_table->fields['gender_id']['order'] = $i++;
			$this->_table->fields['date_of_birth']['order'] = $i++;
			$this->_table->fields['address']['order'] = $i++;
			$this->_table->fields['postal_code']['order'] = $i++;
			$this->_table->fields['address_area_id']['order'] = $i++;
			$this->_table->fields['birthplace_area_id']['order'] = $i++;

			if ($this->_table->action != 'index') {
				$this->_table->ControllerAction->field('photo_content', ['type' => 'image', 'order' => 0]);
				$this->_table->ControllerAction->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
			}
		}
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->_table->ControllerAction->field('photo_content', ['type' => 'image', 'order' => 0]);
		$this->_table->ControllerAction->field('openemis_no', [
			'type' => 'readonly',
			'order' => 1,
			'sort' => ['field' => 'Users.openemis_no']
		]);
		$this->_table->ControllerAction->field('identity', ['order' => 2]);

		if ($this->_table->table() == 'security_users') {
			$this->_table->ControllerAction->field('name', [
				'order' => 3, 
				'sort' => ['field' => 'Users.first_name']
			]);
		}
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$alias = $query->repository()->alias();
		if ($alias != 'Users') {
			$options['auto_contain'] = false;
			
			$query->matching('Users');

			$sortList = ['Users.openemis_no', 'Users.first_name'];
			if (array_key_exists('sortWhitelist', $options)) {
				$sortList = array_merge($options['sortWhitelist'], $sortList);
			}
			$options['sortWhitelist'] = $sortList;
		}
	}

	public function onGetOpenemisNo(Event $event, Entity $entity) {
		$value = '';
		if ($entity->has('user')) {
			$value = $entity->user->openemis_no;
		} else if ($entity->has('_matchingData')) {
			$value = $entity->_matchingData['Users']->openemis_no;
		}
		return $value;
	}

	public function onGetIdentity(Event $event, Entity $entity) {
		return $entity->_matchingData['Users']->default_identity_type;
	}

	public function onGetName(Event $event, Entity $entity) {
		$value = '';
		if ($entity->has('_matchingData')) {
			$value = $entity->_matchingData['Users']->name;
		}
		return $value;
	}

	public function onGetPhotoContent(Event $event, Entity $entity) {
		$fileContent = null;
		if ($entity->has('user')) {
			$fileContent = $entity->user->photo_content;
		} else if ($entity->has('_matchingData')) {
			$fileContent = $entity->_matchingData['Users']->photo_content;
		} else {
			if ($this->_table->table() == 'security_users') {
				$fileContent = $entity->photo_content;
			}
		}
		
		$value = "";
		if (empty($fileContent) && is_null($fileContent)) {
			if ($this->_table->alias() == 'Students') {
				$value = $this->defaultStudentProfileIndex;
			} else if ($this->_table->alias() == 'Staff') {
				$value = $this->defaultStaffProfileIndex;
			} else if ($this->_table->alias() == 'Guardians') {
				$value = $this->defaultGuardianProfileIndex;
			}
			// if (($this->hasBehavior('Student')) && ($this->action == "index")) {
			// 	$value = $this->defaultStudentProfileIndex;
			// }
			//  else if(($this->hasBehavior('Staff')) && ($this->action == "index")){
			// 	$value = $this->defaultStaffProfileIndex;
			// } else if(($this->hasBehavior('Guardian')) && ($this->action == "index")){
			// 	$value = $this->defaultGuardianProfileIndex;
			// } else if(($this->hasBehavior('User')) && ($this->action == "index")){
			// 	$value = $this->defaultUserProfileIndex;
			// } else if(($this->hasBehavior('Student')) && ($this->action == "view")){
			// 	$value = $this->defaultStudentProfileView;
			// } else if(($this->hasBehavior('Staff')) && ($this->action == "view")){
			// 	$value = $this->defaultStaffProfileView;
			// } else if(($this->hasBehavior('Guardian')) && ($this->action == "view")){
			// 	$value = $this->defaultGuardianProfileView;
			// } else if(($this->hasBehavior('User')) && ($this->action == "view")){
			// 	$value = $this->defaultUserProfileView;
			// }
		} else {
			$value = base64_encode(stream_get_contents($fileContent));
		}
		return $value;
	}

	public function getDefaultImgMsg() {
		return $this->defaultImgMsg;
	}

	public function getDefaultImgIndexClass() {
		return $this->defaultImgIndexClass;
	}

	public function getDefaultImgViewClass() {
		return $this->defaultImgViewClass;
	}

	public function getDefaultImgView() {
		$value = '';
		if ($this->_table->alias() == 'Students') {
			$value = $this->defaultStudentProfileView;
		} else if ($this->_table->alias() == 'Staff') {
			$value = $this->defaultStaffProfileView;
		} else if ($this->_table->alias() == 'Guardians') {
			$value = $this->defaultGuardianProfileView;
		}
		return $value;
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
		if ($field == 'identity') {
			$IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
			$identity = $IdentityType
							   ->find()
							   ->contain(['FieldOptions'])
							   ->where(['FieldOptions.code' => 'IdentityTypes'])
							   ->order(['IdentityTypes.default DESC'])
							   ->first();

			if ($identity) {
				$value = $identity->name;
			}

			return !empty($value) ? $value : $this->_table->onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		} else {
			return $this->_table->onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		}
	}

	public function getUniqueOpenemisId($options = []) {
		$prefix = '';
		
		if (array_key_exists('model', $options)) {
			switch ($options['model']) {
				case 'Student': case 'Staff': case 'Guardian':
					$prefix = TableRegistry::get('ConfigItems')->value(strtolower($options['model']).'_prefix');
					$prefix = explode(",", $prefix);
					$prefix = ($prefix[1] > 0)? $prefix[0]: '';
					break;
			}
		}

		$latest = $this->_table->find()
			->order($this->_table->aliasField('id').' DESC')
			->first();

		
		$latestOpenemisNo = $latest->openemis_no;
		$latestOpenemisNo = 0;
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
}
