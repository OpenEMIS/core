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
}
