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
		$this->ControllerAction->model()->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		$this->ControllerAction->model()->addBehavior('CustomField.Record', [
			'behavior' => 'Student',
			'recordKey' => 'security_user_id',
			'fieldValueKey' => ['className' => 'Student.StudentCustomFieldValues', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellKey' => ['className' => 'Student.StudentCustomTableCells', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);

		$this->ControllerAction->models = [
			'Accounts' 			=> ['className' => 'User.Accounts', 'actions' => ['view', 'edit']],
			'Contacts' 			=> ['className' => 'User.Contacts'],
			'Identities' 		=> ['className' => 'User.Identities'],
			'Languages' 		=> ['className' => 'User.UserLanguages'],
			'Comments' 			=> ['className' => 'User.Comments'],
			'SpecialNeeds' 		=> ['className' => 'User.SpecialNeeds'],
			'Awards' 			=> ['className' => 'User.Awards'],
			'Attachments' 		=> ['className' => 'User.Attachments'],
			'Programmes' 		=> ['className' => 'Student.Programmes', 'actions' => ['index']],
			'Sections'			=> ['className' => 'Student.StudentSections', 'actions' => ['index']],
			'Classes' 			=> ['className' => 'Student.StudentClasses', 'actions' => ['index']],
			'Absences' 			=> ['className' => 'Student.Absences', 'actions' => ['index']],
			'Behaviours' 		=> ['className' => 'Student.StudentBehaviours', 'actions' => ['index']],
			'Results' 			=> ['className' => 'Student.Results', 'actions' => ['index']],
			'Extracurriculars' 	=> ['className' => 'Student.Extracurriculars'],
			'BankAccounts' 		=> ['className' => 'User.BankAccounts'],
			'StudentFees' 		=> ['className' => 'Student.StudentFees', 'actions' => ['index']],
			'History' 			=> ['className' => 'Student.StudentActivities', 'actions' => ['index']],

		];

		$this->loadComponent('Paginator');
		$this->set('contentHeader', 'Students');
	}

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$this->Navigation->addCrumb('Student', ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'index']);
    	$session = $this->request->session();
		$action = $this->request->params['action'];

		if ($action == 'index') {
			$session->delete('Student.security_user_id');
			$session->delete('Users.id');
		}
		if ($session->check('Student.security_user_id') || $session->check('Users.id') || $action == 'view') {
			// $id = 0;
			if ($session->check('Student.security_user_id')) {
				$id = $session->read('Student.security_user_id');
			} else if ($session->check('Users.id')) {
				$id = $session->read('Users.id');
			} else if (isset($this->request->pass[0])) {
				$id = $this->request->pass[0];
			}
			if (!empty($id)) {
				$obj = $this->Users->get($id);
				$name = $obj->name;
				$this->Navigation->addCrumb($name, ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'view', $id]);
			} else {
				// return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => 'index']);
			}
		}
    	$header = __('Student');
    	$this->set('contentHeader', $header);
    }

	public function onInitialize($event, $model) {
		$session = $this->request->session();
		$header = __('Student');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb($model->getHeader($model->alias), ['plugin' => 'Student', 'controller' => 'Students', 'action' => $model->alias]);

		if (array_key_exists('security_user_id', $model->fields)) {
			if (!$session->check('Student.security_user_id')) {
				$this->Alert->warning('general.notExists');
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
				$this->Alert->warning('general.noData');
				$this->redirect(['action' => 'index']);
				return false;
			}
		}
		return $options;
	}

	public function afterFilter(Event $event) {
		$session = $this->request->session();
	}

}