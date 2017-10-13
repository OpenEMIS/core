<?php
namespace Transport\Controller;

use App\Controller\PageController;

class BusesController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->Page->loadElementsFromTable($this->Buses);
    }
}
