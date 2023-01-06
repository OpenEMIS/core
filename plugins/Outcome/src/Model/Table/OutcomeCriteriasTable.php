<?php
namespace Outcome\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

use App\Model\Table\ControllerActionTable;

class OutcomeCriteriasTable extends ControllerActionTable
{
    private $periodId = null;
    private $templateId = null;
    private $gradeId = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('OutcomeGradingTypes', ['className' => 'Outcome.OutcomeGradingTypes', 'foreignKey' => 'outcome_grading_type_id']);
        $this->belongsTo('Templates', [
            'className' => 'Outcome.OutcomeTemplates',
            'foreignKey' => ['outcome_template_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id']
        ]);

        $this->hasMany('InstitutionOutcomeResults', [
            'className' => 'Institution.InstitutionOutcomeResults',
            'foreignKey' => ['outcome_criteria_id', 'academic_period_id', 'outcome_template_id', 'education_grade_id', 'education_subject_id'],
            'bindingKey' => ['id', 'academic_period_id', 'outcome_template_id', 'education_grade_id', 'education_subject_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentOutcomes' => ['index']
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('code')
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['validateUnique', ['scope' => ['education_subject_id', 'outcome_template_id', 'academic_period_id']]],
                'provider' => 'table'
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $queryString = $this->request->query('queryString');

        if ($queryString) {
            $this->periodId = $this->getQueryString('academic_period_id');
            $this->templateId = $this->getQueryString('outcome_template_id');

            $templateEntity = $this->Templates->get(['id' => $this->templateId, 'academic_period_id' => $this->periodId]);
            $this->gradeId = $templateEntity->education_grade_id;

            // set tabs
            $this->controller->getOutcomeTemplateTabs(['queryString' => $queryString]);

            // set header
            $header = $templateEntity->name . ' - ' . __(Inflector::humanize(Inflector::underscore($this->alias())));
            $this->controller->set('contentHeader', $header);

        } else {
            $event->stopPropagation();
            return $this->controller->redirect(['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Templates']);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('education_subject_id', ['type' => 'integer']);
        $this->setFieldOrder(['code', 'name', 'education_subject_id', 'outcome_grading_type_id']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $conditions[$this->aliasField('outcome_template_id')] = $this->templateId;
        $conditions[$this->aliasField('academic_period_id')] = $this->periodId;

        // subject filter
        $gradeId = $this->gradeId;

        $subjectOptions = $this->EducationSubjects
            ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
            ->innerJoinWith('EducationGrades', function ($q) use ($gradeId) {
                return $q->where(['EducationGrades.id' => $gradeId]);
            })
            ->order([$this->EducationSubjects->aliasField('order')])
            ->toArray();
        $subjectOptions = ['0' => '-- '.__('All Subjects').' --'] + $subjectOptions;

        $selectedSubject = !is_null($this->request->query('subject')) ? $this->request->query('subject') : 0;
        if (!empty($selectedSubject)){
            $conditions[$this->aliasField('education_subject_id')] = $selectedSubject;
        }
        $this->controller->set(compact('subjectOptions', 'selectedSubject'));

        // set baseUrl for filter (to maintain queryString)
        $baseUrl = $this->url('index');
        if (isset($baseUrl['subject'])) {
            unset($baseUrl['subject']);
        }
        $this->controller->set('baseUrl', $baseUrl);

        $extra['elements']['controls'] = ['name' => 'Outcome.criterias_controls', 'data' => [], 'options' => [], 'order' => 1];
        $extra['auto_contain_fields'] = ['EducationSubjects' => ['code']];

        $query->where($conditions);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('education_subject_id', ['type' => 'integer']);
        $this->setFieldOrder(['code', 'name', 'education_subject_id', 'outcome_grading_type_id']);
    }

    public function onGetEducationSubjectId(Event $events, Entity $entity)
    {
        $value = '';
        if ($entity->has('education_subject')) {
            $value = $entity->education_subject->code_name;
        }
        return $value;
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->request->query('criteriaForm')) {
            $this->request->data[$this->alias()]['education_subject_id'] = $this->getQueryString('education_subject_id', 'criteriaForm');
            $this->request->data[$this->alias()]['name'] = $this->getQueryString('name', 'criteriaForm');
            $this->request->data[$this->alias()]['code'] = $this->getQueryString('code', 'criteriaForm');
            $this->request->data[$this->alias()]['outcome_grading_type_id'] = $this->getQueryString('outcome_grading_type_id', 'criteriaForm');
        }
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        if ($toolbarButtons->offsetExists('back')) {
            if (isset($toolbarButtons['back']['url']['criteriaForm'])) {
                unset($toolbarButtons['back']['url']['criteriaForm']);
            }
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id');
        $this->field('outcome_template_id');
        $this->field('education_subject_id', ['entity' => $entity]);
        $this->field('name', ['type' => 'text']);
        $this->field('outcome_grading_type_id', ['entity' => $entity]);
        $this->field('education_grade_id', ['type' => 'hidden', 'value' => $this->gradeId]);

        $this->setFieldOrder([
            'academic_period_id', 'outcome_template_id', 'education_subject_id', 'code', 'name', 'outcome_grading_type_id'
        ]);
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $url = $this->url('index');
        if (isset($url['criteriaForm'])) {
            unset($url['criteriaForm']);
        }
        $extra['redirect'] = $url;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $this->periodId;
            $attr['attr']['value'] = $this->AcademicPeriods->get($this->periodId)->name;
        }
        return $attr;
    }

    public function onUpdateFieldOutcomeTemplateId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $this->templateId;
            $attr['attr']['value'] = $this->Templates->get(['id' => $this->templateId, 'academic_period_id' => $this->periodId])->code_name;
        }
        return $attr;
    }

    public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $gradeId = $this->gradeId;

            $subjectOptions = $this->EducationSubjects
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->innerJoinWith('EducationGrades', function ($q) use ($gradeId) {
                    return $q->where(['EducationGrades.id' => $gradeId]);
                })
                ->toArray();

            $attr['type'] = 'select';
            $attr['options'] = $subjectOptions;

        } else if ($action == 'edit') {
            $subjectId = $attr['entity']->education_subject_id;

            $attr['type'] = 'readonly';
            $attr['value'] = $subjectId;
            $attr['attr']['value'] = $this->EducationSubjects->get($subjectId)->code_name;
        }
        return $attr;
    }

    public function onUpdateFieldOutcomeGradingTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $defaultOptions = ['' => '-- '.__('Select').' --'];

            // only allow createNew in add
            if ($action == 'add') {
                $defaultOptions['createNew'] = '-- ' . __('Create New') . ' --';
                $attr['onChangeReload'] = 'changeGradingType';
            }

            $gradingTypeOptions = $this->OutcomeGradingTypes
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->toArray();
            $options = $defaultOptions + $gradingTypeOptions;

            $attr['options'] = $options;
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
        }
        return $attr;
    }

    public function addOnChangeGradingType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $competencyGradingTypeId = $data[$this->alias()]['outcome_grading_type_id'];

        if ($competencyGradingTypeId == 'createNew') {
            $criteriaParams = [
                'education_subject_id' => $data[$this->alias()]['education_subject_id'],
                'name' => $data[$this->alias()]['name'],
                'code' => $data[$this->alias()]['code']
            ];

            // redirect to GradingTypes add page
            $url = $this->url('add');
            $url['action'] = 'GradingTypes';
            $url = $this->setQueryString($url, $criteriaParams, 'criteriaForm');

            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
    }
}
