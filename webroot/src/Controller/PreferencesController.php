<?php
namespace App\Controller;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;

use App\Controller\AppController;

class PreferencesController extends AppController {
	public $activeObj = null;

	public function initialize() {
		parent::initialize();
		$this->ControllerAction->models = [];
	}

	public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(Event $event, $action)
    {
        return true;
    }

    // CAv4
    public function Preferences()	{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Preferences']); }
    // End

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$header = __('Preferences');

		$action = $this->request->params['action'];

        $Preferences = TableRegistry::get('Preferences');
        $loginUserId = $this->Auth->user('id');

        if ($Preferences->exists([$Preferences->primaryKey() => $loginUserId])) {
            $this->activeObj = $Preferences->get($loginUserId);
        }

		$this->Navigation->addCrumb('Preferences', ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index']);

		$this->set('contentHeader', $header);
	}

	public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		if (!is_null($this->activeObj)) {
			if ($model->hasField('security_user_id') && !is_null($this->activeObj)) {
				$model->fields['security_user_id']['type'] = 'hidden';
				$model->fields['security_user_id']['value'] = $this->activeObj->id;
			}
		}
	}

	public function index() {
		$userId = $this->Auth->user('id');
		return $this->redirect(['plugin' => false, 'controller' => $this->name, 'action' => 'Preferences', 'view', $this->ControllerAction->paramsEncode(['id' => $userId])]);
	}

	public function beforePaginate(Event $event, $model, Query $query, ArrayObject $options) {
		$user = $this->Auth->user();
		if (isset($user['id'])) {
			$userId = $user['id'];
			$query->where([$model->aliasField('security_user_id') => $userId]);
		} else {
			$this->Alert->warning('general.noData');
			$event->stopPropagation();
			return $this->redirect(['action' => 'index']);
		}
	}

    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra) {
        $this->beforePaginate($event, $model, $query, $extra);
    }
}
