<?php
namespace Gpa\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenTime;
use Cake\I18n\FrozenDate;


/**
 * POCOR-8222
 * Develop GPA features in system
 * */
class GpaSystemTable extends ControllerActionTable {
    public function initialize(array $config): void
    {
        $this->setTable('education_grades_gpa');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods','foreignKey' => 'academic_period_id']);
        $this->belongsTo('GpaEducationGrades', ['className' => 'Education.EducationGrades','foreignKey' => 'gpa_education_grade_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades','foreignKey' => 'education_grade_id']);
        $this->belongsTo('AssessmentGradingTypes', ['className' => 'Assessment.AssessmentGradingTypes' ,'foreignKey' => 'gpa_grading_type_id']);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('gpa_education_grade_id')
            ->notEmpty('gpa_education_programme_id')
            ->notEmpty('gpa_grading_type_id');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getAttribute('query')['academic_period_id']) ? $this->request->getAttribute('query')['academic_period_id'] : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where = [];
        $nullVal = 0;
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        $where[$this->aliasField('education_grade_id =')] = $nullVal;
        $extra['elements']['controls'] = ['name' => 'Gpa.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where($where);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getGpaTab(); 
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('gpa_education_programme_id', ['type' => 'hidden']);
        $this->field('education_grade_id', ['type' => 'hidden']);
        $this->field('gpa_education_grade_id', ['type' => 'select']);
        $this->field('gpa_grading_type_id', ['type' => 'select']);

        $this->setFieldOrder(['academic_period_id', 'gpa_education_grade_id', 'gpa_grading_type_id']);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select','entity' => $entity]);
        $this->field('start_date', ['visible' => true]);
        $this->field('end_date', ['visible' => true]);
        $this->field('gpa_education_programme_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('education_grade_id', ['type' => 'hidden']);
        $this->field('gpa_education_grade_id', ['type' => 'select']);
        $this->field('gpa_grading_type_id', ['type' => 'select']);
        
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
       
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->getQuery('period')));
				$attr['options'] = $periodOptions;
				$attr['onChangeReload'] = true;
                $attr['default'] = $selectedPeriod;

            } else {
                $recordId = $this->getQueryString('id');
                $academic_period_id = $this->find()->where(['id' => $recordId])->first()->academic_period_id;
                $academicPeriodValue = $this->AcademicPeriods->find()->select(['id', 'name'])->where(['id' => $academic_period_id])->first();
                $attr['type'] = 'readonly';
                $attr['value'] = $academicPeriodValue->id;
                $attr['attr']['value'] = $academicPeriodValue->name;
            }
        }
        return $attr;
    }

    public function onUpdateFieldGpaEducationProgrammeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'view') {
            $attr['visible'] = false;
        } else if ($action == 'add' || $action == 'edit') {
            $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
			$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$academicPeriodId = !is_null($request->getData($this->aliasField('academic_period_id'))) ? $request->getData($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();

            if ($action == 'add' || $action == 'edit') {
                $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('visible')
					->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    ->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
                    ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
					->toArray();

                $attr['options'] = $programmeOptions;
                $attr['onChangeReload'] = 'changeEducationProgrammeId';

            } /*else {
                //since programme_id is not stored, then during edit need to get from grade
                $programmeId = $this->EducationGrades->get($attr['entity']->education_grade_id)->education_programme_id;
                $attr['type'] = 'readonly';
                $attr['value'] = $programmeId;
                $attr['attr']['value'] = $EducationProgrammes->get($programmeId)->name;
            }*/
        }
        return $attr;
    }

    public function onUpdateFieldGpaEducationGradeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {

            if ($action == 'add') {

                $selectedProgramme =  $request->getData()[$this->getAlias()]['gpa_education_programme_id'];
                $gradeOptions = [];
                if (!is_null($selectedProgramme)) {
                    $gradeOptions = $this->GpaEducationGrades
                        ->find('list')
                        ->find('visible')
                        ->contain(['EducationProgrammes'])
                        ->where([$this->GpaEducationGrades->aliasField('education_programme_id') => $selectedProgramme])
                        ->order(['EducationProgrammes.order' => 'ASC', $this->GpaEducationGrades->aliasField('order') => 'ASC'])
                        ->toArray();
                }
                $attr['options'] = $gradeOptions;
                $attr['onChangeReload'] = 'changeEducationGrade';

            } else {
                $recordId = $this->getQueryString('id');
                $EducationGradesId = $this->find()->where(['id' => $recordId])->first()->gpa_education_grade_id;

                $EducationGrades = $this->GpaEducationGrades->find()->select(['id','name'])->where(['id' => $EducationGradesId])->first();
                $attr['type'] = 'select';
                $attr['value'] = $EducationGrades->id;
                $attr['attr']['value'] = $EducationGrades->name;
            }
        }

        return $attr;
    }

    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList();

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' ) {
            return $this->updateDateRangeField('start_date', $attr, $request);
        }elseif ($action == 'edit') {
            $queryString = $this->request->getParam('pass')[1];
            $DecodedQueryString = $this->paramsDecode($queryString);
            $id = $DecodedQueryString['id'];
            $selectDate = $this->find()->where([$this->aliasField('id') => $id])->first()->start_date;
            $entity = $attr['entity'];
            $attr['value'] = (new FrozenDate($selectDate))->format('Y-m-d');
            $attr['attr']['value'] = (new FrozenDate($selectDate))->format('Y-m-d');
            return $attr;
        }
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            return $this->updateDateRangeField('end_date', $attr, $request);
        }elseif ($action == 'edit') {
           $queryString = $this->request->getParam('pass')[1];
            $DecodedQueryString = $this->paramsDecode($queryString);
            $id = $DecodedQueryString['id'];
            $selectDate = $this->find()->where([$this->aliasField('id') => $id])->first()->end_date;
            $entity = $attr['entity'];
            $attr['value'] = (new FrozenDate($selectDate))->format('Y-m-d');
            $attr['attr']['value'] = (new FrozenDate($selectDate))->format('Y-m-d');
            return $attr;
        }
    }

    // Misc
    private function updateDateRangeField($key, $attr, ServerRequest $request)
    {
        $requestData = $request->getData();
        if (array_key_exists($this->getAlias(), $requestData) && array_key_exists('academic_period_id', $requestData[$this->getAlias()])) {
            $selectedPeriodId = $requestData[$this->getAlias()]['academic_period_id'];
        } else {
            $selectedPeriodId = $this->AcademicPeriods->getCurrent();
        }

        $selectedPeriod = $this->AcademicPeriods->get($selectedPeriodId);
        $attr['type'] = 'date';
        $attr['date_options']['startDate'] = $selectedPeriod->start_date->format('d-m-Y');
        $attr['date_options']['endDate'] = $selectedPeriod->end_date->format('d-m-Y');

        if (!array_key_exists($this->getAlias(), $requestData) || !array_key_exists($key, $requestData[$this->getAlias()])) {
            if ($selectedPeriodId != $this->AcademicPeriods->getCurrent()) {
                $attr['value'] = $selectedPeriod->start_date;
            } else {
                $attr['value'] = FrozenTime::now();
            }
        }
//echo "<pre>"; print_r($attr); die;
        return $attr;
    }
    public function beforeSave(Event $event, Entity $entity, ArrayObject $data) {
        //echo "<pre>"; print_r($this->request); die;
    }
    
}
