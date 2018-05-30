<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class ScholarshipRecipientCollectionsController extends PageController
{
    private $currency = null;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Scholarship.RecipientCollections');
        $this->loadModel('Scholarship.Scholarships');
        $this->loadModel('Scholarship.ScholarshipRecipients');
        $this->loadModel('Scholarship.FinancialAssistanceTypes');
        $this->loadModel('Security.Users');
        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->loadModel('Configuration.ConfigItems');
        $this->Page->loadElementsFromTable($this->RecipientCollections);

        $this->loadComponent('Scholarship.ScholarshipTabs');
        $this->currency = $this->ConfigItems->value('currency');
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderBalanceAmount'] = 'onRenderBalanceAmount';
        return $event;
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        parent::beforeFilter($event);

        $queryString = $this->request->query('queryString');
        $recipientId = $this->paramsDecode($queryString)['recipient_id'];
        $scholarshipId = $this->paramsDecode($queryString)['scholarship_id'];
        $recipientName = $this->Users->get($recipientId)->name;

        $page->addCrumb('Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Scholarships', 'index']);
        $page->addCrumb('Recipients', ['plugin' => 'Scholarship', 'controller' => 'ScholarshipRecipients', 'action' => 'index']);
        $page->addCrumb($recipientName);
        $page->addCrumb('Collections');

        $page->setHeader($recipientName . ' - ' . __('Collections'));

        $page->setQueryString('recipient_id', $recipientId);
        $page->setQueryString('scholarship_id', $scholarshipId);

        $page->get('recipient_id')->setControlType('hidden')->setValue($recipientId);
        $page->get('scholarship_id')->setControlType('hidden')->setValue($scholarshipId);
        $page->get('amount')->setLabel($this->addCurrencySuffix('Amount'));

        $page->move('academic_period_id')->first();
        $page->move('payment_date')->after('academic_period_id');
        $page->move('amount')->after('payment_date');
        $page->move('comments')->after('amount');

        $this->setupTabElements($scholarshipId);
    }

    public function index()
    {
        $page = $this->Page;
        parent::index();
        $page->exclude(['recipient_id', 'scholarship_id']);
    }

    public function add()
    {
        parent::add();
        $this->addEdit();
    }

    public function edit($id)
    {
        parent::edit($id);
        $this->addEdit($id);
    }

    private function addEdit($id = null)
    {
        $page = $this->Page;

        $recipientId = $page->getQueryString('recipient_id');
        $scholarshipId = $page->getQueryString('scholarship_id');

        $scholarshipEntity = $this->Scholarships->get($scholarshipId, ['contain' => ['FinancialAssistanceTypes']]);
        $recipientEntity = $this->ScholarshipRecipients->get(['recipient_id' => $recipientId, 'scholarship_id' => $scholarshipId]);

        // summary fields
        $page->addNew('financial_assistance_type')
            ->setControlType('string')
            ->setDisabled(true)
            ->setValue($scholarshipEntity->financial_assistance_type->name);

        $page->addNew('loan')
            ->setControlType('string')
            ->setDisabled(true)
            ->setValue($scholarshipEntity->code_name);

        $page->addNew('approved_amount')
            ->setControlType('string')
            ->setDisabled(true)
            ->setLabel($this->addCurrencySuffix('Approved Amount'))
            ->setValue($recipientEntity->approved_amount);

        $page->addNew('balance_amount')
            ->setControlType('string')
            ->setLabel($this->addCurrencySuffix('Balance Amount'))
            ->setDisabled(true);

        $page->addNew('collections')->setControlType('section');

        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $page->get('academic_period_id')
            ->setControlType('select')
            ->setOptions($academicPeriodOptions);

        $page->move('financial_assistance_type')->first();
        $page->move('loan')->after('financial_assistance_type');
        $page->move('approved_amount')->after('loan');
        $page->move('balance_amount')->after('approved_amount');
        $page->move('collections')->after('balance_amount');
        $page->move('academic_period_id')->after('collections');
    }

    public function onRenderBalanceAmount(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['add', 'edit'])) {
            $recipientId = $page->getQueryString('recipient_id');
            $scholarshipId = $page->getQueryString('scholarship_id');
            $recipientEntity = $this->ScholarshipRecipients->get(['recipient_id' => $recipientId, 'scholarship_id' => $scholarshipId]);

            if (!empty($recipientEntity->approved_amount)) {
                $approvedAmount = $recipientEntity->approved_amount;

                $where = [
                    $this->RecipientCollections->aliasField('recipient_id') => $recipientId,
                    $this->RecipientCollections->aliasField('scholarship_id') => $scholarshipId
                ];
                if ($entity->has('id')) {
                    $where[$this->RecipientCollections->aliasField('id <> ')] = $entity->id;
                }

                $amountUsed = $this->RecipientCollections->find()
                    ->select(['total' => $this->RecipientCollections->find()->func()->sum('amount')])
                    ->where($where)
                    ->first();

                $balance = $approvedAmount - $amountUsed->total;
                return $balance;
            }
        }
    }

    public function setupTabElements($scholarshipId)
    {
        $page = $this->Page;
        $tabElements = $this->ScholarshipTabs->getScholarshipRecipientTabs();

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }

        // set active tab
        $page->getTab('Collections')->setActive('true');
    }

    public function addCurrencySuffix($label)
    {
        return __($label) . ' (' . $this->currency . ')';
    }
}
