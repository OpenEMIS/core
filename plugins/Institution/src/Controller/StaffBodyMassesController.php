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
        $institutionId = $this->getInstitutionID();
        $institutionName = $session->read('Institution.Institutions.name');
        $staffId = $session->read('Staff.Staff.id');
        $staffName = $session->read('Staff.Staff.name');
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
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
            'institutionId' => $encodedInstitutionId,
            'institutionName' => $institutionName
        ]);

        // set Tabs
        $this->setupInstitutionTabElements([
            'userId' => $staffId,
            'userName' => $staffName,
            'userRole' => 'Staff',
            'institutionId' => $encodedInstitutionId
        ]);
    
        $page->get('security_user_id')->setControlType('hidden')->setValue($staffId); // set value and hide the staff_id
    
        $this->setTooltip();
    }

    private function getInstitutionID()
    {
        $session = $this->request->session();
        $insitutionIDFromSession = $session->read('Institution.Institutions.id');
        $encodedInstitutionIDFromSession = $this->paramsEncode(['id' => $insitutionIDFromSession]);
        $encodedInstitutionID = isset($this->request->params['institutionId']) ?
            $this->request->params['institutionId'] :
            $encodedInstitutionIDFromSession;
        try {
            $institutionID = $this->paramsDecode($encodedInstitutionID)['id'];
        } catch (\Exception $exception) {
            $institutionID = $insitutionIDFromSession;
        }
        return $institutionID;
    }
}