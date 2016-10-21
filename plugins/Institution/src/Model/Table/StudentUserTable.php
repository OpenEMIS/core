<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Network\Session;
use App\Model\Table\ControllerActionTable;

class StudentUserTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		// Associations
		self::handleAssociations($this);

		// Behaviors
		$this->addBehavior('User.User');

		$this->addBehavior('CustomField.Record', [
			'model' => 'Student.Students',
			'behavior' => 'Student',
			'fieldKey' => 'student_custom_field_id',
			'tableColumnKey' => 'student_custom_table_column_id',
			'tableRowKey' => 'student_custom_table_row_id',
			'fieldClass' => ['className' => 'StudentCustomField.StudentCustomFields'],
			'formKey' => 'student_custom_form_id',
			'filterKey' => 'student_custom_filter_id',
			'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
			'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
			'recordKey' => 'student_id',
			'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);

		$this->addBehavior('Excel', [
			'excludes' => ['photo_name', 'is_student', 'is_staff', 'is_guardian', 'super_admin', 'date_of_death'],
			'filename' => 'Students',
			'pages' => ['view']
		]);

		$this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Student.Students.id']);
		$this->hasMany('InstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'student_id']);
		$this->addBehavior('Restful.RestfulAccessControl', [
        	'Students' => ['index', 'add']
        ]);

		$this->toggle('index', false);
        $this->toggle('remove', false);
	}

	public static function handleAssociations($model)
	{
		$model->belongsTo('Genders', 		 ['className' => 'User.Genders']);
		$model->belongsTo('AddressAreas', 	 ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$model->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$model->hasMany('Identities', 		['className' => 'User.Identities',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('Nationalities', 	['className' => 'User.UserNationalities',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('SpecialNeeds', 	['className' => 'User.SpecialNeeds',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('Contacts', 		['className' => 'User.Contacts',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('Attachments', 		['className' => 'User.Attachments',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('BankAccounts', 	['className' => 'User.BankAccounts',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('Comments', 		['className' => 'User.Comments',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('Languages', 		['className' => 'User.UserLanguages',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('Awards', 			['className' => 'User.Awards',			'foreignKey' => 'security_user_id', 'dependent' => true]);

		$model->belongsToMany('SecurityRoles', [
			'className' => 'Security.SecurityRoles',
			'foreignKey' => 'security_role_id',
			'targetForeignKey' => 'security_user_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);

		$model->hasMany('ClassStudents', [
			'className' => 'Institution.InstitutionClassStudents',
			'foreignKey' => 'student_id'
		]);

		// remove all student records from institution_students, institution_site_student_absences, student_behaviours, assessment_item_results, student_guardians, institution_student_admission, student_custom_field_values, student_custom_table_cells, student_fees, student_extracurriculars


		$model->belongsToMany('Institutions', [
			'className' => 'Institution.Institutions',
			'joinTable' => 'institution_students',
			'foreignKey' => 'student_id',
			'targetForeignKey' => 'institution_id',
			'through' => 'Institution.Students',
			'dependent' => true
		]);

        $model->hasMany('InstitutionStudents', ['className' => 'Institution.Students',    'foreignKey' => 'student_id', 'dependent' => true]);
		$model->hasMany('StudentAbsences', ['className' => 'Institution.InstitutionSiteStudentAbsences',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('StudentBehaviours', ['className' => 'Institution.StudentBehaviours',	'foreignKey' => 'student_id', 'dependent' => true]);
		$model->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults',	'foreignKey' => 'student_id', 'dependent' => true]);
		$model->belongsToMany('Guardians', [
			'className' => 'Student.Guardians',
			'foreignKey' => 'student_id',
			'targetForeignKey' => 'guardian_id',
			'through' => 'Student.StudentGuardians',
			'dependent' => true
		]);
		$model->hasMany('StudentAdmission', ['className' => 'Institution.StudentAdmission',	'foreignKey' => 'student_id', 'dependent' => true]);
		$model->hasMany('StudentCustomFieldValues', ['className' => 'CustomField.StudentCustomFieldValues',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('StudentCustomTableCells', ['className' => 'CustomField.StudentCustomTableCells',	'foreignKey' => 'security_user_id', 'dependent' => true]);
		$model->hasMany('StudentFees', ['className' => 'Institution.StudentFeesAbstract',	'foreignKey' => 'student_id', 'dependent' => true]);
		$model->hasMany('Extracurriculars', ['className' => 'Student.Extracurriculars',	'foreignKey' => 'security_user_id', 'dependent' => true]);
	}

	public function implementedEvents()
	{
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);
		$BaseUsers = TableRegistry::get('User.Users');
		$validator = $BaseUsers->setUserValidation($validator, $this);
		$validator
			->add('date_of_birth', 'ruleCheckAdmissionAgeWithEducationCycleGrade', [
				'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
				'on' => 'create'
			])
			->add('education_grade_id', [
			])
			->add('academic_period_id', [
			])
			->allowEmpty('postal_code')
			->add('postal_code', 'ruleCustomPostalCode', [
        		'rule' => ['validateCustomPattern', 'postal_code'],
        		'provider' => 'table',
        		'last' => true
		    ])
			;
		return $validator;
	}

	public function beforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('username', ['visible' => false]);
		$toolbarButtons = $extra['toolbarButtons'];
		$action = $this->action;
		if ($action == 'add') {
			$backAction = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Students', 'add'];
			$toolbarButtons['back']['url'] = $backAction;
			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		}

		// this value comes from the list page from StudentsTable->onUpdateActionButtons
		$institutionStudentId = $this->request->query('id');
		if (empty($institutionStudentId)) { // if value is empty, redirect back to the list page
			$event->stopPropagation();
			return $this->controller->redirect(['action' => 'Students', 'index']);
		} else {
			$extra['institutionStudentId'] = $institutionStudentId;
		}
	}

	public function afterAction(Event $event, ArrayObject $extra)
	{
		$entity = $extra['entity'];
		if (!is_null($entity)) {
			$StudentTable = TableRegistry::get('Institution.Students');
			$studentEntity = $StudentTable->get($extra['institutionStudentId']);

			$userId = $this->Auth->user('id');
			$studentId = $studentEntity->student_id;

			$isStudentEnrolled = $StudentTable->checkEnrolledInInstitution($studentId, $studentEntity->institution_id); // PHPOE-1897
			$isAllowedByClass = $this->checkClassPermission($studentId, $userId); // POCOR-3010
			if (!$isStudentEnrolled || $isAllowedByClass) {
				$this->toggle('edit', false);
			}
		}
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
	{
		if (!$this->AccessControl->isAdmin()) {
			$institutionIds = $this->AccessControl->getInstitutionsByUser();
			$this->Session->write('AccessControl.Institutions.ids', $institutionIds);
		}
		$this->Session->write('Student.Students.id', $entity->id);
		$this->Session->write('Student.Students.name', $entity->name);
		$this->setupTabElements($entity);
		$this->setupToolbarButtons($entity, $extra);
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
	{
		$this->Session->write('Student.Students.id', $entity->id);
		$this->Session->write('Student.Students.name', $entity->name);
		$this->setupTabElements($entity);

		// POCOR-3010
		$userId = $this->Auth->user('id');
		if (!$this->checkClassPermission($entity->id, $userId)) {
			$this->Alert->error('security.noAccess');
			$event->stopPropagation();
			$url = $this->url('view');
			return $this->controller->redirect($url);
		}
		// End POCOR-3010

		$this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
	}

	private function setupToolbarButtons(Entity $entity, ArrayObject $extra)
	{
		$toolbarButtons = $extra['toolbarButtons'];
		$toolbarButtons['back']['url']['action'] = 'Students';

		// Export execute permission.
		if (!$this->AccessControl->check(['Institutions', 'StudentUser', 'excel'])) {
			if (isset($toolbarButtons['export'])) {
				unset($toolbarButtons['export']);
			}
		}

		$this->addPromoteButton($entity, $extra);
		$this->addTransferButton($entity, $extra);
		$this->addDropoutButton($entity, $extra);
	}

	private function setupTabElements($entity)
	{
		$id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;

		$options = [
			'userRole' => 'Student',
			'action' => $this->action,
			'id' => $id,
			'userId' => $entity->id
		];

		$tabElements = $this->controller->getUserTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

    private function addTransferButton(Entity $entity, ArrayObject $extra)
    {
    	if ($this->AccessControl->check([$this->controller->name, 'TransferRequests', 'add'])) {
    		$session = $this->Session;
    		$toolbarButtons = $extra['toolbarButtons'];

    		$StudentsTable = TableRegistry::get('Institution.Students');
    		$TransferRequests = TableRegistry::get('Institution.TransferRequests');

    		$institutionStudentId = $extra['institutionStudentId'];
			$studentEntity = $StudentsTable->get($institutionStudentId);

			$institutionId = $studentEntity->institution_id;
			$studentId = $studentEntity->student_id;
			$session->write($TransferRequests->registryAlias().'.id', $institutionStudentId);
			$checkIfCanTransfer = $StudentsTable->checkIfCanTransfer($studentEntity, $institutionId);

			if ($checkIfCanTransfer) {
				$transferButton = $toolbarButtons['back'];
				$transferButton['type'] = 'button';
				$transferButton['label'] = '<i class="fa kd-transfer"></i>';
				$transferButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
				$transferButton['attr']['title'] = __('Transfer');
				$transferButton['url']['action'] = 'TransferRequests';
				$transferButton['url'][0] = 'add';

				// check if there is an existing transfer request
				$transferRequest = $TransferRequests
					->find()
					->where([
						$TransferRequests->aliasField('previous_institution_id') => $institutionId,
						$TransferRequests->aliasField('student_id') => $studentId,
						$TransferRequests->aliasField('status') => 0
					])
					->first();

				if (!empty($transferRequest)) {
					$transferButton['url'][0] = 'view';
					$transferButton['url'][1] = $transferRequest->id;
				}

				$toolbarButtons['transfer'] = $transferButton;
			}
    	}
    }

    private function addPromoteButton(Entity $entity, ArrayObject $extra)
    {
		if ($this->AccessControl->check([$this->controller->name, 'Promotion', 'add'])) {
			$session = $this->Session;
    		$toolbarButtons = $extra['toolbarButtons'];

    		$StudentsTable = TableRegistry::get('Institution.Students');
			$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
			$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$editableAcademicPeriods = $AcademicPeriods->getYearList(['isEditable' => true]);

			$Enrolled = $StudentStatuses->getIdByCode('CURRENT');
			$institutionStudentId = $extra['institutionStudentId'];
			$studentEntity = $StudentsTable->get($institutionStudentId);
			$academicPeriodId = $studentEntity->academic_period_id;

			// Show Promote button only if the Student Status is Current and academic period is editable
			if ($studentEntity->student_status_id == $Enrolled && array_key_exists($academicPeriodId, $editableAcademicPeriods)) {
				// Promote button
				$promoteButton = $toolbarButtons['back'];
				$promoteButton['type'] = 'button';
				$promoteButton['label'] = '<i class="fa kd-graduate"></i>';
				$promoteButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
				$promoteButton['attr']['title'] = __('Promotion');
				$promoteButton['url']['action'] = 'IndividualPromotion';
				$promoteButton['url'][0] = 'add';

				$toolbarButtons['promote'] = $promoteButton;
				//End

				// $session->write('Institution.IndividualPromotion.id', $institutionStudentId);
			}
		}
    }

    private function addDropoutButton(Entity $entity, ArrayObject $extra)
    {
    	if ($this->AccessControl->check([$this->controller->name, 'DropoutRequests', 'add'])) {
    		$session = $this->Session;
    		$toolbarButtons = $extra['toolbarButtons'];

    		$InstitutionStudentsTable = TableRegistry::get('Institution.Students');
    		$StudentsTable = TableRegistry::get('Institution.Students');
    		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');

    		$institutionStudentId = $extra['institutionStudentId'];
    		$studentEntity = $StudentsTable->get($institutionStudentId);
			$enrolledStatus = $StudentStatuses->getIdByCode('CURRENT');

			// Check if the student is enrolled
			if ($studentEntity->student_status_id == $enrolledStatus) {
				$DropoutRequests = TableRegistry::get('Institution.DropoutRequests');
				$session->write($DropoutRequests->registryAlias().'.id', $institutionStudentId);
				$NEW = 0;

				// check if there is an existing dropout request
				$dropoutRequest = $DropoutRequests->find()
					->select(['institution_student_dropout_id' => 'id'])
					->where([$DropoutRequests->aliasField('student_id') => $studentEntity->student_id,
							$DropoutRequests->aliasField('institution_id') => $studentEntity->institution_id,
							$DropoutRequests->aliasField('education_grade_id') => $studentEntity->education_grade_id,
							$DropoutRequests->aliasField('status') => $NEW
						])
					->first();

				$dropoutButton = $toolbarButtons['back'];
				$dropoutButton['type'] = 'button';
				$dropoutButton['label'] = '<i class="fa kd-dropout"></i>';
				$dropoutButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
				$dropoutButton['attr']['title'] = __('Dropout');
				$dropoutButton['url']['action'] = 'DropoutRequests';
				$dropoutButton['url'][0] = 'add';

				if (!empty($dropoutRequest)) {
					$dropoutButton['url'][0] = 'edit';
					$dropoutButton['url'][1] = $dropoutRequest->institution_student_dropout_id;
				}
				$toolbarButtons['dropout'] = $dropoutButton;
			}
		}
    }

	//to handle identity_number field that is automatically created by mandatory behaviour.
	public function onUpdateFieldIdentityNumber(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add') {
			$attr['fieldName'] = $this->alias().'.identities.0.number';
			$attr['attr']['label'] = __('Identity Number');
		}
		return $attr;
	}

	private function checkClassPermission($studentId, $userId)
	{
		$permission = false;
		if (!$this->AccessControl->isAdmin()) {
			$event = $this->controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
			$roles = [];
            if (is_array($event->result)) {
                $roles = $event->result;
            }
			if (!$this->AccessControl->check(['Institutions', 'AllClasses', $permission], $roles)) {
				$Class = TableRegistry::get('Institution.InstitutionClasses');
				$classStudentRecord = $Class
					->find('ByAccess', [
						'accessControl' => $this->AccessControl,
						'controller' => $this->controller,
						'userId' => $userId,
						'permission' => 'edit'
					])
					->innerJoinWith('ClassStudents')
					->where(['ClassStudents.student_id' => $studentId])
					->toArray();
				if (!empty($classStudentRecord)) {
					$permission = true;
				}
			} else {
				$permission = true;
			}

		} else {
			$permission = true;
		}
		return $permission;
	}

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        foreach ($fields as $key => $field) {
            //get the value from the table, but change the label to become default identity type.
            if ($field['field'] == 'identity_number') {
                $fields[$key] = [
                    'key' => 'StudentUser.identity_number',
                    'field' => 'identity_number',
                    'type' => 'string',
                    'label' => __($identity->name)
                ];
                break;
            }
        }
    }

	public function getAcademicTabElements($options = [])
	{
		$id = (array_key_exists('id', $options))? $options['id']: 0;

		$tabElements = [];
		$studentTabElements = [
			'Programmes' => ['text' => __('Programmes')],
			'Classes' => ['text' => __('Classes')],
			'Subjects' => ['text' => __('Subjects')],
			'Absences' => ['text' => __('Absences')],
			'Behaviours' => ['text' => __('Behaviours')],
			'Results' => ['text' => __('Results')],
			'Awards' => ['text' => __('Awards')],
			'Extracurriculars' => ['text' => __('Extracurriculars')],
		];

		$tabElements = array_merge($tabElements, $studentTabElements);

		// Programme will use institution controller, other will be still using student controller
		foreach ($studentTabElements as $key => $tab) {
            if ($key == 'Programmes') {
                $type = (array_key_exists('type', $options))? $options['type']: null;
        		$studentUrl = ['plugin' => 'Institution', 'controller' => 'Institutions'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>'Student'.$key, 'index', 'type' => $type]);
            } else {
				$studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>$key, 'index']);
            }
        }
		return $tabElements;
	}

	// needs to migrate
    public function findStudents(Query $query, array $options = []) {
        $query->where([$this->aliasField('super_admin').' <> ' => 1]);

        $limit = (array_key_exists('limit', $options))? $options['limit']: null;
        $page = (array_key_exists('page', $options))? $options['page']: null;

        // conditions
        $firstName = (array_key_exists('first_name', $options))? $options['first_name']: null;
        $lastName = (array_key_exists('last_name', $options))? $options['last_name']: null;
        $openemisNo = (array_key_exists('openemis_no', $options))? $options['openemis_no']: null;
        $identityNumber = (array_key_exists('identity_number', $options))? $options['identity_number']: null;
        $dateOfBirth = (array_key_exists('date_of_birth', $options))? $options['date_of_birth']: null;

        if (is_null($firstName) && is_null($lastName) && is_null($openemisNo) && is_null($identityNumber) && is_null($dateOfBirth)) {
        	return $query->where(['1 = 0']);
        }

        $conditions = [];
        if (!empty($firstName)) $conditions['first_name LIKE'] = $firstName . '%';
        if (!empty($lastName)) $conditions['last_name LIKE'] = $lastName . '%';
        if (!empty($openemisNo)) $conditions['openemis_no LIKE'] = $openemisNo . '%';
        if (!empty($dateOfBirth)) $conditions['date_of_birth'] = $dateOfBirth;

        $identityConditions = [];
        if (!empty($identityNumber)) $identityConditions['Identities.number LIKE'] = $identityNumber . '%';

        $identityJoinType = (empty($identityNumber))? 'LEFT': 'INNER';
        $default_identity_type = $this->Identities->IdentityTypes->getDefaultValue();
        $query->join([
            [
                'type' => $identityJoinType,
                'table' => 'user_identities',
                'alias' => 'Identities',
                'conditions' => array_merge([
                        'Identities.security_user_id = ' . $this->aliasField('id'),
                        'Identities.identity_type_id' => $default_identity_type
                    ], $identityConditions)
            ]
        ]);

        $query->group([$this->aliasField('id')]);

        if (!empty($conditions)) $query->where($conditions);
        if (!is_null($limit)) $query->limit($limit);
        if (!is_null($page)) $query->page($page);

        return $query;
    }

    // needs to migrate
    public function findEnrolledInstitutionStudents(Query $query, array $options = []) {
    	$query->contain([
    		'InstitutionStudents' => function($q) {
    			return $q->where(['InstitutionStudents.student_status_id' => 1]);
    		},
    		'InstitutionStudents.Institutions'
    	]);
    	return $query;
    }
}
