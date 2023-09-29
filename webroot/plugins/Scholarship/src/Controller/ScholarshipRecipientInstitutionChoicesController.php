<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;
use App\Model\Traits\OptionsTrait;
use Scholarship\Controller\InstitutionChoicesController as BaseController;

class ScholarshipRecipientInstitutionChoicesController extends BaseController
{
    use OptionsTrait;

    private $isSelectedOptions = [];

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Scholarship.InstitutionChoiceStatuses');      
        $this->isSelectedOptions = $this->getSelectOptions('general.yesno');
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderIsSelected'] = 'onRenderIsSelected';
        return $event;
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        if (isset($this->request->query['queryString'])) {
            $queryString = $this->request->query['queryString'];

            $recipientId = $this->paramsDecode($queryString)['recipient_id'];
            $scholarshipId = $this->paramsDecode($queryString)['scholarship_id'];
            $userName = $this->Users->get($recipientId)->name;

            parent::beforeFilter($event);

            // set header
            $page->setHeader($userName . ' - ' . __('Institution Choices'));

            $page->setQueryString('applicant_id', $recipientId);
            $page->setQueryString('scholarship_id', $scholarshipId);

            $page->get('applicant_id')->setControlType('hidden')->setValue($recipientId);
            $page->get('scholarship_id')->setControlType('hidden')->setValue($scholarshipId);

            $this->setBreadCrumb(['userName' => $userName]);
            $this->setupTabElements();
        }

        $page->disable(['add', 'delete', 'reorder']);
    }

    public function edit($id)
    {
        $page = $this->Page;
        parent::edit($id);

        $page->get('location_type')
            ->setControlType('string')
            ->setDisabled(true);

        $page->get('country_id')
            ->setControlType('string')
            ->setDisabled(true);

        $page->get('scholarship_institution_choice_type_id')
            ->setControlType('string')
            ->setDisabled(true);

        $page->get('estimated_cost')
            ->setDisabled(true);

        $page->get('course_name')
            ->setDisabled(true);

        $page->get('education_field_of_study_id')
            ->setControlType('string')
            ->setDisabled(true);

        $page->get('qualification_level_id')
            ->setControlType('string')
            ->setDisabled(true);

        $page->get('start_date')
            ->setDisabled(true);

        $page->get('end_date')
            ->setDisabled(true);

        $entity = $page->getData();
        $this->renderSelection($entity);
    }

    public function renderSelection(Entity $entity) 
    {
        $page = $this->Page;

        $statusId = $entity->scholarship_institution_choice_status_id;

        $institutionChoiceStatusesOptions = $this->InstitutionChoiceStatuses
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'code'
            ])
            ->order([$this->InstitutionChoiceStatuses->aliasField('id')])
            ->toArray();

        if ($institutionChoiceStatusesOptions[$statusId] == 'ACCEPTED') {
            $page->get('is_selected')
                ->setControlType('select')
                ->setOptions($this->isSelectedOptions);
        } else {
            $page->exclude(['is_selected']);
        }
    }

    public function onRenderIsSelected(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view', 'delete'])) {
            $value = $this->isSelectedOptions[$entity->is_selected];
            return $value;
        }
    }
}
