<?php
namespace Staff\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class StaffController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('User.Users');
		$this->ControllerAction->model()->addBehavior('Staff.Staff');

		$this->ControllerAction->models = [
			'Contacts' => ['className' => 'User.UserContacts'],
			'Identities' => ['className' => 'User.UserIdentities'],
			'Languages' => ['className' => 'User.UserLanguages'],
			'Comments' => ['className' => 'User.UserComments'],
			'SpecialNeeds' => ['className' => 'User.UserSpecialNeeds'],
			'Awards' => ['className' => 'User.UserAwards'],
			'Attachments' => ['className' => 'User.UserAttachments']
		];

		$this->set('contentHeader', 'Staff');
    }

    // temp method until institution site role is using security user id
    public $modelsThatUseSecurityUserId = [
			'Contacts',
			'Identities',
			'Languages',
			'Comments',
			'SpecialNeeds',
			'Awards',
			'Attachments'
		];

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.onInitialize'] = 'onInitialize';
		$events['ControllerAction.beforePaginate'] = 'beforePaginate';
		return $events;
	}

	public function onInitialize($event, $model) {
		$session = $this->request->session();
		$header = __('Staff');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb($model->getHeader($model->alias), ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => $model->alias]);

		if (array_key_exists('security_user_id', $model->fields)) {
			if (!$session->check('Staff.security_user_id')) {
				$this->Message->alert('general.notExists');
			}
			$model->fields['security_user_id']['type'] = 'hidden';
			$model->fields['security_user_id']['value'] = $session->read('Staff.security_user_id');
		}
		
		$this->set('contentHeader', $header);
	}

	public function beforePaginate($event, $model, $options) {
		$session = $this->request->session();

		if (in_array($model->alias, $this->modelsThatUseSecurityUserId)) {
				if ($this->ControllerAction->Session->check('Staff.security_user_id')) {
					$securityUserId = $this->ControllerAction->Session->read('Staff.security_user_id');
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
			$id = $this->ControllerAction->Session->read('Staff.security_user_id');
		} else {
			$this->ControllerAction->Session->write('Staff.security_user_id', $id);
		}
		$this->ControllerAction->view($id);
		$this->ControllerAction->render();
	}

	public function edit($id = null) {
		if (is_null($id)) {
			$id = $this->ControllerAction->Session->read('Staff.security_user_id');
		}
		$this->ControllerAction->edit($id);
		$this->ControllerAction->render();
	}



}
