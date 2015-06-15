<?php
namespace Staff\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

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
			'Attachments' => ['className' => 'User.UserAttachments'],
			'Qualifications' => ['className' => 'Staff.Qualifications'],
			'Positions' => ['className' => 'Staff.Positions'],
			'Sections' => ['className' => 'Staff.StaffSections'],
			'Classes' => ['className' => 'Staff.StaffClasses'],
			'Absences' => ['className' => 'Staff.Absences'],
			'Leaves' => ['className' => 'Staff.Leaves'],
			'Behaviours' => ['className' => 'Staff.StaffBehaviours'],
			'Extracurriculars' => ['className' => 'Staff.Extracurriculars'],
			'Employments' => ['className' => 'Staff.Employments'],
			'Salaries' => ['className' => 'Staff.Salaries'],
			'Memberships' => ['className' => 'Staff.Memberships'],
			'Licenses' => ['className' => 'Staff.Licenses'],
			'BankAccounts' => ['className' => 'User.UserBankAccounts'],
		];

		$this->set('contentHeader', 'Staff');
    }

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Controller.onInitialize'] = 'onInitialize';
		$events['ControllerAction.Controller.beforePaginate'] = 'beforePaginate';
		return $events;
	}

	public function onInitialize($event, $model) {
		$session = $this->request->session();
		$header = __('Staff');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb($model->getHeader($model->alias), ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => $model->alias]);

		if (array_key_exists('security_user_id', $model->fields)) {
			if (!$session->check('Staff.security_user_id')) {
				$this->Alert->warning('general.notExists');
				$this->redirect(['action' => 'index']);
			}
			$model->fields['security_user_id']['type'] = 'hidden';
			$model->fields['security_user_id']['value'] = $session->read('Staff.security_user_id');
		}
		
		$this->set('contentHeader', $header);
	}

	public function beforePaginate($event, $model, $options) {
		$session = $this->request->session();

		if (in_array($model->alias, array_keys($this->ControllerAction->models))) {
				if ($this->ControllerAction->Session->check('Staff.security_user_id')) {
					$securityUserId = $this->ControllerAction->Session->read('Staff.security_user_id');
					if (!array_key_exists('conditions', $options)) {
						$options['conditions'] = [];
					}
					$options['conditions'][] = [$model->alias().'.security_user_id = ' => $securityUserId];
					pr($options['conditions']);
				} else {
					$this->Alert->warning('general.noData');
					$this->redirect(['action' => 'index']);
					return false;
				}
			}
			return $options;
	}
}
