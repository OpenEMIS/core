<?php
namespace Map\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class MapController extends AppController
{
	public function initialize()
	{
		parent::initialize();

		$this->attachAngularModules();
	}

	public function beforeFilter(Event $event)
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
        $manualTable = TableRegistry::get('Manuals');
        $ManualContent =   $manualTable->find()->select(['url'])->where([
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
		$action = $this->request->action;

		switch ($action) {
			case 'index':
				$this->Angular->addModules([
					'map.ctrl',
					'map.svc'
				]);
			break;
		}
	}
}
