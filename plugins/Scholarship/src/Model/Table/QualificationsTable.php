<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Controller\Component;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;

/**
 * POCOR-8151
 * Initialize method for QualificationsTable.
 * @return void
 */

class QualificationsTable extends ControllerActionTable
{
     public function initialize(array $config): void
    {
        $this->setTable('staff_qualifications');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('QualificationTitles', ['className' => 'FieldOption.QualificationTitles']);
        $this->belongsTo('QualificationCountries', ['className' => 'FieldOption.Countries', 'foreignKey' => 'qualification_country_id']);
        $this->belongsTo('FieldOfStudies', ['className' => 'Education.EducationFieldOfStudies', 'foreignKey' => 'education_field_of_study_id']);

        $this->belongsToMany('EducationSubjects', [
            'className' => 'Education.EducationSubjects',
            'joinTable' => 'staff_qualifications_subjects',
            'foreignKey' => 'staff_qualification_id',
            'targetForeignKey' => 'education_subject_id',
            'through' => 'Staff.QualificationsSubjects',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsToMany('QualificationSpecialisations', [
            'className' => 'FieldOption.QualificationSpecialisations',
            'joinTable' => 'staff_qualifications_specialisations',
            'foreignKey' => 'staff_qualification_id',
            'targetForeignKey' => 'qualification_specialisation_id',
            'through' => 'Staff.QualificationsSpecialisations',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

         $this->behaviors()->get('ControllerAction')->setConfig(
            'actions.download.show',
            true
        );

    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $applicantId = $queryString['applicant_id'];
        $query->where(['staff_id' => $applicantId]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $applicantId = $this->getQueryString('applicant_id');
        $applicantName = $this->Users->get($applicantId)->name;
        $this->controller->set('contentHeader', $applicantName. ' - ' .__('Qualifications'));

        $tabElements = $this->ScholarshipTabs->getScholarshipApplicationTabs();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }

    public function indexBeforeAction(EventInterface $event)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['type' => 'binary', 'visible' => false]);
        $this->field('gpa', ['visible' => false]);
        $this->field('qualification_country_id', ['visible' => false]);
        $this->field('qualification_level', ['type' => 'string','sort'=>['field'=>'QualificationLevels.name']]);
       // $this->field('file_type', ['type' => 'string']);
        $this->setFieldOrder([
            'graduate_year', 'qualification_level', 'qualification_title_id', 'document_no', 'qualification_institution'
        ]);
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona)
    {   
        $this->Navigation->substituteCrumb($this->getHeader($this->getAlias()), __('Qualifications'));
    }

    public function onGetAcademicPeriodId(EventInterface $event, Entity $entity)
    {
        return $entity->scholarship->academic_period->name;
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        if (isset($extra['toolbarButtons']['back']['url'])) {
            $extra['toolbarButtons']['back']['url'] = [
                'plugin' => 'Scholarship',
                'controller' => 'Scholarships',
                'action' => 'Qualifications',
                0 => 'index',
                1 => $encodedQueryString
            ];
        }
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $applicantId = $this->getQueryString('applicant_id') ?? $entity->staff_id ?? null;
        $scholarshipId = $this->getQueryString('scholarship_id');

        $encodedQueryString = $this->paramsEncode([
            'id' => $entity->id,
            'security_user_id' => $applicantId,
            'applicant_id' => $applicantId,
            'scholarship_id' => $scholarshipId
        ]);

        $url = [
            'plugin' => 'Scholarship',
            'controller' => 'Scholarships',
            'action' => 'Qualifications',
            'view',
            $encodedQueryString
        ];

        $buttons['view']['url'] = $url;

        return $buttons;
    }

    public function onGetQualificationLevel(EventInterface $event, Entity $entity)
    {
        $qualificationLevelsTable = \Cake\ORM\TableRegistry::getTableLocator()->get('FieldOption.QualificationLevels');

        $result = $qualificationLevelsTable
            ->find()
            ->select(['qualification_level' => 'QualificationLevels.name'])
            ->innerJoin(
                ['QualificationTitles' => 'qualification_titles'],
                ['QualificationTitles.qualification_level_id = QualificationLevels.id']
            )
            ->where([
                'QualificationTitles.id' => $entity->qualification_title_id
            ])
            ->first();

        return $result ? $result->qualification_level : '';
    }

    
    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $showFunc = function () use ($entity) {
            return !empty($entity->file_content);
        };

        $this->behaviors()
            ->get('ControllerAction')
            ->setConfig('actions.download.show', $showFunc);

        if ($extra['toolbarButtons']->offsetExists('download')) {

            $applicantId = $this->getQueryString('applicant_id');
            $scholarshipId = $this->getQueryString('scholarship_id');

            $encoded = $this->paramsEncode([
                'id' => $entity->id,
                'security_user_id' => $applicantId,
                'applicant_id' => $applicantId,
                'scholarship_id' => $scholarshipId
            ]);

            $extra['toolbarButtons']['download']['url'] = [
                'plugin' => 'Scholarship',
                'controller' => 'Scholarships',
                'action' => 'Qualifications',
                'download',
                $encoded
            ];
        }
        if ($extra['toolbarButtons']->offsetExists('download') && empty($entity->file_content)) {
            $extra['toolbarButtons']['download'] = false;
        }

         $this->field('file_content', ['visible' => false]);
    }


}
