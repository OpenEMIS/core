<?php
namespace Scholarship\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class ScholarshipRecipientsController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Scholarship.RecipientActivityStatuses');
        $this->loadComponent('Scholarship.ScholarshipTabs');
        $this->Page->loadElementsFromTable($this->ScholarshipRecipients);
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderStatus'] = 'onRenderStatus';
        $event['Controller.Page.getEntityRowActions'] = 'getEntityRowActions';
        return $event;
    }

    public function beforeFilter(Event $event)
    {
    	$page = $this->Page;
        parent::beforeFilter($event);

		$page->addCrumb('Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Scholarships', 'index']);
		$page->addCrumb('Recipients');

		$page->setHeader(__('Scholarships') . ' - ' . __('Recipients'));

        $page->disable(['add', 'delete']);
    }

    public function index()
    {
        parent::index();
        $page = $this->Page;

        $page->exclude(['scholarship_recipient_activity_status_id', 'approved_amount']);

        $page->addNew('status');
        $page->addNew('openemis_no')
            ->setDisplayFrom('recipient.openemis_no');
        $page->addNew('financial_assistance_type')
            ->setDisplayFrom('scholarship.financial_assistance_type.name');

        $page->move('status')->first();
        $page->move('openemis_no')->after('status');
        $page->move('recipient_id')->after('openemis_no');
        $page->move('financial_assistance_type')->after('recipient_id');
        $page->move('scholarship_id')->after('financial_assistance_type');
    }

    public function view($id)
    {
        parent::view($id);
        $page = $this->Page;
        $entity = $page->getData();
        $this->setupTabElements();

        $page->exclude(['scholarship_recipient_activity_status_id']);

        $page->addNew('status');
        $page->addNew('openemis_no')
            ->setDisplayFrom('recipient.openemis_no');
        $page->addNew('financial_assistance_type')
            ->setDisplayFrom('scholarship.financial_assistance_type.name');

        $activityStatusData = $this->getActivityStatusData($entity);
        $page->addNew('activity_status')
            ->setControlType('table')
            ->setAttributes('column', [
                ['label' => __('Date'), 'key' => 'date'],
                ['label' => __('Transition'), 'key' => 'transition'],
                ['label' => __('Comments'), 'key' => 'comments']
            ])
            ->setAttributes('row', $activityStatusData);

        $page->move('status')->first();
        $page->move('openemis_no')->after('status');
        $page->move('recipient_id')->after('openemis_no');
        $page->move('financial_assistance_type')->after('recipient_id');
        $page->move('scholarship_id')->after('financial_assistance_type');
        $page->move('approved_amount')->after('scholarship_id');
        $page->move('activity_status')->after('approved_amount');
    }

    public function edit($id)
    {
        parent::edit($id);
        $page = $this->Page;
        $entity = $page->getData();

        $this->setupTabElements();

        $page->addNew('openemis_no')
            ->setDisplayFrom('recipient.openemis_no');
        $page->addNew('financial_assistance_type')
            ->setDisplayFrom('scholarship.financial_assistance_type.name');

        $page->addNew('status')
            ->setControlType('section');
        $page->addNew('date')
            ->setControlType('date');

        $nextStatusOptions = $this->RecipientActivityStatuses
            ->find('optionList', ['defaultOption' => false])
            ->where([
                $this->RecipientActivityStatuses->aliasField('id <>') => $entity->scholarship_recipient_activity_status_id
            ])
            ->toArray();

        $page->addNew('next_status')
            ->setControlType('select')
            ->setOptions($nextStatusOptions);

        $page->addNew('comments', ['length' => ''])
            ->setControlType('textarea');

        $page->move('scholarship_recipient_activity_status_id')->first();
        $page->move('openemis_no')->after('scholarship_recipient_activity_status_id');
        $page->move('recipient_id')->after('openemis_no');
        $page->move('financial_assistance_type')->after('recipient_id');
        $page->move('scholarship_id')->after('financial_assistance_type');
    }

    public function setupTabElements()
    {
        $page = $this->Page;
        $tabElements = $this->ScholarshipTabs->getScholarshipRecipientTabs();

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }

        // set active tab
        $page->getTab('Recipients')->setActive('true');
    }

    public function onRenderStatus(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view'])) {
            if ($entity->has('recipient_activity_status') && $entity->recipient_activity_status->has('name')) {
                return '<span class="status highlight">' . $entity->recipient_activity_status->name . '</span>';
            }
        }
    }

    public function getEntityRowActions(Event $event, $entity, ArrayObject $rowActions)
    {
        $rowActionsArray = $rowActions->getArrayCopy();

        $recipientId = $entity->recipient_id;
        $scholarshipId = $entity->scholarship_id;
        $queryString = $this->paramsEncode([
            'recipient_id' => $recipientId,
            'scholarship_id' => $scholarshipId
        ]);

        if (array_key_exists('view', $rowActions)) {
            $rowActionsArray['view']['url']['queryString'] = $queryString;
        }

        if (array_key_exists('edit', $rowActions)) {
            $rowActionsArray['edit']['url']['queryString'] = $queryString;
        }

        $rowActions->exchangeArray($rowActionsArray);
    }

    private function getActivityStatusData(Entity $entity)
    {
        $rows = [];
        if ($entity->has('recipient_activities')) {
            foreach ($entity->recipient_activities as $key => $obj) {
                // $dateDisplay = $obj->date->format('Y-m-d H:i:s');
                $dateDisplay = $this->ScholarshipRecipients->formatDate($obj->date);
                $prevStatusName = $obj->prev_recipient_activity_status_name;
                $statusName = $obj->recipient_activity_status_name;

                $transitionDisplay = '<span class="status past">' . __($prevStatusName) . '</span>';
                $transitionDisplay .= '<span class="transition-arrow"></span>';
                if (count($entity->recipient_activities) - 1 == $key) {
                    $transitionDisplay .= '<span class="status highlight">' . __($statusName) . '</span>';
                } else {
                    $transitionDisplay .= '<span class="status past">' . __($statusName) . '</span>';
                }

                $rows[] = ['date' => $dateDisplay, 'transition' => $transitionDisplay, 'comments' => nl2br($obj->comments)];
            }
        }

        return $rows;
    }
}
