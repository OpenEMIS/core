<?php
namespace Profile\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Controller\Component;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Log\Log;


class InstitutionChoicesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('scholarship_application_institution_choices');
        parent::initialize($config);

        $this->belongsTo('Applications', ['className' => 'Scholarship.Applications', 'foreignKey' => ['applicant_id', 'scholarship_id']]);
        $this->belongsTo('Countries', ['className' => 'FieldOption.Countries', 'foreignKey' => 'country_id']);
        $this->belongsTo('InstitutionChoiceTypes', ['className' => 'Scholarship.InstitutionChoiceTypes', 'foreignKey' => 'scholarship_institution_choice_type_id']);
        $this->belongsTo('InstitutionChoiceStatuses', ['className' => 'Scholarship.InstitutionChoiceStatuses', 'foreignKey' => 'scholarship_institution_choice_status_id']);
        $this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies' , 'foreignKey' => 'education_field_of_study_id']);
        $this->belongsTo('QualificationLevels', ['className' => 'FieldOption.QualificationLevels',  'foreignKey' =>'qualification_level_id' ]);
        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $reorderBehavior = $this->behaviors()->get('Reorder');
            $reorderBehavior->setConfig('filter', 'order');
        $this->toggle('add', true);
        $this->toggle('remove', true);
        $this->toggle('search', true);
        $this->toggle('edit', true); 
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
            ->where([$this->aliasField('scholarship_id IS') => $scholarshipId])
            ->order(['AcademicPeriods.name' => 'DESC']);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $encodedQueryString = $this->request->getParam('pass')[1];
        $tabElements = $this->ScholarshipTabs->getScholarshipProfileTabs();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
            $extra['toolbarButtons']['back']['url'] = [
                'plugin' => 'Profile',
                'controller' => 'Profiles',
                'action' => 'ScholarshipApplicationInstitutionChoices',
                0 => 'index',
                1 => $encodedQueryString
            ];

            $extra['toolbarButtons']['list']['url'] = [
                'plugin' => 'Profile',
                'controller' => 'Profiles',
                'action' => 'ScholarshipApplicationInstitutionChoices',
                0 => 'index',
                1 => $encodedQueryString
            ];
        
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('applicant_id', ['visible' => false]);
        $this->field('scholarship_id', ['visible' => false]);
        $this->field('start_date', ['visible' => false]);
        $this->field('end_date', ['visible' => false]);
        $this->field('scholarship_institution_choice_type_id', ['attr' => ['label' => __('Institution')]]);
        $this->field('scholarship_institution_choice_status_id', ['type' => 'select','attr' => ['label' => __('Status')]]);
        $this->field('location_type', ['type' => 'select','attr' => ['label' => __('LocationType')]]);
        $this->field('is_selected', ['visible' => false]);
        
        $this->field('scholarship_institution_choice_types', ['visible' => false]);
        $this->field('estimated_cost', ['visible' => false]);
        
        $this->setFieldOrder(['location_type', 'country_id', 'scholarship_institution_choice_type_id','scholarship_institution_choice_status_id'
            ,'education_field_of_study_id','course_name', 'qualification_level_id']);

    }

    public function onGetBreadcrumb(Event $event, ServerRequest $request, Component $Navigation, $persona)
    {   
        $this->Navigation->substituteCrumb($this->getHeader($this->getAlias()), __('Institution Choice'));
    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        return $entity->scholarship->academic_period->name;
    }

    /*public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        if (isset($extra['toolbarButtons']['back']['url'])) {
            $extra['toolbarButtons']['back']['url'] = [
                'plugin' => 'Profile',
                'controller' => 'Profiles',
                'action' => 'ScholarshipApplicationInstitutionChoices',
                0 => 'index',
                1 => $encodedQueryString
            ];
        }
    }*/

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'scholarship_institution_choice_type_id') {
            return __('Institution');
        }else if ($field == 'scholarship_institution_choice_status_id') {
            return __('Status');
        } else if ($field == 'education_field_of_study_id') {
            return __('Area of Study');
        } else if ($field == 'applicant_id') {
            return __('Applicant');
        }else if ($field == 'start_date') {
            return __('Commencement Date');
        }else if ($field == 'end_date') {
            return __('Completion Date');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $applicantId = $this->getQueryString('applicant_id');
        $scholarshipId = $this->getQueryString('scholarship_id');
        $locationListOptions = array(
                            'Domestic' => 'Domestic',
                            'Regional' => 'Regional',
                            'International' => 'International',
                            'Online' => 'Online',
                        );
        $this->field('location_type', [
            'type' => 'select',
            'options' => $locationListOptions,
            'empty' => 'Select' 
        ]);
        
        $this->field('country_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('scholarship_institution_choice_status_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('education_field_of_study_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('qualification_level_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('applicant_id', ['type' => 'hidden', 'entity' => $applicantId]);
        $this->field('scholarship_id', ['type' => 'hidden', 'entity' => $scholarshipId]);
        $this->field('is_selected', ['type' => 'hidden']);
        $this->field('scholarship_institution_choice_type_id', ['type' => 'select', 'entity' => $entity]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->requirePresence('country_id')
            ->requirePresence('location_type')
            ->requirePresence('scholarship_institution_choice_type_id')
            ->add('end_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'start_date', true],
                'message' => __('End Date should not be earlier than Start Date')
            ])
            ->add('estimated_cost', 'validateDecimal', [
                'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                'message' => __('Value cannot be more than two decimal places')
            ])
            ->add('scholarship_institution_choice_status_id', 'ruleCheckChoiceStatus', [
                'rule' => ['checkChoiceStatus'],
                'provider' => 'table',
                'message' => __('Please ensure that status is ACCEPTED'),
                'on' => function ($context) {
                    //trigger validation only when selection is set to 1 and edit operation
                    return (isset($context['data']['is_selected']) && $context['data']['is_selected'] == 1  && !$context['newRecord']);
                }
            ]);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        
        if ($entity->getDirty('is_selected')) {
            if ($entity->is_selected == 1) {
                $this->updateAll(
                    ['is_selected' => 0],
                    [
                        'applicant_id' => $entity->applicant_id,
                        'scholarship_id' => $entity->scholarship_id,
                        'id <> ' => $entity->id
                    ]
                 );
            } 
        }
        $encodedQueryString = $this->request->getParam('pass')[1];
        $url = [
                'plugin' => 'Profile',
                'controller' => 'Profiles',
                'action' => 'ScholarshipApplicationInstitutionChoices',
                '0' => 'index',
                 $encodedQueryString,

            ];
        return $this->controller->redirect($url);            

    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $queryString = $this->getQueryString();

        $entity->applicant_id = $queryString['applicant_id']; 
        $entity->scholarship_id = $queryString['scholarship_id']; 
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) 
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $applicantId = $this->getQueryString('applicant_id');
        $scholarshipId = $this->getQueryString('scholarship_id');
        $encodedQueryString = $this->paramsEncode(['id' => $entity->id,'applicant_id' => $applicantId,'security_user_id' =>$applicantId ,'scholarship_id' => $scholarshipId]);
        $url['plugin'] = 'Profile';
        $url['controller'] = 'Profiles';
        $url['action'] = 'ScholarshipApplicationInstitutionChoices';
        $url[0] = 'view';
        $url[1] = $encodedQueryString; 
        $buttons['view']['url'] = $url;
        if (is_null($buttons['edit'])) {
            $buttons['edit'] = [];
        }
        $buttons['edit']['url'] = [
            'plugin' => 'Profile',
            'controller' => 'Profiles',
            'action' => 'ScholarshipApplicationInstitutionChoices',  // Specify the action
            0 => 'edit',  // Specify the action
            1 => $encodedQueryString          
        ];
        // Ensure 'options' is an array
        $buttons['edit']['label'] = $buttons['edit']['label'] ?? '<i class="fa fa-edit"></i>' . __('Edit');
        $buttons['edit']['attr'] = $buttons['edit']['attr'] ?? [
            'role' => 'menuitem',
            'tabindex' => '-1',
            'escape' => false,
        ];
        $buttons['remove']['url'] = [
            'plugin' => 'Profile',
            'controller' => 'Profiles',
            'action' => 'ScholarshipApplicationInstitutionChoices',
            0 => 'remove',
            1 => $encodedQueryString
        ];
        if ($entity) {
        $encodedQueryStrings = $this->paramsEncode(['id' => $entity->id]);

        // Setup the remove button
        $buttons['remove']['url'] = [
            'plugin' => 'Profile',
            'controller' => 'Profiles',
            'action' => 'ScholarshipApplicationInstitutionChoices',
            0 => 'remove',
            1 => $encodedQueryString
        ];


        // Set label and attributes
        $buttons['remove']['label'] = $buttons['remove']['label'] ?? '<i class="fa fa-trash"></i>' . __('Delete');
        $buttons['remove']['attr'] = $buttons['remove']['attr'] ?? [
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Delete')
        ];

    } else {
        Log::error('Entity is null when setting up action buttons.');
    }
        return $buttons;

    }

    public function deleteBeforeAction(Event $event, ArrayObject $extra)
    {
        
        if($this->action == 'remove'){
            $applicantId = $this->getQueryString('applicant_id');
            $scholarshipId = $this->getQueryString('scholarship_id');
            $encodedQueryString = $this->paramsEncode(['applicant_id' => $applicantId,'scholarship_id' => $scholarshipId,'security_user_id' => $applicantId]);
             if(!empty($encodedQueryString)){
                $session = $this->request->getSession();
                $session->write('urlRequest', $encodedQueryString);
            }
            if(empty($encodedQueryString)){
                $session = $this->request->getSession();
                $encodedQueryString = $session->read('urlRequest');
            }
            $url = [
                'plugin' => 'Profile',
                'controller' => 'Profiles',
                'action' => 'ScholarshipApplicationInstitutionChoices',
                0 => 'index',
                1 => $encodedQueryString
            ];
            $extra['redirect'] = $url;
        }
        
    }

}
