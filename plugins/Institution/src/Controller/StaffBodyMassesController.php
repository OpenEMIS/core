<?php
namespace Institution\Controller;

use Cake\Event\Event;
use App\Controller\PageController;
use Page\Model\Entity\PageElement;
use Profile\Controller\BodyMassesController as BaseController;

class StaffBodyMassesController extends BaseController
{
    public function initialize()
    {
        parent::initialize();
        $this->Page->enable(['download']);
        // $this->addBehavior('Page.FileUpload', [
        //     'name' => 'file_name',
        //     'content' => 'file_content',
        //     'size' => '10MB',
        //     'contentEditable' => true,
        //     'allowable_file_types' => 'all',
        //     'useDefaultName' => true
        // ]);
    }

    public function index()
    {
        $page = $this->Page;

        // set field
        $page->exclude(['id','security_user_id','file_name', 'file_content', 'comment']);
        
        parent::index();
    }  
    public function view($id)
    {
        $page = $this->Page;
        $page->exclude(['file_name']);

        // set the file download for attachment
        $page->get('file_content')
            ->setLabel('Attachment')
            ->setAttributes('fileNameField', 'file_name');

        parent::view($id);

        $entity = $page->getData();

    } 

    private function addEditBodyMass()
    {
        $page = $this->Page;
        $page->exclude(['file_name']);

        // set the file upload for attachment
        $page->get('file_content')
            ->setLabel('Attachment')
            ->setAttributes('fileNameField', 'file_name');
    }

    public function add()
    {
        $this->addEditBodyMass();
        parent::add();
    }

    public function delete($id)
    {
        $page = $this->Page;
        $page->exclude(['file_content']);
        parent::delete($id);
    }

    public function edit($id)
    {
        $this->addEditBodyMass();
        parent::edit($id);
    }

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
}