<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;
use Security\Model\Table\SecurityUserTypesTable as UserTypes;

class StudentsTable extends AppTable {
	// used for type = image
	private $defaultImgIndexClass = "profile-image-thumbnail";
	private $defaultImgViewClass= "profile-image";
	private $defaultImgMsg = "<p>* Advisable photo dimension 90 by 115px<br>* Format Supported: .jpg, .jpeg, .png, .gif </p>";
	private $defaultStudentProfileView = "<div class='profile-image'><i class='kd-students'></i></div>";

	public $InstitutionStudent;

	public function initialize(array $config) {
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		// $this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStudents]);
		$this->addBehavior('AdvanceSearch');

		$this->InstitutionStudent = TableRegistry::get('Institution.Students');
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	// $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	// public function beforeAction(Event $event) {

	// }

	public function viewAfterAction(Event $event, Entity $entity) {
		// to set the student name in headers
		$this->request->session()->write('Students.name', $entity->name);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$settings['model'] = 'Security.SecurityUserTypes';
		$this->fields = []; // unset all fields first

		$this->ControllerAction->field('institution', ['order' => 50]);
		$this->ControllerAction->field('status', ['order' => 51]);
	}

	public function onGetInstitution(Event $event, Entity $entity) {
		$userId = $entity->security_user_id;
		$query = $this->InstitutionStudent->find()
		->contain(['Institutions', 'StudentStatuses'])
		->where([$this->InstitutionStudent->aliasField('student_id') => $userId])
		->order([$this->InstitutionStudent->aliasField('start_date') => 'DESC'])
		;

		$value = '';
		if ($query->count() > 0) {
			$obj = $query->first();
			$value = $obj->institution->name;
			$entity->status = $obj->student_status->name;
		}
		return $value;
	}

	public function onGetStatus(Event $event, Entity $entity) {
		$value = '';
		if ($entity->has('status')) {
			$value = $entity->status;
		}
		return $value;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain(['Users']);
		$query->where(['SecurityUserTypes.user_type' => UserTypes::STUDENT]);

		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}
	}

	public function addBeforeAction(Event $event) {
		$openemisNo = $this->getUniqueOpenemisId(['model' => Inflector::singularize('Student')]);
		$this->ControllerAction->field('openemis_no', [
			'type' => 'readonly', 
			'attr' => ['value' => $openemisNo],
			'value' => $openemisNo,
			'order' => 1
		]);

		$this->ControllerAction->field('username', ['order' => 100]);
		$this->ControllerAction->field('password', ['order' => 101, 'visible' => true]);
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$userTypes = TableRegistry::get('Security.SecurityUserTypes');
		$affectedRows = $userTypes->deleteAll([
			'security_user_id' => $entity->id,
			'user_type' => UserTypes::STUDENT
		]);
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
		return $this->defaultStudentProfileView;;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		foreach (['view', 'edit'] as $action) {
			if (array_key_exists($action, $buttons)) {
				$buttons[$action]['url'][1] = $entity->security_user_id;
			}
		}
		if (array_key_exists('remove', $buttons)) {
			$buttons['remove']['attr']['field-value'] = $entity->security_user_id;
		}
		return $buttons;
	}
}
