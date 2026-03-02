<?php
namespace App\Controller;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;

use App\Controller\AppController;

class PreferencesController extends AppController {
	public $activeObj = null;

	public function initialize(): void {
		parent::initialize();
		$this->ControllerAction->models = [];
	}

	public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
		return $events;
    }

    public function isActionIgnored(EventInterface $event, $action)
    {
        return true;
    }

    // CAv4
    public function Preferences()	{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Preferences']); }
    // End

    public function beforeFilter(EventInterface $event) {
		parent::beforeFilter($event);
		$header = __('Preferences');

		$action = $this->request->getParam('action');

        $Preferences = TableRegistry::getTableLocator()->get('Preferences');
        $loginUserId = $this->Auth->user('id');
        if ($Preferences->exists([$Preferences->getPrimaryKey() => $loginUserId])) {
            $this->activeObj = $Preferences->get($loginUserId);
        }

		$this->Navigation->addCrumb('Preferences', ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index']);

		$this->set('contentHeader', $header);
	}
	//POCOR-9447 Start
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
		$buttons = $this->viewBuilder()->getVars()['toolbarButtons'] ?? null;
		
		if ($buttons) {
			unset($buttons['back'], $buttons['remove']); //dd($buttons);
			if (isset($buttons['edit']['url'])) {
				$buttons['edit']['url'] = [
					'plugin' => false,
					'controller' => 'Preferences',
					'action' => 'edit',
					$this->request->getParam('pass.0')
				];
			}
			$this->set('toolbarButtons', $buttons); 
		}
    }
	//POCOR-9447 End

	public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra) {
		if (!is_null($this->activeObj)) {
			if ($model->hasField('security_user_id') && !is_null($this->activeObj)) {
				$model->fields['security_user_id']['type'] = 'hidden';
				$model->fields['security_user_id']['value'] = $this->activeObj->id;
			}
		}
	}

	public function index() {
		$userId = $this->Auth->user('id');
		return $this->redirect(['plugin' => false, 'controller' => $this->getName(), 'action' => 'view','', $this->ControllerAction->paramsEncode(['id' => $userId])]);
	}

	public function beforePaginate(EventInterface $event, $model, Query $query, ArrayObject $options) {
		$user = $this->Auth->user();
		if (isset($user['id'])) {
			$userId = $user['id'];
			$query->where([$model->aliasField('id') => $userId]);
		} else {
			$this->Alert->warning('general.noData');
			$event->stopPropagation();
			return $this->redirect(['action' => 'index']);
		}
	}

    public function beforeQuery(EventInterface $event, Table $model, Query $query, ArrayObject $extra) {
        $this->beforePaginate($event, $model, $query, $extra);
    }
}
