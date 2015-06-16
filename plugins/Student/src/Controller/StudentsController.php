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
			'StudentFees' => ['className' => 'Student.StudentFees']
		];

		$this->loadComponent('Paginator');
		$this->set('contentHeader', 'Students');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Navigation->addCrumb('Students', ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'index']);
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
}