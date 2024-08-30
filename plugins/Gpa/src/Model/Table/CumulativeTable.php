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
use Cake\Utility\Text;

/**
 * POCOR-8222
 * Develop GPA features in system
 * */
class CumulativeTable extends ControllerActionTable {
    public function initialize(array $config): void
    {
        $this->setTable('education_grades_gpa');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods','foreignKey' => 'academic_period_id']);
        $this->belongsTo('GpaEducationGrades', ['className' => 'Education.EducationGrades','foreignKey' => 'gpa_education_grade_id']);
        /*$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades','foreignKey' => 'education_grade_id']);*/
        $this->belongsTo('AssessmentGradingTypes', ['className' => 'Assessment.AssessmentGradingTypes' ,'foreignKey' => 'gpa_grading_type_id']);
        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'joinTable' => 'cumulative_gpa_grades',
            'foreignKey' => 'education_grade_gpa_id',
            'targetForeignKey' => 'education_grade_id',
            'through' => 'Gpa.CumulativeGpaGrades',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->notEmpty('academic_period_id');
            
            //->notEmpty('education_grade_id');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
         $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
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
         $this->field('education_grade_id', ['visiable' => false]);
        $this->field('gpa_education_grade_id', ['type' => 'select']);
        $this->field('gpa_grading_type_id', ['type' => 'hidden']);

        $this->setFieldOrder(['academic_period_id', 'gpa_education_grade_id', 'gpa_grading_type_id']);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {  
        $this->field('start_date', ['visible' => true]);
        $this->field('end_date', ['visible' => true]);
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('gpa_education_programme_id', ['type' => 'select']);
        $this->field('education_grade_id',['visible' => false]);
        $this->field('gpa_grading_type_id',['visible' => false]);
        $this->field('cumulative_gpa_grades', [
            'type' => 'element',
            'element' => 'cumulative',
            'attr' => [
                'label' => 'Cumulative Gpa Grade Selection'
            ]
        ]);
        $this->field('gpa_education_grade_id',['type' => 'select']);
        $this->field('gpa_grading_type_id', ['type' => 'select']);
        $this->setFieldOrder(['academic_period_id', 'gpa_education_programme_id','gpa_education_grade_id', 'gpa_grading_type_id']);

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
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodId = !is_null($request->getData($this->aliasField('academic_period_id'))) ? $request->getData($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        if ($action == 'view') {
            $attr['visible'] = false;

        } else if ($action == 'add') {
            if ($action == 'add') {
                $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('visible')
					->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    ->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
                    ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
					->toArray();

                $attr['options'] = $programmeOptions;
                $attr['onChangeReload'] = 'changeEducationProgrammeId';

            }
        }else if ($action == 'edit'){
                //since programme_id is not stored, then during edit need to get from grade
            $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('visible')
                    ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    ->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
                    ->where(['EducationSystems.academic_period_id IS' => $academicPeriodId])
                    ->toArray();
                $recordId = $this->getQueryString('id');
                
                $gradeId = $this->find()->where([$this->aliasField('id') => $recordId])->first()->gpa_education_grade_id;
                $programmeId = $this->EducationGrades->find()->where([$this->EducationGrades->aliasField('id IS') => $gradeId])->first()->education_programme_id;
                $EducationProgrammes = $EducationProgrammes->find()->select(['id','name'])->where([$EducationProgrammes->aliasField('id IS') => $programmeId])->first();
                $attr['type'] = 'select';
                $attr['options'] = $programmeOptions;
                $attr['default'] = $EducationProgrammes->id;
                $attr['onChangeReload'] = 'changeEducationProgrammeId';
            }
        return $attr;
    }

    public function onUpdateFieldGpaEducationGradeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $selectedProgramme =  $request->getData()[$this->getAlias()]['gpa_education_programme_id'];
            if ($action == 'add') {
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
                $gradeOptions = $this->GpaEducationGrades
                        ->find('list')
                        ->find('visible')
                        ->contain(['EducationProgrammes'])
                        ->where([$this->GpaEducationGrades->aliasField('education_programme_id IS') => $selectedProgramme])
                        ->order(['EducationProgrammes.order' => 'ASC', $this->GpaEducationGrades->aliasField('order') => 'ASC'])
                        ->toArray();
                $recordId = $this->getQueryString('id');
                $EducationGradesId = $this->find()->where(['id' => $recordId])->first()->gpa_education_grade_id;
                $EducationGrades = $this->GpaEducationGrades->find()->select(['id','name'])->where(['id' => $EducationGradesId])->first();
                $attr['type'] = 'select';
                $attr['options'] = $gradeOptions;
                $attr['default'] = $EducationGradesId;
                $attr['onChangeReload'] = 'changeEducationGradeId';

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
        return $attr;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'gpa_education_grade_id') {
            return __('Education Grade');
        }else if ($field == 'gpa_grading_type_id') {
            return  __('Grading Type');
        }else if ($field == 'gpa_education_programme_id') {
            return  __('Education programme');
        }else if ($field == 'gpa_education_programme_id') {
            return  __('Education programme');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateFieldCumulativeGpaGrades(Event $event, array $attr, $action, ServerRequest $request)
    {
        $attr['type'] = 'element';
        $attr['element'] = 'cumulative';
        
        if ($action == 'add' || $action == 'edit') {
            if ($request->is(['post', 'put'])) {
                $academicPeriodId = $request->getData($this->aliasField('academic_period_id'));
                $educationProgrammeId = $request->getData($this->aliasField('gpa_education_programme_id'));
            }
            if ($action == 'edit') {
                $recordId = $this->getQueryString('id');
                
                $gradeId = $this->find()->where([$this->aliasField('id') => $recordId])->first()->gpa_education_grade_id;
                $educationProgrammeId = $this->EducationGrades->find()->where([$this->EducationGrades->aliasField('id IS') => $gradeId])->first()->education_programme_id;
                if (!empty($recordId)) {
                    $cumulativeData = $this->find()
                        ->where([$this->aliasField('id') => $recordId])
                        ->first();
                    $academicPeriodId = $cumulativeData->academic_period_id;
                    $CumulativeGpaGradesData = TableRegistry::getTableLocator()
                        ->get('Gpa.CumulativeGpaGrades')
                        ->find('list', [
                            'keyField' => 'education_grade_id',
                            'valueField' => 'education_grade_id'
                        ])
                        ->where(['education_grade_gpa_id' => $recordId])
                        ->toArray();
                    
                    $attr['exists'] = array_values($CumulativeGpaGradesData);
                }
            }
            
            if (!empty($academicPeriodId)) {
                $query = $this->EducationGrades->find('all')
                            ->select([
                                'education_grade_id' => 'EducationGrades.id',
                                'id' => 'EducationGrades.id',
                                'name' => 'EducationGrades.name',
                                'code' => 'EducationGrades.code',
                            ])
                            ->innerJoinWith('EducationProgrammes', function ($q) {
                                return $q->innerJoinWith('EducationCycles', function ($q) {
                                    return $q->innerJoinWith('EducationLevels', function ($q) {
                                        return $q->innerJoinWith('EducationSystems', function ($q) {
                                            return $q->innerJoinWith('AcademicPeriods');
                                        });
                                    });
                                });
                            })
                            ->where([
                                'EducationProgrammes.id IS' => $educationProgrammeId,
                                'AcademicPeriods.id IS' => $academicPeriodId
                            ])
                            ->order([
                                'AcademicPeriods.order' => 'ASC',
                                'EducationLevels.order' => 'ASC',
                                'EducationCycles.order' => 'ASC',
                                'EducationProgrammes.order' => 'ASC',
                                'EducationGrades.order' => 'ASC'
                            ]);

                $results = $query->toArray();
                $attr['data'] = $results;
            }
        }

        return $attr;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $cumulativeGpaGrades = $entity['cumulative_gpa_grades'];

        // Filter out the objects with empty or zero `education_grade_id`
        $filteredGrades = array_filter($cumulativeGpaGrades, function($grade) {
            return !empty($grade->education_grade_id) && $grade->education_grade_id != 0;
        });
        $entity->cumulative_gpa_grades =  $filteredGrades;
    }


    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['EducationGrades' => [
            'sort' => ['EducationGrades.id' => 'ASC']]]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) 
    {
        $this->field('cumulative_gpa_grades', [
            'type' => 'element',
            'element' => 'cumulative',
            'attr' => [
                'label' => 'Cumulative Gpa Grade Selection'
            ]
        ]);
        $this->setFieldOrder([
            'academic_period_id', 'gpa_education_programme_id','gpa_education_grade_id', 'start_date','end_date','cumulative_gpa_grades'
        ]);
    }


    
}
