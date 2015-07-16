<?php
namespace Student\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

use App\Controller\AppController;

class StudentsController extends AppController {
	public $activeObj = null;

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
		$this->ControllerAction->model()->addBehavior('TrackActivity', ['target' => 'Student.StudentActivities', 'key' => 'security_user_id', 'session' => 'Users.id']);
		$this->ControllerAction->model()->addBehavior('AdvanceSearch');
		$this->ControllerAction->model()->addBehavior('Excel', [
			'excludes' => ['password', 'photo_name'],
			'filename' => 'Students'
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
		'Guardians' 		=> ['className' => 'Student.Guardians'],
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
		$header = __('Students');

		if ($action == 'index') {
			$session->delete('Students.security_user_id');
			$session->delete('Users.id');
		} elseif ($session->check('Students.security_user_id') || $session->check('Users.id') || $action == 'view' || $action == 'edit') {
			$id = 0;
			if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
				$id = $this->request->pass[0];
			} else if ($session->check('Students.security_user_id')) {
				$id = $session->read('Students.security_user_id');
			} else if ($session->check('Users.id')) {
				$id = $session->read('Users.id');
			}
			if (!empty($id)) {
				$this->activeObj = $this->Users->get($id);
				$name = $this->activeObj->name;
				$header = $name .' - Overview';
				$this->Navigation->addCrumb($name, ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'view', $id]);
			} else {
				return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => 'index']);
			}
		}

		$this->set('contentHeader', $header);
	}

	public function onInitialize($event, $model) {
		/**
		 * if student object is null, it means that students.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
		 */
		
		if (!is_null($this->activeObj)) {
			$session = $this->request->session();
			$action = false;
			$params = $this->request->params;
			if (isset($params['pass'][0])) {
				$action = $params['pass'][0];
			}

			$persona = false;
			if ($action) {
				$this->Navigation->addCrumb($model->getHeader($model->alias), ['plugin' => 'Student', 'controller' => 'Students', 'action' => $model->alias]);
				if (strtolower($action) != 'index')	{
					if (in_array('Guardian', $model->behaviors()->loaded())) {
						if (isset($params['pass'][1])) {
							$persona = $model->get($params['pass'][1]);
							if (is_object($persona)) {
								$this->Navigation->addCrumb($persona->name);
							}
						}
					} else {
						$this->Navigation->addCrumb(ucwords($action));
					}
				}
			} else {
				$this->Navigation->addCrumb($model->getHeader($model->alias));
			}

			$header = $this->activeObj->name . ' - ' . $model->getHeader($model->alias);

			if ($model->hasField('security_user_id') && !is_null($this->activeObj)) {
				$model->fields['security_user_id']['type'] = 'hidden';
				$model->fields['security_user_id']['value'] = $this->activeObj->id;

				if (count($this->request->pass) > 1) {
					$modelId = $this->request->pass[1]; // id of the sub model

					$exists = $model->exists([
						$model->aliasField($model->primaryKey()) => $modelId,
						$model->aliasField('security_user_id') => $this->activeObj->id
						]);
					
					/**
					 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
					 */
					if (!$exists) {
						$this->Alert->warning('general.notExists');
						return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => $model->alias]);
					}
				}
			}

			$this->set('contentHeader', $header);
		} else {
			$this->Alert->warning('general.notExists');
			$event->stopPropagation();
			return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => 'index']);
		}
	}


	public function beforePaginate($event, $model, $options) {
		$session = $this->request->session();

		if ($model->alias() != 'Guardians') {
			if (in_array($model->alias, array_keys($this->ControllerAction->models))) {
				if ($this->ControllerAction->Session->check('Students.security_user_id')) {
					$securityUserId = $this->ControllerAction->Session->read('Students.security_user_id');
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
		}
		
		return $options;
	}

	public function excel($id=0) {
		$this->Users->excel($id);
		$this->autoRender = false;
	}

	public function afterFilter(Event $event) {
		$session = $this->request->session();
	}

}