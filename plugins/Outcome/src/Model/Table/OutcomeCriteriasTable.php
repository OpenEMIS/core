<?php
namespace Outcome\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;

class OutcomeCriteriasTable extends ControllerActionTable
{
    private $periodId = null;
    private $templateId = null;
    private $gradeId = null;

    public function initialize(array $config): void
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

    public function validationDefault(Validator $validator): Validator
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
        $queryString = $this->request->getQuery('queryString');

        if ($queryString) {
            $this->periodId = $this->getQueryString('academic_period_id');
            $this->templateId = $this->getQueryString('outcome_template_id');

            $templateEntity = $this->Templates->get(['id' => $this->templateId, 'academic_period_id' => $this->periodId]);
            $this->gradeId = $templateEntity->education_grade_id;

            // set tabs
            $this->controller->getOutcomeTemplateTabs(['queryString' => $queryString]);

            // set header
            $header = $templateEntity->name . ' - ' . __(Inflector::humanize(Inflector::underscore($this->getAlias())));
            $this->controller->set('contentHeader', $header);

        } else {
            $event->stopPropagation();
            return $this->controller->redirect(['plugin' => $this->controller->getPlugin(), 'controller' => $this->controller->getName(), 'action' => 'Templates']);
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

        $selectedSubject = !is_null($this->request->getQuery('subject')) ? $this->request->getQuery('subject') : 0;
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
        if ($this->request->getQuery('criteriaForm')) {
            $this->request->data[$this->getAlias()]['education_subject_id'] = $this->getQueryString('education_subject_id', 'criteriaForm');
            $this->request->data[$this->getAlias()]['name'] = $this->getQueryString('name', 'criteriaForm');
            $this->request->data[$this->getAlias()]['code'] = $this->getQueryString('code', 'criteriaForm');
            $this->request->data[$this->getAlias()]['outcome_grading_type_id'] = $this->getQueryString('outcome_grading_type_id', 'criteriaForm');
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

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $this->periodId;
            $attr['attr']['value'] = $this->AcademicPeriods->get($this->periodId)->name;
        }
        return $attr;
    }

    public function onUpdateFieldOutcomeTemplateId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $this->templateId;
            $attr['attr']['value'] = $this->Templates->get(['id' => $this->templateId, 'academic_period_id' => $this->periodId])->code_name;
        }
        return $attr;
    }

    public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, $request)
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

    public function onUpdateFieldOutcomeGradingTypeId(Event $event, array $attr, $action, ServerRequest $request)
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
        $competencyGradingTypeId = $data[$this->getAlias()]['outcome_grading_type_id'];

        if ($competencyGradingTypeId == 'createNew') {
            $criteriaParams = [
                'education_subject_id' => $data[$this->getAlias()]['education_subject_id'],
                'name' => $data[$this->getAlias()]['name'],
                'code' => $data[$this->getAlias()]['code']
            ];

            // redirect to GradingTypes add page
            $url = $this->url('add');
            $url['action'] = 'GradingTypes';
            $url = $this->setQueryString($url, $criteriaParams, 'criteriaForm');

            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
    }

     //POCOR-8875 start
     public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
     {
        $LabelTable = TableRegistry::get('Labels');
         if ($field == 'academic_period_id') {
             return __('Academic Period');
         } elseif ($field == 'name') {
             return __('Name');
         }elseif ($field == 'code') {
            $codeName = $LabelTable->find()->where(['module_name' =>'Outcome -> Criterias' , 'field_name' =>'code'])->first();
            if($codeName != null){
               $codeName =  $codeName->name;
            }
            return  __((string)$codeName);
         } elseif ($field == 'outcome_template_id') {
             return __('Outcome Template');
         } elseif ($field == 'education_subject_id') {
             return __('Education Subject');
         } elseif ($field == 'education_grade_id') {
             return __('Education Grade');
         } elseif ($field == 'outcome_grading_type_id') {
             return __('Outcome Grading Type');
         }  elseif ($field == 'modified_user_id') {
             return __('Modified By');
         } elseif ($field == 'modified') {
             return __('Modified On');
         } elseif ($field == 'created_user_id') {
             return __('Created By');
         } elseif ($field == 'created') {
             return __('Created On');
         } 
         elseif ($field == 'code') {
             $LabelsTable = TableRegistry::getTableLocator()->get('Labels');
 
             $label = $LabelsTable->find()
                 ->where([
                     'module' => $module,
                     'module_name' => 'Outcome -> Criterias',
                     'field' => 'code'
                 ])
                 ->first();
             
             return $label ? $label->field_name : null;
         } 
         else {
             return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
         }
     }
     //POCOR-8875 end
    
}
