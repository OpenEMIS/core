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
		
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		return $events;
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->_table->ControllerAction->field('photo_content', ['type' => 'image', 'order' => 0]);
		$this->_table->ControllerAction->field('openemis_no', ['order' => 1]);
		$this->_table->ControllerAction->field('identity', ['order' => 2]);
	}

	public function onGetOpenemisNo(Event $event, Entity $entity) {
		$value = '';
		if ($entity->has('user') && !empty($entity->user)) {
			$value = $entity->user->openemis_no;
		}
		return $value;
	}

	public function onGetIdentity(Event $event, Entity $entity) {
		$value = '';
		if ($entity->has('user') && !empty($entity->user)) {
			$value = $entity->user->default_identity_type;
		}
		return $value;
	}

	public function onGetPhotoContent(Event $event, Entity $entity) {
		if ($entity->has('user') && !empty($entity->user)) {
			$fileContent = $entity->user->photo_content;
		}
		
		$value = "";
		if (empty($fileContent) && is_null($fileContent)) {
			if ($this->_table->alias() == 'Students') {
				$value = $this->defaultStudentProfileIndex;
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
}
