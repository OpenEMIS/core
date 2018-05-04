<?php
namespace Profile\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class ScholarshipApplicationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Applicants', ['className' => 'Security.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->hasMany('InstitutionChoices', ['className' => 'Scholarship.InstitutionChoices', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('Workflow.Workflow', ['model' => 'Scholarship.ScholarshipApplications']);

        $this->toggle('edit', false);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('requested_amount', ['visible' => false]);
        $this->field('academic_period_id');
        $this->field('scholarship_id', ['type' => 'string']);
        $this->field('financial_assistance_type_id');
        $this->setFieldOrder(['status_id', 'assignee_id', 'academic_period_id', 'scholarship_id', 'financial_assistance_type_id']);

        if ($extra['toolbarButtons']->offsetExists('add')) {
            $extra['toolbarButtons']['add']['url'] = [
                'plugin' => 'Profile',
                'controller' => 'ScholarshipDirectories',
                'action' => 'index'
            ];
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Scholarships' => ['AcademicPeriods', 'FinancialAssistanceTypes']]);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $queryString = $this->ControllerAction->getQueryString();

        if (array_key_exists('scholarship_id', $queryString) && !empty($queryString['scholarship_id'])) {
            $scholarshipId = $queryString['scholarship_id'];
            $applicantId = $this->Auth->user('id');

            $scholarshipDetails = $this->Scholarships->get($scholarshipId, ['contain' => ['FinancialAssistanceTypes']]);
            $financialAssistanceTypeCode = $scholarshipDetails->financial_assistance_type->code;

            if ($financialAssistanceTypeCode != 'LOAN') {
                $application = [];
                $application['applicant_id'] = $applicantId;
                $application['scholarship_id'] = $scholarshipId;
                $application['status_id'] = 0;
                $entity = $this->newEntity($application);

                if ($this->save($entity)) {
                    $this->Alert->success('general.add.success', ['reset' => true]);
                } else {
                    $this->Alert->error('general.add.failed', ['reset' => true]);
                }

                $event->stopPropagation();
                return $this->controller->redirect($this->url('index', false));

            } else {
                $this->field('scholarship_id', ['type' => 'readonly', 'value' => $scholarshipId, 'attr' => ['value' => $scholarshipDetails->code_name]]);
                $this->field('applicant_id', ['type' => 'hidden', 'value' => $applicantId]);
                $this->field('requested_amount', ['type' => 'float']);
            }
        } else {
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index', false));
        }
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('requested_amount', ['visible' => false]);
        $this->field('academic_period_id');
        $this->field('code');
        $this->field('scholarship_id', ['type' => 'string']);
        $this->field('financial_assistance_type_id');
        $this->field('description');
        $this->field('max_award_amount');
        $this->field('bond');
        $this->field('requirement', ['type' =>'text']);
        $this->field('instruction', ['type' =>'text']);

        $this->setFieldOrder([
            'academic_period_id', 'code', 'scholarship_id', 'financial_assistance_type_id', 'description', 'max_award_amount', 'bond', 'requirement', 'instruction'
        ]);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Scholarships' => ['AcademicPeriods', 'FinancialAssistanceTypes']]);
    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship') && $entity->scholarship->has('academic_period')) {
            $value = $entity->scholarship->academic_period->name;
        }
        return $value;
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->code;
        }
        return $value;
    }

    public function onGetScholarshipId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->name;
        }
        return $value;
    }

    public function onGetDescription(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->description;
        }
        return $value;
    }

    public function onGetFinancialAssistanceTypeId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship') && $entity->scholarship->has('financial_assistance_type')) {
            $value = $entity->scholarship->financial_assistance_type->name;
        }
        return $value;
    }

    public function onGetMaxAwardAmount(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->max_award_amount;
        }
        return $value;
    }

    public function onGetBond(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->bond;
        }
        return $value;
    }

    public function onGetRequirement(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->requirement;
        }
        return $value;
    }

    public function onGetInstruction(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('scholarship')) {
            $value = $entity->scholarship->instruction;
        }
        return $value;
    }
}
