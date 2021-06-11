<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Profile\Controller\BodyMassesController as BaseController;

class StaffBodyMassesController extends BaseController
{
    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');
        $staffId = $session->read('Staff.Staff.id');
        $staffName = $session->read('Staff.Staff.name');

        parent::beforeFilter($event);

        // set header
        $page->setHeader($staffName . ' - ' . __('Body Mass'));

        // set queryString
        $page->setQueryString('institution_id', $institutionId);
        $page->setQueryString('security_user_id', $staffId);

        // set Breadcrumb
        $this->setBreadCrumb([
            'userId' => $staffId,
            'userName' => $staffName,
            'userRole' => 'Staff',
            'institutionId' => $institutionId,
            'institutionName' => $institutionName
        ]);

        // set Tabs
        $this->setupInstitutionTabElements([
            'userId' => $staffId,
            'userName' => $staffName,
            'userRole' => 'Staff',
            'institutionId' => $institutionId
        ]);
    
        $page->get('security_user_id')->setControlType('hidden')->setValue($staffId); // set value and hide the staff_id
    
        $this->setTooltip();
    }

    public function index()
    {//die("kk");
        $page = $this->Page;

        // set field
        //$page->exclude(['academic_period_id', 'institution_id']);
    }
}