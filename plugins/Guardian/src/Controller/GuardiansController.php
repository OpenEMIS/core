<?php
namespace Guardian\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class GuardiansController extends AppController {
	private $_guardianObj = null;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('User.Users');
		$this->ControllerAction->model()->addBehavior('Guardian.Guardian');
		
		// $this->ControllerAction->model()->addBehavior('User.Mandatory', ['userRole' => 'Guardian', 'roleFields' =>['Identities', 'Nationalities', 'Contacts']]);
		// $this->ControllerAction->model()->addBehavior('CustomField.Record', [
		// 	'behavior' => 'Guardian',
		// 	'recordKey' => 'security_user_id',
		// 	'fieldValueKey' => ['className' => 'Guardian.GuardianCustomFieldValues', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true],
		// 	'tableCellKey' => ['className' => 'Guardian.GuardianCustomTableCells', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]
		// ]);

		$this->ControllerAction->models = [
		'Accounts' 			=> ['className' => 'User.Accounts', 'actions' => ['view', 'edit']],
		'Contacts' => ['className' => 'UserContacts'],
		'Identities' => ['className' => 'UserIdentities'],
		'Languages' => ['className' => 'UserLanguages'],
		'Comments' => ['className' => 'UserComments'],
		];

		$this->loadComponent('Paginator');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->Navigation->addCrumb('Guardian', ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];
		$header = __('Guardians');

		if ($action == 'index') {
			$session->delete('Guardian.security_user_id');
			$session->delete('Users.id');
		} elseif ($session->check('Guardian.security_user_id') || $session->check('Users.id') || $action == 'view' || $action == 'edit') {
			$id = 0;
			if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
				$id = $this->request->pass[0];
			} else if ($session->check('Guardian.security_user_id')) {
				$id = $session->read('Guardian.security_user_id');
			} else if ($session->check('Users.id')) {
				$id = $session->read('Users.id');
			}
			if (!empty($id)) {
				$this->_GuardianObj = $this->Users->get($id);
				$name = $this->_GuardianObj->name;
				$header = $name .' - Overview';
				$this->Navigation->addCrumb($name, ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'view', $id]);
			} else {
				return $this->redirect(['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']);
			}
		}

		$this->set('contentHeader', $header);

		// $this->ControllerAction->beforePaginate = function($model, $options) {
		// 	if (in_array($model->alias, array_keys($this->ControllerAction->models))) {
		// 		if ($this->ControllerAction->Session->check('Guardian.security_user_id')) {
		// 			$securityUserId = $this->ControllerAction->Session->read('Guardian.security_user_id');
		// 			if (!array_key_exists('conditions', $options)) {
		// 				$options['conditions'] = [];
		// 			}
		// 			$options['conditions'][] = [$model->alias().'.security_user_id = ' => $securityUserId];
		// 		} else {
		// 			$this->ControllerAction->Message->alert('general.noData');
		// 			$this->redirect(['action' => 'index']);
		// 		}
		// 	}
		// 	return $options;
		// };

		// $visibility = ['view' => true, 'edit' => true];

		// $header = __('Guardian');
		// $controller = $this;

		// $this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
		// 	$header .= ' - ' . $model->alias;
		// 	$session = $this->request->session();

		// 	$model->fields['security_user_id']['type'] = 'hidden';
		// 	$model->fields['security_user_id']['value'] = $this->ControllerAction->Session->read('Guardian.security_user_id');

		// 	$controller->set('contentHeader', $header);
		// };

		// $this->Users->fields['photo_content']['type'] = 'image';

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

	public function onInitialize($event, $model) {
		/**
		 * if guardian object is null, it means that guardian.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
		 */
		if (!is_null($this->_guardianObj)) {
			$session = $this->request->session();
			$action = false;
			$params = $this->request->params;
			if (isset($params['pass'][0])) {
				$action = $params['pass'][0];
			}

			if ($action) {
				$this->Navigation->addCrumb($model->getHeader($model->alias), ['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => $model->alias]);
				if (strtolower($action) != 'index')	{
					$this->Navigation->addCrumb(ucwords($action));
				}
			} else {
				$this->Navigation->addCrumb($model->getHeader($model->alias));
			}

			$header = $this->_guardianObj->name . ' - ' . $model->getHeader($model->alias);

			if ($model->hasField('security_user_id') && !is_null($this->_guardianObj)) {
				$model->fields['security_user_id']['type'] = 'hidden';
				$model->fields['security_user_id']['value'] = $this->_guardianObj->id;

				if (count($this->request->pass) > 1) {
					$modelId = $this->request->pass[1]; // id of the sub model

					$exists = $model->exists([
						$model->aliasField($model->primaryKey()) => $modelId,
						$model->aliasField('security_user_id') => $this->_guardianObj->id
						]);
					
					/**
					 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
					 */
					if (!$exists) {
						$this->Alert->warning('general.notExists');
						return $this->redirect(['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => $model->alias]);
					}
				}
			}

			$this->set('contentHeader', $header);
		} else {
			$this->Alert->warning('general.notExists');
			$event->stopPropagation();
			return $this->redirect(['plugin' => 'Guardian', 'controller' => 'Guardians', 'action' => 'index']);
		}
	}

	public function beforePaginate($event, $model, $options) {
		$session = $this->request->session();

		if (in_array($model->alias, array_keys($this->ControllerAction->models))) {
			if ($this->ControllerAction->Session->check('Guardian.security_user_id')) {
				$securityUserId = $this->ControllerAction->Session->read('Guardian.security_user_id');
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
