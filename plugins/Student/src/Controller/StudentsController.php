<?php
namespace Student\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class StudentsController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('User.Users');
		$this->ControllerAction->model()->addBehavior('Student.Student');

		$this->ControllerAction->models = [
			'Contacts' => ['className' => 'User.UserContacts'],
			'Identities' => ['className' => 'User.UserIdentities'],
			'Languages' => ['className' => 'User.UserLanguages'],
			'Comments' => ['className' => 'User.UserComments'],
			'SpecialNeeds' => ['className' => 'User.UserSpecialNeeds'],
			'Awards' => ['className' => 'User.UserAwards'],
			'Attachments' => ['className' => 'User.UserAttachments'],
			'Programmes' => ['className' => 'Student.Programmes'],
			'Sections' => ['className' => 'Student.StudentSections'],
			'Classes' => ['className' => 'Student.StudentClasses'],
			'Absences' => ['className' => 'Student.Absences'],
			'Behaviours' => ['className' => 'Student.StudentBehaviours'],
			'Results' => ['className' => 'Student.StudentAssessments'],
			'Extracurriculars' => ['className' => 'Student.Extracurriculars'],
			'BankAccounts' => ['className' => 'User.UserBankAccounts'],
			'StudentFees' => ['className' => 'Student.StudentFees'],
		];

		$this->set('contentHeader', 'Students');
    }

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Controller.onInitialize'] = 'onInitialize';
		$events['ControllerAction.Controller.beforePaginate'] = 'beforePaginate';
		return $events;
	}

	public function onInitialize($event, $model) {
		$session = $this->request->session();
		$header = __('Student');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb($model->getHeader($model->alias), ['plugin' => 'Student', 'controller' => 'Students', 'action' => $model->alias]);

		if (array_key_exists('security_user_id', $model->fields)) {
			if (!$session->check('Student.security_user_id')) {
				$this->Message->alert('general.notExists');
			}
			$model->fields['security_user_id']['type'] = 'hidden';
			$model->fields['security_user_id']['value'] = $session->read('Student.security_user_id');
		}
		
		$this->set('contentHeader', $header);
	}

	public function beforePaginate($event, $model, $options) {
		$session = $this->request->session();

		if (in_array($model->alias, array_keys($this->ControllerAction->models))) {
				if ($this->ControllerAction->Session->check('Student.security_user_id')) {
					$securityUserId = $this->ControllerAction->Session->read('Student.security_user_id');
					if (!array_key_exists('conditions', $options)) {
						$options['conditions'] = [];
					}
					$options['conditions'][] = [$model->alias().'.security_user_id = ' => $securityUserId];
				} else {
					$this->ControllerAction->Message->alert('general.noData');
					$this->redirect(['action' => 'index']);
				}
			}
			return $options;
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		$visibility = ['view' => true, 'edit' => true];

		$this->Users->fields['photo_content']['type'] = 'image';
		

		$this->Users->fields['super_admin']['type'] = 'hidden';
		$this->Users->fields['status']['type'] = 'select';
		$this->Users->fields['status']['options'] = $this->Users->getStatus();
		$this->Users->fields['gender_id']['type'] = 'select';
		$this->Users->fields['gender_id']['options'] = $this->Users->Genders->find('list', ['keyField' => 'id', 'valueField' => 'name'])->toArray();

		if (in_array($this->request->action, ['add', 'edit'])) {
			// contact 'mandatory field'
			$contactOptions = TableRegistry::get('User.ContactTypes')
				->find('list', ['keyField' => 'id', 'valueField' => 'full_contact_type_name'])
				->find('withContactOptions')
				->toArray();
			$this->ControllerAction->addField('contact', [
				'type' => 'element', 
				'element' => 'selectAndTxt',
				'label' => __('Contact'),
				'selectOptions' => $contactOptions,
				'txtPlaceHolder' => __('Value'),
				'selectId' => 'Users.user_contacts.0.contact_type_id',
				'txtId' => 'Users.user_contacts.0.value',
			]);

			$nationalityOptions = TableRegistry::get('FieldOption.Countries')->find('list', ['keyField' => 'id', 'valueField' => 'name'])->toArray();
			$this->ControllerAction->addField('user_nationalities.0.country_id', [
				'type' => 'select', 
				'options' => $nationalityOptions, 
				'onChangeReload' => true
			]);

			// identity 'mandatory field'
			$identityTypeOptions = TableRegistry::get('FieldOption.IdentityTypes')->getOptions();
			$this->ControllerAction->addField('identity', [
				'type' => 'element', 
				'element' => 'selectAndTxt',
				'label' => __('Identity'),
				'selectOptions' => $identityTypeOptions,
				'txtPlaceHolder' => __('Identity Number'),
				'selectId' => 'Users.user_identities.0.identity_type_id',
				'txtId' => 'Users.user_identities.0.number',
			]);

			// special need 'mandatory field'
			$specialNeedOptions = TableRegistry::get('FieldOption.SpecialNeedTypes')->getOptions();
			$this->ControllerAction->addField('specialNeed', [
				'type' => 'element', 
				'element' => 'selectAndTxt',
				'label' => __('Special Need'),
				'selectOptions' => $specialNeedOptions,
				'txtPlaceHolder' => __('Special Need'),
				'selectId' => 'Users.user_special_needs.0.special_need_type_id',
				'txtId' => 'Users.user_special_needs.0.comment',
			]);
		}

		$order = 0;
		$this->ControllerAction->setFieldOrder('openemis_no', $order++);
		$this->ControllerAction->setFieldOrder('first_name', $order++);
		$this->ControllerAction->setFieldOrder('middle_name', $order++);
		$this->ControllerAction->setFieldOrder('third_name', $order++);
		$this->ControllerAction->setFieldOrder('last_name', $order++);
		$this->ControllerAction->setFieldOrder('preferred_name', $order++);
		$this->ControllerAction->setFieldOrder('address', $order++);
		$this->ControllerAction->setFieldOrder('postal_code', $order++);
		$this->ControllerAction->setFieldOrder('gender_id', $order++);
		$this->ControllerAction->setFieldOrder('date_of_birth', $order++);

		if (array_key_exists('contact', $this->Users->fields)) {
			$this->ControllerAction->setFieldOrder('contact', $order++);
		}
		if (array_key_exists('UserNationality.0.country_id', $this->Users->fields)) {
			$this->ControllerAction->setFieldOrder('UserNationality.0.country_id', $order++);
		}
		if (array_key_exists('identity', $this->Users->fields)) {
			$this->ControllerAction->setFieldOrder('identity', $order++);
		}
		if (array_key_exists('specialNeed', $this->Users->fields)) {
			$this->ControllerAction->setFieldOrder('specialNeed', $order++);
		}

		$this->ControllerAction->setFieldOrder('status', $order++);

		$this->ControllerAction->setFieldOrder('modified_user_id', $order++);
		$this->ControllerAction->setFieldOrder('modified', $order++);
		$this->ControllerAction->setFieldOrder('created_user_id', $order++);
		$this->ControllerAction->setFieldOrder('created', $order++);
		

		// $this->ControllerAction->addField('nationality', [
		// 	'type' => 'element', 
		// 	'order' => 5,
		// 	'element' => 'Institution.Programmes/grades'
		// ]);

		

		// unset($this->SecurityUsers->fields['photo_content']);

		
		// pr($this->ControllerAction->models);

		
		// $this->Institutions->fields['alternative_name']['visible'] = $visibility;
		// $this->Institutions->fields['address']['visible'] = $visibility;
		// $this->Institutions->fields['postal_code']['visible'] = $visibility;
		// $this->Institutions->fields['telephone']['visible'] = $visibility;
		// $this->Institutions->fields['fax']['visible'] = $visibility;
		// $this->Institutions->fields['email']['visible'] = $visibility;
		// $this->Institutions->fields['website']['visible'] = $visibility;
		// $this->Institutions->fields['date_opened']['visible'] = $visibility;
		// $this->Institutions->fields['year_opened']['visible'] = $visibility;
		// $this->Institutions->fields['date_closed']['visible'] = $visibility;
		// $this->Institutions->fields['year_closed']['visible'] = $visibility;
		// $this->Institutions->fields['longitude']['visible'] = $visibility;
		// $this->Institutions->fields['latitude']['visible'] = $visibility;
		// $this->Institutions->fields['security_group_id']['visible'] = $visibility;
		// $this->Institutions->fields['contact_person']['visible'] = $visibility;

		// // columns to be removed, used by ECE QA Dashboard
		// $this->Institutions->fields['institution_site_area_id']['visible'] = $visibility;
	}

	public function view($id = null) {
		if (is_null($id)) {
			$id = $this->ControllerAction->Session->read('Student.security_user_id');
		} else {
			$this->ControllerAction->Session->write('Student.security_user_id', $id);
		}
		$this->ControllerAction->view($id);
		$this->ControllerAction->render();
	}

	public function add($id = null) {
		if ($this->ControllerAction->Session->check('Institutions.id')) {
			$institutionId = $this->ControllerAction->Session->read('Institutions.id');
		} else {
			// todo-mlee need to put correct alert saying need to select institution first
			$action = $this->ControllerAction->buttons['index']['url'];
			return $this->redirect($action);
		}

		$this->ControllerAction->addField('institution_site_students.0.institution_site_id', [
			'type' => 'hidden', 
			'value' =>$institutionId
		]);
		
		$this->Users->fields['openemis_no']['attr']['readonly'] = true;
		$this->Users->fields['openemis_no']['attr']['value'] = $this->Users->getUniqueOpenemisId(['model'=>'Student']);
		
		$this->ControllerAction->add($id);
		// only set data if there is a post back

		// $this->Users->fields['contact']['data'] = [];
		$this->ControllerAction->render();
	}

	public function edit($id = null) {
		if (is_null($id)) {
			$id = $this->ControllerAction->Session->read('Student.security_user_id');
		} else {
			$this->ControllerAction->Session->write('Student.security_user_id', $id);
		}

		// $this->Users->fields['contact']['data'] = [];

		$this->ControllerAction->edit($id);
		$this->ControllerAction->render();
	}



}
