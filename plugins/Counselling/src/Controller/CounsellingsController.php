<?php
namespace Counselling\Controller;

use Cake\Event\Event;

use App\Controller\PageController;

class CounsellingsController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->Page->loadElementsFromTable($this->Counsellings);

        $this->Page->enable(['download']);
    }

    public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');
        $studentId = $session->read('Student.Students.id');
        $studentName = $session->read('Student.Students.name');

        parent::beforeFilter($event);

        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
        $encodedStudentId = $this->paramsEncode(['id' => $studentId]);

        $page = $this->Page;

        // set Breadcrumb
        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
        $page->addCrumb('Students', ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'Students', 'institutionId' => $encodedInstitutionId]);
        $page->addCrumb($studentName, ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $encodedStudentId]);
        $page->addCrumb('Counselling');

        $page->get('student_id')->setControlType('hidden')->setValue($studentId); // set value and hide the student_id

        $page->move('file_name')->after('guidance_type_id'); // move file_content after guidance type
        $page->move('file_content')->after('file_name'); // move file_name after file_content

        // set header
        $header = $page->getHeader();
        $page->setHeader($studentName . ' - ' . $header);

        // set queryString
        $page->setQueryString('institution_id', $institutionId);
        $page->setQueryString('student_id', $studentId);
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['file_name', 'file_content', 'student_id', 'guidance_utilized', 'comment']);

        // set default ordering
        $page->setQueryOption('order', [$this->Counsellings->aliasField('date') => 'DESC']);

        parent::index();
    }

    public function add()
    {
        $this->addEditCounselling();
        parent::add();
    }

    public function edit($id)
    {
        $this->addEditCounselling();
        parent::edit($id);
    }

    public function view($id)
    {
        $page = $this->Page;
        $page->exclude(['file_name']);

        // set the file download for attachment
        $page->get('file_content')
            ->setLabel('Attachment')
            ->setAttributes('fileNameField', 'file_name');

        $this->reorderFields();

        parent::view($id);
    }

    public function delete($id)
    {
        $page = $this->Page;
        $page->exclude(['file_content']);
        parent::delete($id);
    }

    private function addEditCounselling()
    {
        $page = $this->Page;
        $page->exclude(['file_name']);
        $institutionId = $page->getQueryString('institution_id');
        $studentId = $page->getQueryString('student_id');

        $page->get('guidance_type_id')->setControlType('select');

        // set the options for counselor_id
        $counselorOptions = $this->Counsellings->getCounselorOptions($institutionId);
        $page->get('counselor_id')->setControlType('select')->setOptions($counselorOptions);

        $requestorOptions = $this->Counsellings->getRequesterOptions($institutionId);
        $page->get('requester_id')->setControlType('select')->setOptions($requestorOptions);

        // set the file upload for attachment
        $page->get('file_content')
            ->setLabel('Attachment')
            ->setAttributes('fileNameField', 'file_name');

        $this->reorderFields();
    }

    private function reorderFields()
    {
        $page = $this->Page;

        $page->move('counselor_id')->after('date');
        $page->move('guidance_type_id')->after('counselor_id');
        $page->move('requester_id')->after('guidance_type_id');
        $page->move('guidance_utilized')->after('requester_id');
        $page->move('description')->after('guidance_utilized');
        $page->move('intervention')->after('description');
        $page->move('comment')->after('intervention');
        $page->move('file_content')->after('comment');
    }
}
