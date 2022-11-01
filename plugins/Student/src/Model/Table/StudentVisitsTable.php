<?php
namespace Student\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;

class StudentVisitsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_visits');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Evaluator', ['className' => 'Security.Users']);
        $this->belongsTo('Students', ['className' => 'Security.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('StudentVisitTypes', ['className' => 'Student.StudentVisitTypes']);
        $this->belongsTo('StudentVisitPurposeTypes', ['className' => 'Student.StudentVisitPurposeTypes']);

        $this->addBehavior('Student.StudentVisit');
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []]
                ]
            ])
            ->allowEmpty('file_content');
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'student_visit_type_id':
                return __('Visit Type');
            case 'student_visit_purpose_type_id':
                return __('Purpose');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Academic Periods Filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();

        $query->where([
            $this->aliasField('academic_period_id') => $selectedAcademicPeriod
        ]);
        
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $extra['elements']['controls'] = ['name' => 'Student.Visits/controls', 'data' => [], 'options' => [], 'order' => 1];
        // Academic Periods Filter - END
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (is_null($this->request->query('academic_period_id'))) {
            $currentAcademicPeriod = $this->AcademicPeriods->getCurrent();
            $url = $this->ControllerAction->url($this->alias());
            $url['academic_period_id'] = $currentAcademicPeriod;
            $this->controller->redirect($url);
        }

        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('institution_id', ['visible' => false]);
        $this->setFieldOrder(['referrer_id', 'referrer_type_id', 'date', 'reason_type_id']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            if ($entity->has('academic_period_id')) {
                $selectedAcademicPeriodId = $entity->academic_period_id;
            } else {
                $academicPeriodQueryString = $this->request->query('academic_period_id');
                if (!is_null($academicPeriodQueryString) && $this->AcademicPeriods->exists($academicPeriodQueryString)) {
                    $selectedAcademicPeriodId = $academicPeriodQueryString;
                } else {
                    $selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
                }
            }

            $academicPeriodName = $this->AcademicPeriods
                ->get($selectedAcademicPeriodId)
                ->name;

            $attr['type'] = 'readonly';
            $attr['value'] = $selectedAcademicPeriodId;
            $attr['attr']['value'] = $academicPeriodName;

            return $attr;
        }
    }

    public function onUpdateFieldEvaluatorId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $userId = $this->Session->read('Auth.User.id');
            $userName = $this->Session->read('Auth.User.name');

            if (!is_null($userId) && !is_null($userName)) {
                $attr['type'] = 'readonly';
                $attr['value'] = $userId;
                $attr['attr']['value'] = $userName;
            }

            return $attr;
        }
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $institutionId = $this->Session->read('Institution.Institutions.id');

            if (!is_null($institutionId)) {
                $attr['type'] = 'hidden';
                $attr['value'] = $institutionId;

                return $attr;
            }
        }
    }

    public function onGetEvaluatorId(Event $event, Entity $entity)
    {
        if ($this->action == 'view' || $this->action == 'index') {
            if ($entity->has('evaluator_id')) {
                return $entity->evaluator->name_with_id;
            }
        } 
    }

    private function setupFields($entity = null)
    {
        $this->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('date');
        $this->field('evaluator_id');
        $this->field('student_visit_type_id', ['type' => 'select']);
        $this->field('student_visit_purpose_type_id', ['type' => 'select']);
        $this->field('comment', ['type' => 'text']);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment'), 'required' => true], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('institution_id', ['type' => 'hidden']);

        $this->setFieldOrder(['academic_period_id', 'date', 'evaluator_id', 'student_visit_type_id', 'student_visit_purpose_type_id', 'comment', 'file_name', 'file_content']);
    }
}

