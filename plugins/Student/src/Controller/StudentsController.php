<?php
namespace Student\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class StudentsController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('SecurityUsers');
		$this->ControllerAction->model()->addBehavior('Student.Student');

		$this->ControllerAction->models = [
			'Contacts' => ['className' => 'UserContacts'],
			'Identities' => ['className' => 'UserIdentities'],
			'Languages' => ['className' => 'UserLanguages'],
			'Comments' => ['className' => 'UserComments'],
			'SpecialNeeds' => ['className' => 'UserSpecialNeeds'],
			'Awards' => ['className' => 'UserAwards'],
			'Attachments' => ['className' => 'Student.StudentAttachments'],
			'Programmes' => ['className' => 'Student.Programmes'],
			'Sections' => ['className' => 'Student.StudentSections'],
			'Absences' => ['className' => 'Student.Absences'],
			'Behaviours' => ['className' => 'Student.StudentBehaviours'],
			'Extracurriculars' => ['className' => 'Student.StudentExtracurriculars'],
			'BankAccounts' => ['className' => 'Student.StudentBankAccounts'],
			'StudentFees' => ['className' => 'Student.StudentFees'],
		];

		$this->set('contentHeader', 'Students');
    }

    // temp method until institution site role is using security user id
    public $modelsThatUseSecurityUserId = [
			'Contacts',
			'Identities',
			'Languages',
			'Comments',
			'SpecialNeeds',
			'Awards'
		];
	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->ControllerAction->beforePaginate = function($model, $options) {
			// if (in_array($model->alias, array_keys($this->ControllerAction->models))) {
			if (in_array($model->alias, $this->modelsThatUseSecurityUserId)) {
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
		};

		$visibility = ['view' => true, 'edit' => true];

		$header = __('Student');
		$controller = $this;

		$this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
			$header .= ' - ' . $model->alias;
			$session = $this->request->session();

			$model->fields['security_user_id']['type'] = 'hidden';
			$model->fields['security_user_id']['value'] = $this->ControllerAction->Session->read('Student.security_user_id');

			$controller->set('contentHeader', $header);
		};

		$this->SecurityUsers->fields['photo_content']['type'] = 'image';

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

	public function edit($id = null) {
		if (is_null($id)) {
			$id = $this->ControllerAction->Session->read('Student.security_user_id');
		}
		$this->ControllerAction->edit($id);
		$this->ControllerAction->render();
	}



}
