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
		$this->ControllerAction->model()->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		$this->ControllerAction->model()->addBehavior('CustomField.Record', [
			'behavior' => 'Staff',
			'recordKey' => 'security_user_id',
			'fieldValueKey' => ['className' => 'Staff.StaffCustomFieldValues', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellKey' => ['className' => 'Staff.StaffCustomTableCells', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);

		$this->ControllerAction->models = [
			'Accounts' => ['className' => 'User.Accounts', 'actions' => ['view', 'edit']],
			'Contacts' => ['className' => 'User.Contacts'],
			'Identities' => ['className' => 'User.Identities'],
			'Languages' => ['className' => 'User.UserLanguages'],
			'Comments' => ['className' => 'User.Comments'],
			'SpecialNeeds' => ['className' => 'User.SpecialNeeds'],
			'Awards' => ['className' => 'User.Awards'],
			'Attachments' => ['className' => 'User.Attachments'],
			'Qualifications' => ['className' => 'Staff.Qualifications'],
			'Positions' => ['className' => 'Staff.Positions', 'actions' => ['index']],
			'Sections' => ['className' => 'Staff.StaffSections', 'actions' => ['index']],
			'Classes' => ['className' => 'Staff.StaffClasses', 'actions' => ['index']],
			'Absences' => ['className' => 'Staff.Absences', 'actions' => ['index']],
			'Leaves' => ['className' => 'Staff.Leaves'],
			'Behaviours' => ['className' => 'Staff.StaffBehaviours', 'actions' => ['index']],
			'Extracurriculars' => ['className' => 'Staff.Extracurriculars'],
			'Employments' => ['className' => 'Staff.Employments'],
			'Salaries' => ['className' => 'Staff.Salaries'],
			'Memberships' => ['className' => 'Staff.Memberships'],
			'Licenses' => ['className' => 'Staff.Licenses'],
			'BankAccounts' => ['className' => 'User.BankAccounts']
		];

		$this->set('contentHeader', 'Staff');
	}

	public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$this->Navigation->addCrumb('Staff', ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'index']);
    	$session = $this->request->session();
		$action = $this->request->params['action'];

		if ($action == 'index') {
			$session->delete('Staff.security_user_id');
			$session->delete('Users.id');
		}
		if ($session->check('Staff.security_user_id') || $session->check('Users.id') || $action == 'view') {
			// $id = 0;
			if ($session->check('Staff.security_user_id')) {
				$id = $session->read('Staff.security_user_id');
			} else if ($session->check('Users.id')) {
				$id = $session->read('Users.id');
			} else if (isset($this->request->pass[0])) {
				$id = $this->request->pass[0];
			}
			if (!empty($id)) {
				$obj = $this->Users->get($id);
				$name = $obj->name;
				$this->Navigation->addCrumb($name, ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'view', $id]);
			} else {
				// return $this->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'index']);
			}
		}

    	$header = __('Staff');
    	$this->set('contentHeader', $header);
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
			} else {
				$this->Alert->warning('general.noData');
				$this->redirect(['action' => 'index']);
				return false;
			}
		}
		return $options;
	}
}
