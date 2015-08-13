<?php
namespace Guardian\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Security\Model\Table\SecurityUserTypesTable as UserTypes;

class GuardiansTable extends AppTable {
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
		$this->addBehavior('AdvanceSearch');

		$this->belongsToMany('Students', [
			'className' => 'Student.Students',
			'joinTable' => 'student_guardians',
			'foreignKey' => 'guardian_id',
			'targetForeignKey' => 'student_id',
			// 'through' => '',
			'dependent' => true
		]);

		// $this->addBehavior('Excel', [
		// 	'excludes' => ['password', 'photo_name'],
		// 	'filename' => 'Guardians'
		// ]);
		// $this->addBehavior('TrackActivity', ['target' => 'Student.StudentActivities', 'key' => 'security_user_id', 'session' => 'Users.id']);

		// $this->InstitutionStudent = TableRegistry::get('Institution.Students');
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	// $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function viewAfterAction(Event $event, Entity $entity) {
		// to set the student name in headers
		$this->request->session()->write('Guardians.name', $entity->name);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$settings['model'] = 'Security.SecurityUserTypes';
		$this->fields = []; // unset all fields first
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->where(['SecurityUserTypes.user_type' => UserTypes::GUARDIAN]);
		$query->group(['SecurityUserTypes.security_user_id']);
		
		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$UserTypes = TableRegistry::get('Security.SecurityUserTypes');

        if ($entity->isNew()) {
			$obj = $UserTypes->newEntity(['security_user_id' => $entity->id, 'user_type' => UserTypes::GUARDIAN]);
			$UserTypes = $UserTypes->save($obj);
        }
	}
	
	// Logic for the mini dashboard
	public function afterAction(Event $event) {
		
	}

	public function addBeforeAction(Event $event) {
		$openemisNo = $this->getUniqueOpenemisId(['model' => Inflector::singularize('Guardian')]);
		$this->ControllerAction->field('openemis_no', [ 
			'attr' => ['value' => $openemisNo],
			'value' => $openemisNo
		]);

		$this->ControllerAction->field('username', ['order' => 70]);
		$this->ControllerAction->field('password', ['order' => 71, 'visible' => true]);
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$userTypes = TableRegistry::get('Security.SecurityUserTypes');
		$affectedRows = $userTypes->deleteAll([
			'security_user_id' => $entity->id,
			'user_type' => UserTypes::GUARDIAN
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->add('first_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					],
					'ruleNotBlank' => [
						'rule' => 'notBlank',
					]
				])
			->add('last_name', [
					'ruleCheckIfStringGotNoNumber' => [
						'rule' => 'checkIfStringGotNoNumber',
					]
				])
			->add('openemis_no', [
					'ruleUnique' => [
						'rule' => 'validateUnique',
						'provider' => 'table',
					]
				])
			->add('username', [
				'ruleUnique' => [
					'rule' => 'validateUnique',
					'provider' => 'table',
				],
				'ruleAlphanumeric' => [
				    'rule' => 'alphanumeric',
				]
			])
			->allowEmpty('username')
			->allowEmpty('password')
			->allowEmpty('photo_content')
			;

		$this->setValidationCode('first_name.ruleCheckIfStringGotNoNumber', 'User.Users');
		$this->setValidationCode('first_name.ruleNotBlank', 'User.Users');
		$this->setValidationCode('last_name.ruleCheckIfStringGotNoNumber', 'User.Users');
		$this->setValidationCode('openemis_no.ruleUnique', 'User.Users');
		$this->setValidationCode('username.ruleUnique', 'User.Users');
		$this->setValidationCode('username.ruleAlphanumeric', 'User.Users');
		return $validator;
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
