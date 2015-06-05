<?php
namespace Student\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

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
		$this->ControllerAction->setFieldOrder('status', $order++);

		// $this->ControllerAction->setFieldOrder('UserContact', $order++);
		// $this->ControllerAction->setFieldOrder('SecurityGroupUser', $order++);
		$this->ControllerAction->setFieldOrder('modified_user_id', $order++);
		$this->ControllerAction->setFieldOrder('modified', $order++);
		$this->ControllerAction->setFieldOrder('created_user_id', $order++);
		$this->ControllerAction->setFieldOrder('created', $order++);

		$this->Users->fields['status']['type'] = 'select';
		$this->Users->fields['status']['options'] = $this->Users->getStatus();

		

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
		$this->Users->fields['openemis_no']['attr']['readonly'] = true;
		$this->Users->fields['openemis_no']['attr']['value'] = $this->Users->getUniqueOpenemisId(['model'=>'Student']);
		$this->ControllerAction->add($id);
		$this->ControllerAction->render();
	}

	public function edit($id = null) {
		if (is_null($id)) {
			$id = $this->ControllerAction->Session->read('Student.security_user_id');
		} else {
			$this->ControllerAction->Session->write('Student.security_user_id', $id);
		}
		$this->ControllerAction->edit($id);
		$this->ControllerAction->render();
	}



}
