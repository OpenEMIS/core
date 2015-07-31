<?php
namespace Staff\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Controller\AppController;

class StaffController extends AppController {
	public $activeObj = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('User.Users');
		$this->ControllerAction->model()->addBehavior('Staff.Staff');
		$this->ControllerAction->model()->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		$this->ControllerAction->model()->addBehavior('CustomField.Record', [
			'behavior' => 'Staff',
			'fieldKey' => 'staff_custom_field_id',
			'tableColumnKey' => 'staff_custom_table_column_id',
			'tableRowKey' => 'staff_custom_table_row_id',
			'formKey' => 'staff_custom_form_id',
			'filterKey' => 'staff_custom_filter_id',
			'formFieldClass' => ['className' => 'StaffCustomField.StaffCustomFormsFields'],
			'formFilterClass' => ['className' => 'StaffCustomField.StaffCustomFormsFilters'],
			'recordKey' => 'security_user_id',
			'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);
        $this->ControllerAction->model()->addBehavior('TrackActivity', ['target' => 'Staff.StaffActivities', 'key' => 'security_user_id', 'session' => 'Users.id']);
        $this->ControllerAction->model()->addBehavior('AdvanceSearch');
        $this->ControllerAction->model()->addBehavior('Excel', [
			'excludes' => ['password', 'photo_name'],
			'filename' => 'Staff'
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
			'BankAccounts' => ['className' => 'User.BankAccounts'],
			'History' 			=> ['className' => 'Staff.StaffActivities', 'actions' => ['index']],
		];

		$this->set('contentHeader', 'Staff');
	}

	public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$this->Navigation->addCrumb('Staff', ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'index']);
    	$session = $this->request->session();
		$action = $this->request->params['action'];
    	$header = __('Staff');

		if ($action == 'index') {
			$session->delete('Staff.security_user_id');
			$session->delete('Users.id');
		} elseif ($session->check('Staff.security_user_id') || $session->check('Users.id') || $action == 'view' || $action == 'edit') {
			$id = 0;
			if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
				$id = $this->request->pass[0];
			} else if ($session->check('Staff.security_user_id')) {
				$id = $session->read('Staff.security_user_id');
			} else if ($session->check('Users.id')) {
				$id = $session->read('Users.id');
			}
			if (!empty($id)) {
				$this->activeObj = $this->Users->get($id);
				$name = $this->activeObj->name;
				$header = $name .' - Overview';
				$this->Navigation->addCrumb($name, ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'view', $id]);
			} else {
				return $this->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'index']);
			}
		}
		
    	$this->set('contentHeader', $header);
    }

	public function onInitialize($event, $model) {
		/**
		 * if student object is null, it means that student.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
		 */
		if (!is_null($this->activeObj)) {
			$session = $this->request->session();
			$action = false;
			$params = $this->request->params;
			if (isset($params['pass'][0])) {
				$action = $params['pass'][0];
			}

			$alias = $model->alias;
			// temporary fix for renaming Sections and Classes
			if ($alias == 'Sections') $alias = 'Classes';
			else if ($alias == 'Classes') $alias = 'Subjects';

			if ($action) {
				$this->Navigation->addCrumb($model->getHeader($alias), ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => $alias]);
				if (strtolower($action) != 'index')	{
					$this->Navigation->addCrumb(ucwords($action));
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
						return $this->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => $alias]);
					}
				}
			}
			
			$this->set('contentHeader', $header);
		} else {
			$this->Alert->warning('general.notExists');
			$event->stopPropagation();
			return $this->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'index']);
		}
	}

	public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {
		$session = $this->request->session();
		
		if ($model->alias() != 'InstitutionSiteStaff') {
			if ($session->check('Staff.security_user_id')) {
				if ($model->hasField('security_user_id')) {
					$userId = $session->read('Staff.security_user_id');
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
