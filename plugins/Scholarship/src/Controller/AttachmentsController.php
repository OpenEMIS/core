<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Datasource\ResultSetInterface;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class AttachmentsController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Security.Users');
        $this->loadModel('Scholarship.ApplicationAttachments');
        $this->loadModel('Scholarship.AttachmentTypes');
        $this->loadModel('Scholarship.ScholarshipsScholarshipAttachmentTypes');

        $this->loadComponent('Scholarship.ScholarshipTabs');

        $this->Page->loadElementsFromTable($this->ApplicationAttachments);
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderMandatory'] = 'onRenderMandatory';
        return $event;
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        parent::beforeFilter($event);

        $page->get('scholarship_attachment_type_id')
            ->setLabel('Type');

        if ($page->is(['view', 'add', 'edit'])) {
            $page->exclude(['file_name']);
            $page->move('file_content')->after('scholarship_attachment_type_id');

        } elseif ($page->is(['delete'])) {
            $page->exclude(['file_content']);
            $page->move('file_name')->after('scholarship_attachment_type_id');
        }
    }

    public function index()
    {
        $page = $this->Page;
        $page->setAutoContain(false);

        parent::index();

        $page->exclude(['applicant_id', 'scholarship_id', 'file_name', 'file_content']);

        $page->addNew('mandatory');
        $page->addNew('uploaded_by')->setDisplayFrom('created_user.name');
        $page->addNew('uploaded_on')->setDisplayFrom('created');

        $page->move('mandatory')->first();
    }

    public function add()
    {
        parent::add();
        $this->addEdit();
    }

    private function addEdit()
    {
        $page = $this->Page;

        $applicantId = $page->getQueryString('applicant_id');
        $scholarshipId = $page->getQueryString('scholarship_id');

        $attachmentTypesOption = $this->AttachmentTypes
            ->find('availableAttachmentTypes', [
                'applicant_id' => $applicantId,
                'scholarship_id' => $scholarshipId
            ])
            ->formatResults(function (ResultSetInterface $results) {
                $returnArr = [];
                foreach ($results as $result) {
                    $name = $result->name;
                    if (!$result->is_mandatory) {
                        $name .= ' (' . __('Optional') . ')';
                    }
                    $returnArr[$result->id] = $name;
                }
                return $returnArr;
            })
            ->toArray();

        $page->get('scholarship_attachment_type_id')
            ->setControlType('select')
            ->setOptions($attachmentTypesOption);
    }

    public function setBreadCrumb($options)
    {
        $page = $this->Page;
        $plugin = $this->plugin;
        $name = $this->name;

        $userName = array_key_exists('userName', $options) ? $options['userName'] : '';
        $userId = array_key_exists('userId', $options) ? $options['userId'] : '';

        if ($plugin == 'Scholarship') {
            $page->addCrumb('Scholarships', [
                'plugin' => 'Scholarship',
                'controller' => 'Scholarships',
                'action' => 'Scholarships',
                'index'
            ]);
            
            $page->addCrumb('Applicants', [
                'plugin' => 'Scholarship',
                'controller' => 'Scholarships',
                'action' => 'Applications',
                'index'
            ]);

            $page->addCrumb($userName);
            $page->addCrumb(__('Attachments'));

        } else if ($plugin == 'Profile') {
            $page->addCrumb('Profile', [
                'plugin' => 'Profile',
                'controller' => 'Profiles',
                'action' => 'Profiles',
                'view',
                $this->paramsEncode(['id' => $userId])
            ]);
            $page->addCrumb($userName);
            $page->addCrumb('Scholarship Applications');
            $page->addCrumb('Attachments');
        }
    }

    public function setupTabElements()
    {
        $page = $this->Page;
        $name = $this->name;

        $tabElements = [];
        if ($name == 'ScholarshipApplicationAttachments') {
            $tabElements = $this->ScholarshipTabs->getScholarshipApplicationTabs();
        } elseif ($name == 'ProfileApplicationAttachments') {
            $tabElements = $this->ScholarshipTabs->getScholarshipProfileTabs();
        }

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }

        // set active tab
        $page->getTab('Attachments')->setActive('true');
    }

    public function onRenderMandatory(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index'])) {
            $isMandatory = $this->ScholarshipsScholarshipAttachmentTypes->get([
                    'scholarship_id' => $entity->scholarship_id,
                    'scholarship_attachment_type_id' => $entity->scholarship_attachment_type_id
                ])
                ->is_mandatory;

            return $isMandatory ? "<i class='fa fa-check'></i>" : "<i class='fa fa-close'></i>";
        }
    }
}
