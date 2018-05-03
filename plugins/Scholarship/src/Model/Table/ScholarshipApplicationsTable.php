<?php
namespace Scholarship\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\Controller\Component;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\Utility\Security;
use Cake\ORM\ResultSet;

class ScholarshipApplicationsTable extends ControllerActionTable
{
    private $applicantName = null;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Applicants', ['className' => 'Security.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->hasMany('InstitutionChoices', ['className' => 'Scholarship.InstitutionChoices','dependent' => true, 'cascadeCallbacks' => true]);
        
        $this->addBehavior('Workflow.Workflow');
    
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator;
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'view' || $this->action == 'edit') {    
            $applicantId = $this->ControllerAction->getQueryString('applicant_id');
            $scholarshipId = $this->ControllerAction->getQueryString('scholarship_id');
            $this->request->params['pass'][1] = $this->paramsEncode(['applicant_id' => $applicantId,'scholarship_id' => $scholarshipId]);

            $this->applicantName = $this->Applicants->get($applicantId)->name;
        }

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'Applicants.Genders',
            'Applicants.IdentityTypes',
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add']['url'])) {
            $extra['toolbarButtons']['add']['url']['controller'] = 'ScholarshipApplicationDirectories';
            $extra['toolbarButtons']['add']['url']['action'] = 'index';
            $extra['toolbarButtons']['add']['attr']['title'] = __('Apply');
            unset($extra['toolbarButtons']['add']['url'][0]);
        }

        $this->setupFields();
    }


    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {   
        $query->contain([
            'Scholarships.AcademicPeriods',
            'Scholarships.FinancialAssistanceTypes'
        ]);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        // Set the header of the page
        $this->controller->set('contentHeader', $this->applicantName. ' - ' .__('Overview'));

        $tabElements = $this->controller->getScholarshipTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
       
        $this->setupFields();
    }


    public function onGetOpenemisNo(Event $event, Entity $entity) 
    {
        if ($this->action == 'index') {
            return $entity->applicant->openemis_no;
        }
    }

    public function onGetStudentId(Event $event, Entity $entity) 
    {   
        if ($this->action == 'index') {
            return $entity->applicant->name;
        }
    }

    public function onGetDateOfBirth(Event $event, Entity $entity) 
    {
        if ($this->action == 'index') {
            return $this->formatDate($entity->applicant->date_of_birth);
        }
    }

    public function onGetGenderId(Event $event, Entity $entity) 
    {
        if ($this->action == 'index') {
            return $entity->applicant->gender->name;
        }
    }

    public function onGetIdentityType(Event $event, Entity $entity) 
    {
        if ($this->action == 'index') {
            return $entity->applicant->identity_type_id;
        }
    }

    public function onGetIdentityNumber(Event $event, Entity $entity) 
    {
        if ($this->action == 'index') {
            return $entity->applicant->identity_number;
        }
    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity) 
    { 
        if ($this->action == 'view') {
            return $entity->scholarship->academic_period->name;
        }
    }

    public function onGetCode(Event $event, Entity $entity) 
    { 
        if ($this->action == 'view') {
            return $entity->scholarship->code;
        }    
    }

    public function onGetScholarshipName(Event $event, Entity $entity) 
    {
        if ($this->action == 'view') {
            return $entity->scholarship->name;
        }
    }

    public function onGetFinancialAssistanceTypeId(Event $event, Entity $entity) 
    {
        if ($this->action == 'view') {
            return $entity->scholarship->financial_assistance_type->name;
        }
    }

    public function onGetDescription(Event $event, Entity $entity) 
    {
        if ($this->action == 'view') {
          return $entity->scholarship->description;
        }
    }

    public function onGetMaxAwardAmount(Event $event, Entity $entity) 
    {
        if ($this->action == 'view') {
            return $entity->scholarship->max_award_amount;
        }
    }

    public function onGetBond(Event $event, Entity $entity) 
    {
        if ($this->action == 'view') {
            return $entity->scholarship->bond.' Years';
        }
    }

    public function onGetRequirement(Event $event, Entity $entity) 
    {
        if ($this->action == 'view') {
          return $entity->scholarship->requirement;
        }
    }

    public function onGetInstruction(Event $event, Entity $entity) 
    {
        if ($this->action == 'view') {
            return $entity->scholarship->instruction;
        }
    }
     
    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        if ($this->action == 'add') {
            $Navigation->substituteCrumb('Applicants', 'Single Application');
        } 
    }


    public function setupFields()
    {
        $this->field('requested_amount', ['visible' => false]);
        $this->field('assignee_id', ['visible' => false]);

        if ($this->action == 'index') {
            $this->field('openemis_no');
            $this->field('student_id');
            $this->field('date_of_birth');
            $this->field('gender_id');
            $this->field('identity_type_id');
            $this->field('identity_number');  

            $this->setFieldOrder([
                'status_id', 'openemis_no', 'student', 'date_of_birth', 'gender_id', 'identity_type_id', 'identity_number'
            ]);    

        } else if ($this->action == 'view') {
            
            $this->field('modified_user_id', ['visible' => false]);
            $this->field('modified', ['visible' => false]);
            $this->field('created_user_id', ['visible' => false]);
            $this->field('created', ['visible' => false]);
            $this->field('academic_period_id');
            $this->field('code');
            $this->field('scholarship_name');
            $this->field('financial_assistance_type_id');
            $this->field('description');
            $this->field('max_award_amount');
            $this->field('bond');
            $this->field('requirement');
            $this->field('instruction');

            $this->setFieldOrder([
                'academic_period_id', 'status_id', 'code', 'scholarship_name', 'financial_assistance_type_id', 'description', 'max_award_amount', 'bond', 'requirement', 'instruction'
            ]);   
        }        
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {

        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        
        $params = [
                'applicant_id' => $entity->applicant_id,
                'scholarship_id' => $entity->scholarship_id
            ];

        if (isset($buttons['view']['url'])) {
            $buttons['view']['url'] = $this->ControllerAction->setQueryString($buttons['view']['url'], $params);
        }

        return $buttons;
    }
}
