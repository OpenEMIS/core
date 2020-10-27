<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Datasource\ResultSetInterface;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class InstitutionStaffDutiesController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->loadModel('Institution.StaffDuties');
        $this->loadModel('Institution.Staff');
    }

	public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');

    	parent::beforeFilter($event);

        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

		$page = $this->Page;

        // set Breadcrumb
        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
        $page->addCrumb('Duties');

        // set header
        $page->setHeader($institutionName . ' - ' . __('Duties'));

        // to filter by institution_id
        $page->setQueryString('institution_id', $institutionId);

        // set institution_id
        $page->get('institution_id')
            ->setControlType('hidden')
            ->setValue($institutionId);

        // $repeatOptions = [
        //     1 => __('Yes'),
        //     0 => __('No')
        // ];
        // $page->get('repeat')
        //     ->setControlType('select')
        //     ->setOptions($repeatOptions, false);
    }

	public function index()
    {
        parent::index();

        $page = $this->Page;
        $page->exclude(['institution_id']);

        
        // reorder fields
        $page->move('academic_period_id')->first();
        $page->move('staff_duties_id')->after('academic_period_id');
        // end reorder fields
    }

     public function view($id)
    {
        parent::view($id);

        $page = $this->Page;

        $entity = $page->getData();

        // $page->addNew('information')
        //     ->setControlType('section');

        // $page->addNew('days')
        //     ->setControlType('select')
        //     ->setAttributes('multiple', true);

        // $page->addNew('passengers')
        //     ->setControlType('section');

        // $assignedStudents = $this->getAssignedStudents($entity);
        // $page->addNew('assigned_students')
        //     ->setControlType('table')
        //     ->setAttributes('column', [
        //         ['label' => __('OpenEMIS ID'), 'key' => 'openemis_no'],
        //         ['label' => __('Student'), 'key' => 'student'],
        //         ['label' => __('Education Grade'), 'key' => 'education_grade'],
        //         ['label' => __('Status'), 'key' => 'status']
        //     ])
        //     ->setAttributes('row', $assignedStudents);

        $this->reorderFields();
    }

    public function add()
    {
        parent::add();
        $this->addEdit();
        $page = $this->Page;

        if ($this->request->is(['get'])) {
            // set default academic period to current year
            $academicPeriodId = !is_null($page->getQueryString('academic_period_id')) ? $page->getQueryString('academic_period_id') : $this->AcademicPeriods->getCurrent();
            $page->get('academic_period_id')->setValue($academicPeriodId);
        }
    }

    public function edit($id)
    {
        parent::edit($id);
        $this->addEdit($id);
    }

    private function addEdit($id=0)
    {
        $page = $this->Page;

        $entity = $page->getData();
    
        $institutionId = $page->getQueryString('institution_id');


        if ($entity->isNew()) {
            // Academic Period
            $academicPeriodOptions = $this->AcademicPeriods->getYearList();
            $page->get('academic_period_id')
                ->setControlType('select')
                ->setOptions($academicPeriodOptions, false);
            // end Academic Period

            // Staff Duties
            $StaffDuties = $this->StaffDuties
                ->find('optionList', ['defaultOption' => false])
                ->toArray();
            $page->get('staff_duties_id')
                ->setControlType('select')
                ->setOptions($StaffDuties, false);

                // Staff List
            $StaffList = $this->Staff
                ->find('optionList', ['defaultOption' => false])
                ->toArray();
            $page->get('staff_id')
                ->setControlType('select')
                ->setOptions($StaffList, false);

                

            // reorder fields
            $page->move('academic_period_id')->first();
            $page->move('staff_duties_id')->after('academic_period_id');
            // end reorder fields
        } else {
            $page->get('academic_period_id')
                ->setDisabled(true);


            $institutionId = $entity->institution_id;
            $academicPeriodId = $entity->academic_period_id;

            $this->reorderFields();
        }
    }

    private function reorderFields()
    {
        $page = $this->Page;

        $page->move('academic_period_id')->first();
       
        
    }

}
