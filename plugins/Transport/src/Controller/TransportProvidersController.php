<?php
namespace Transport\Controller;

use App\Controller\PageController;

class TransportProvidersController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->Page->loadElementsFromTable($this->TransportProviders);
    }

	public function index()
    {
        $page = $this->Page;
        $page->exclude(['comment']);

        parent::index();
    }
}
