<?php
namespace Map\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;

class MapController extends AppController
{
	public function initialize(): void
	{
		parent::initialize();

		$this->attachAngularModules();
	}

	public function beforeFilter(EventInterface $event)
	{
		parent::beforeFilter($event);

		$header = __('Maps');
		$this->set('contentHeader', $header);
	}

	public function index()
	{
		$this->set('ngController', 'MapCtrl as MapController');
        $this->set('noBreadcrumb', true);


        // Start POCOR-5188
        $manualTable = TableRegistry::getTableLocator()->get('Manuals');
        $ManualContent = $manualTable->find()->select(['url'])->where([
                $manualTable->aliasField('function') => 'Map',
                $manualTable->aliasField('module') => 'Reports',
                $manualTable->aliasField('category') => 'Reports',
                ])->first();

        if (!empty($ManualContent['url'])) {
            $this->set('is_manual_exist', ['status'=>'success', 'url'=>$ManualContent['url']]);
        }else{
            $this->set('is_manual_exist', []);
        }
        // End POCOR-5188
	}

	private function attachAngularModules()
	{
		// echo "<pre>";print_r($this->request->getAttribute('params')['action']);die;
		$action = $this->request->getAttribute('params')['action'];
		switch ($action) {
			case 'index':
				$this->Angular->addModules([
					'map.ctrl',
					'map.svc'
				]);
			break;
		}
	}

	public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
    }
}
