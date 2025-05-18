<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Controller\Component;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;

class HistoriesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('scholarship_applications');
        parent::initialize($config);

        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->hasMany('ApplicationAttachments', [
            'className' => 'Scholarship.ApplicationAttachments',
            'foreignKey' => ['applicant_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('ApplicationInstitutionChoices', [
            'className' => 'Scholarship.ApplicationInstitutionChoices',
            'foreignKey' => ['applicant_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Workflow.Workflow', [
            'model' => 'Scholarship.Applications',
            'actions' => [
                'add' => false,
                'remove' => false,
                'edit' => false
            ],
            'disableWorkflow' => true
        ]);
        $this->addBehavior('CompositeKey');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryString  = $this->getQueryString('scholarship_id');
        $scholarshipId = $queryString;

        $query
            ->contain(['Scholarships.AcademicPeriods'])
            ->where([$this->aliasField('scholarship_id').' <> ' => $scholarshipId])
            ->order(['AcademicPeriods.name' => 'DESC']);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $applicantId = $this->getQueryString('applicant_id');
        $applicantName = $this->Applicants->get($applicantId)->name;
        $this->controller->set('contentHeader', $applicantName. ' - ' .__('Scholarship History'));

        $tabElements = $this->ScholarshipTabs->getScholarshipApplicationTabs();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('requested_amount', ['visible' => false]);
        $this->field('assignee_id', ['visible' => false]);
        $this->field('scholarship_id', ['type' => 'string']);
        $this->field('academic_period_id');
        $this->setFieldOrder(['academic_period_id', 'scholarship_id', 'comments']);
    }

    public function onGetBreadcrumb(Event $event, ServerRequest $request, Component $Navigation, $persona)
    {   
        $this->Navigation->substituteCrumb($this->getHeader($this->getAlias()), __('Scholarship History'));
    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        return $entity->scholarship->academic_period->name;
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        if (isset($extra['toolbarButtons']['back']['url'])) {
            $extra['toolbarButtons']['back']['url'] = [
                'plugin' => 'Scholarship',
                'controller' => 'Scholarships',
                'action' => 'Histories',
                0 => 'index',
                1 => $encodedQueryString
            ];
        }
    }
}
