<?php
namespace ReportCard\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class ReportCardEmailTable extends ControllerActionTable
{
    private $alertTypeFeatures = [];

	public function initialize(array $config)
    {
        $this->table('report_cards');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('ReportCardSubjects', ['className' => 'ReportCard.ReportCardSubjects', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']);
        $this->hasMany('StudentReportCards', ['className' => 'Institution.InstitutionStudentsReportCards', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('ReportCard.EmailTemplate', [
            'placeholder' => [
                '${student.openemis_no}' => 'Student OpenEMIS ID.',
                '${student.name}' => 'Student full name.',
                '${student.first_name}' => 'Student first name.',
                '${student.middle_name}' => 'Student middle name.',
                '${student.third_name}' => 'Student third name.',
                '${student.last_name}' => 'Student last name.',
                '${student.preferred_name}' => 'Student preferred name.',
                '${student.address}' => 'Student address.',
                '${student.postal_code}' => 'Student postal code.',
                '${student.date_of_birth}' => 'Student date of birth.',
                '${student.identity_number}' => 'Student identity number.',
                '${student.main_identity_type.name}' => 'Student identity type.',
                '${student.main_nationality.name}' => 'Student nationality.',
                '${institution.code}' => 'Institution code.',
                '${institution.name}' => 'Institution name.',
                '${institution.contact_person}' => 'Institution contact person.',
                '${institution.telephone}' => 'Institution telephone number.',
                '${institution.email}' => 'Institution email.',
                '${academic_period.code}' => 'Academic period code.',
                '${academic_period.name}' => 'Academic period name.',
                '${education_grade.code}' => 'Education grade code.',
                '${education_grade.name}' => 'Education grade name.',
            ]
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('academic_period_id')
            ->allowEmpty('start_date')
            ->allowEmpty('end_date')
            ->allowEmpty('education_grade_id');
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (array_key_exists('back', $toolbarButtonsArray)) {
            $encodedParam = $this->request->params['pass'][1];

            $backUrl = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'Templates',
                'view',
                $encodedParam
            ];

            $toolbarButtonsArray['back']['url'] = $backUrl;
        }
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $this->setupTabElements($entity);
        $this->setupFields($event, $entity);
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'AcademicPeriods' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'EducationGrades' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ]
            ]);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);
        $this->setupFields($event, $entity);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->academic_period->name;
        }

        return $attr;
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $startDate = $this->formatDate($entity->start_date);
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->start_date->format('Y-m-d');
            $attr['attr']['value'] = $startDate;
        }

        return $attr;
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $endDate = $this->formatDate($entity->end_date);
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->end_date->format('Y-m-d');
            $attr['attr']['value'] = $endDate;
        }

        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->education_grade_id;
            $attr['attr']['value'] = $entity->education_grade->name;
        }

        return $attr;
    }

    private function setupTabElements($entity)
    {
        $tabElements = $this->controller->getReportCardTab($entity->id);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    private function setupFields(Event $event, Entity $entity)
    {
        $this->field('report_card_information', ['type' => 'section']);
        $this->field('code', ['type' => 'readonly', 'attr' => ['required' => false]]);
        $this->field('name', ['type' => 'readonly', 'attr' => ['required' => false]]);
        $this->field('description', ['attr' => ['disabled' => 'disabled']]);
        $this->field('academic_period_id', ['entity' => $entity]);
        
        $this->field('start_date', ['entity' => $entity]);
        $this->field('end_date', ['entity' => $entity]);

        $this->field('education_grade_id', ['entity' => $entity]);
        $this->field('principal_comments_required', ['visible' => false]);
        $this->field('homeroom_teacher_comments_required', ['visible' => false]);
        $this->field('teacher_comments_required', ['visible' => false]);
        $this->field('excel_template_name', ['visible' => false]);
        $this->field('excel_template', ['visible' => false]);

        $this->setFieldOrder([
            'report_card_information',
            'code',
            'name',
            'description',
            'academic_period_id',
            'start_date',
            'end_date',
            'education_grade_id'
        ]);
    }
}
