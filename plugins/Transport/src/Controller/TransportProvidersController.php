<?php
namespace Transport\Controller;

use Cake\Event\Event;
use App\Controller\PageController;

class TransportProvidersController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->Page->loadElementsFromTable($this->TransportProviders);
    }

	public function beforeFilter(Event $event)
    {
    	parent::beforeFilter($event);
		$page = $this->Page;

        // set Breadcrumb
    	$page->addCrumb('Transport Providers', ['plugin' => $this->plugin, 'controller' => $this->name]);
    }

	public function index()
    {
        $page = $this->Page;
        $page->exclude(['comment']);

        parent::index();
    }
}
