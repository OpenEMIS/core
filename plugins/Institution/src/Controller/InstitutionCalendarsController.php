<?php
namespace Institution\Controller;

use App\Controller\CalendarsController as BaseController;

class InstitutionCalendarsController extends BaseController
{
    public function initialize()
    {
        parent::initialize();

        // POCOR-4347 Disable CRUD once the institution is inactive
        $this->loadComponent('Institution.InstitutionInactive');
    }

}
