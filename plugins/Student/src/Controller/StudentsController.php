<?php
namespace Student\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
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
			'fieldKey' => 'student_custom_field_id',
			'tableColumnKey' => 'student_custom_table_column_id',
			'tableRowKey' => 'student_custom_table_row_id',
			'formKey' => 'student_custom_form_id',
			'filterKey' => 'student_custom_filter_id',
			'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
			'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
			'recordKey' => 'security_user_id',
			'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]
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
			'History' 			=> ['className' => 'Student.StudentActivities', 'actions' => ['index']]
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
			$alias = $model->alias;
			// temporary fix for renaming Sections and Classes
			if ($alias == 'Sections') $alias = 'Classes';
			else if ($alias == 'Classes') $alias = 'Subjects';
			
			if ($action) {
				$this->Navigation->addCrumb($model->getHeader($alias), ['plugin' => 'Student', 'controller' => 'Students', 'action' => $alias]);
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
				$this->Navigation->addCrumb($model->getHeader($alias));
			}

			$header = $this->activeObj->name . ' - ' . $model->getHeader($alias);

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
						return $this->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => $alias]);
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

	public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {
		$session = $this->request->session();

		if ($model->alias() != 'InstitutionSiteStudents') {
			if ($session->check('Students.security_user_id')) {
				if ($model->hasField('security_user_id')) {
					$userId = $session->read('Students.security_user_id');
					$query->where([$model->aliasField('security_user_id') => $userId]);
				}
			} else {
				$this->Alert->warning('general.noData');
				$event->stopPropagation();
				return $this->redirect(['action' => 'index']);
			}
		} else {
			// we only show distinct records at system level
			$query->group([$model->aliasField('security_user_id')]);
		}
	}

	public function excel($id=0) {
		$this->Users->excel($id);
		$this->autoRender = false;
	}
}
