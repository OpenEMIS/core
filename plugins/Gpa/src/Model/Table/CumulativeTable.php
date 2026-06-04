<?php
namespace Gpa\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\Utility\Text;

/**
 * POCOR-8222
 * Develop GPA features in system
 * */
class CumulativeTable extends ControllerActionTable {

    public function initialize(array $config): void
    {
        $this->setTable('education_grades_cumulative_gpa');
        parent::initialize($config);
        $this->belongsTo('GpaEducationGrades', ['className' => 'Education.EducationGrades','foreignKey' => 'main_education_grade_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades','foreignKey' => 'main_education_grade_id']);
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $this->setDeleteStrategy('restrict'); //POCOR-9078
    }

    public function validationDefault(Validator $validator): Validator {

        $validator = parent::validationDefault($validator);

        return $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('main_education_grade_id')
            ->notEmpty('gpa_education_programme_id')
            ->notEmpty('education_grade_id');
    }

   public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        // Get the table name as a string for GpaSystem
       
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = $this->request->getQuery('academic_period_id') ?? $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));

        $where = [];

        $extra['elements']['controls'] = [
            'name' => 'Gpa.controls',
            'data' => [],
            'options' => [],
            'order' => 1
        ];
        $gradeGpaTable = TableRegistry::getTableLocator()->get('Gpa.EducationGradesGpa');
        $query->leftJoin(
                        [$gradeGpaTable->getAlias() => $gradeGpaTable->getTable()],
                        [
                            $this->aliasField('main_education_grade_id = ') . $gradeGpaTable->aliasField('education_grade_id'),
                        ]
                    )
        ->where(['EducationGradesGpa.academic_period_id' => $selectedAcademicPeriod])
        ->group(['main_education_grade_id']);
    }


    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->controller->getGpaTab();
        
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('main_education_grade_id', ['type' => 'select']);
        $this->field('gpa_education_programme_id'); //POCOR-8962
        $this->field('education_grade_id', ['type' => 'hidden']);
        $this->setFieldOrder(['academic_period_id', 'gpa_education_programme_id','main_education_grade_id', 'gpa_grading_type_id']);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {  
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('gpa_education_programme_id', ['type' => 'select']);
        $this->field('education_grade_id',['visible' => false]);
        $this->field('gpa_grading_type_id',['visible' => false]);
        $this->field('education_grades_cumulative_gpa', [
            'type' => 'element',
            'element' => 'cumulative',
            'attr' => [
                'label' => 'Cumulative Gpa Grade Selection'
            ]
        ]);
        $this->field('main_education_grade_id',['type' => 'select']);
        $this->setFieldOrder(['academic_period_id', 'gpa_education_programme_id','main_education_grade_id']);

    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {

                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->getQuery('period')));
				$attr['options'] = $periodOptions;
				$attr['onChangeReload'] = true;
                $attr['default'] = $selectedPeriod;

            } else {
                $gpa = TableRegistry::getTableLocator()->get('Gpa.EducationGradesGpa');
                $getId = $this->paramsDecode($this->request->getParam('pass')[1]);
                $recordId = $getId['id'];
                $mainEducationGradeId = $this->find()->where(['id' => $recordId])->first()->main_education_grade_id;
                 $academic_period_id = $gpa->find()->where([$gpa->aliasField('education_grade_id') => $mainEducationGradeId])->first()->academic_period_id;
                $academicPeriodValue = $this->AcademicPeriods->find()->select(['id', 'name'])->where(['id' => $academic_period_id])->first();
                $attr['type'] = 'readonly';
                $attr['value'] = $academicPeriodValue->id;
                $attr['attr']['value'] = $academicPeriodValue->name;
            }
        }
        return $attr;
    }

    public function onUpdateFieldGpaEducationProgrammeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $request = $this->request;
        $AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
            $academicPeriodId = !is_null($request->getData($this->aliasField('academic_period_id'))) ? $request->getData($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();
        $EducationProgrammes = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');
        $gpa = TableRegistry::getTableLocator()->get('Gpa.EducationGradesGpa');
        if ($action == 'view') {
            //POCOR-8962
            $getId = $this->paramsDecode($this->request->getParam('pass')[1]);
            $recordId = $getId['id'];
            $mainEducationGradeId = $this->find()->where(['id' => $recordId])->first()->main_education_grade_id;
            $academic_period_id = $gpa->find()->where([$gpa->aliasField('education_grade_id') => $mainEducationGradeId])->first()->academic_period_id;
            $gradeId = $this->find()->where([$this->aliasField('id') => $recordId])->first()->main_education_grade_id;
            $programmeId = $this->GpaEducationGrades->get($gradeId)->education_programme_id;
               // echo "<pre>";print_r($programmeId);exit;
            $attr['type'] = 'readonly';
            $attr['value'] = $EducationProgrammes->get($programmeId)->name;
            $attr['attr']['value'] = $EducationProgrammes->get($programmeId)->name;
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
            
            $getId = $this->paramsDecode($this->request->getParam('pass')[1]);
            $recordId = $getId['id'];
            $mainEducationGradeId = $this->find()->where(['id' => $recordId])->first()->main_education_grade_id;
            $academic_period_id = $gpa->find()->where([$gpa->aliasField('education_grade_id') => $mainEducationGradeId])->first()->academic_period_id;
            $gradeId = $this->find()->where([$this->aliasField('id') => $recordId])->first()->main_education_grade_id;
           $programmeId = $this->GpaEducationGrades->get($gradeId)->education_programme_id;
            $attr['type'] = 'readonly';
            $attr['value'] = $programmeId;
            $attr['attr']['value'] = $EducationProgrammes->get($programmeId)->name;
        }
          
        return $attr;
    }

    public function addOnChangeEducationProgrammeId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('education_grade_id', $request->getData()[$this->getAlias()])) {
                    unset($data[$this->getAlias()]['education_grade_id']);
                }
                if (array_key_exists('subjects', $request->getData()[$this->getAlias()])) {
                    unset($data[$this->getAlias()]['subjects']);
                }
            }
        }
    }


    public function onUpdateFieldMainEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $request = $this->request;
        if ($action == 'add' || $action == 'edit' ) {
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
                 $getId = $this->paramsDecode($this->request->getParam('pass')[1]);
                $recordId = $getId['id'];
                $EducationGradesId = $this->find()->where(['id' => $recordId])->first()->main_education_grade_id;
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $this->GpaEducationGrades->get($EducationGradesId)->name;

            }
        }

        return $attr;
    }

     public function addOnChangeEducationGradeId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('subjects', $request->getData()[$this->getAlias()])) {
                    unset($data[$this->getAlias()]['subjects']);
                }
            }
        }
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


    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'education_grade_id') {
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

    public function onUpdateFieldEducationGradesCumulativeGpa(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['type'] = 'element';
        $attr['element'] = 'cumulative';
        $mainEducationGradeId = $request->getData()['Cumulative']['main_education_grade_id'];

         $gpa = TableRegistry::getTableLocator()->get('Gpa.EducationGradesGpa');
        if ($action == 'add' || $action == 'edit' || $action == 'view') {
            if ($request->is(['post', 'put'])) {
                $academicPeriodId = $request->getData($this->aliasField('academic_period_id'));
                $educationProgrammeId = $request->getData($this->aliasField('gpa_education_programme_id'));
            }

            if ($action == 'edit' || $action == 'view') {
                $gpa = TableRegistry::getTableLocator()->get('Gpa.EducationGradesGpa');
                $selectedProgramme = $request->getData($this->aliasField('gpa_education_programme_id'));
                $getId = $this->paramsDecode($this->request->getParam('pass')[1]);
                $recordId = $getId['id'];
                $mainEducationGradeId = $this->find()->where(['id' => $getId['id']])->first()->main_education_grade_id;
                 $academicPeriodId = $gpa->find()->where([$gpa->aliasField('education_grade_id') => $mainEducationGradeId])->first()->academic_period_id;
                $mainEducationGradeId = $this->find()->where([$this->aliasField('id') => $recordId])->first()->main_education_grade_id;
                if(empty($selectedProgramme)){
                    $educationProgrammeId = $this->EducationGrades->find()->where([$this->EducationGrades->aliasField('id') => $mainEducationGradeId])->first()->education_programme_id;
                }else{
                    $educationProgrammeId = $request->getData($this->aliasField('gpa_education_programme_id'));
                }
               
                if (!empty($recordId)) {
                    $CumulativeGpaGradesData = TableRegistry::getTableLocator()->get('Gpa.EducationCumulativeGrades')
                        ->find('list', [
                            'keyField' => 'education_grade_id',
                            'valueField' => 'education_grade_id'
                        ])
                        ->where(['main_education_grade_id' => $mainEducationGradeId])
                        ->toArray();
                    
                    $attr['exists'] = array_values($CumulativeGpaGradesData);
                    $attr['data_id'] = $recordId;
                }
 
            }
           $stageId =  $this->EducationGrades->find()->where(['id IS' => $mainEducationGradeId])->first()->education_stage_id;
           $conditions = [];

            if ($educationProgrammeId !== null) {
                $conditions['EducationProgrammes.id'] = $educationProgrammeId;
            } else {
                $conditions['EducationProgrammes.id IS'] = null;
            }

            if ($academicPeriodId !== null) {
                $conditions['AcademicPeriods.id'] = $academicPeriodId;
            } else {
                $conditions['AcademicPeriods.id IS'] = null;
            }

            if ($mainEducationGradeId !== null) {
                $conditions[$this->EducationGrades->aliasField('id') . ' !='] = $mainEducationGradeId;
            }

            if ($stageId !== null) {
                $conditions[$this->EducationGrades->aliasField('education_stage_id') . ' <'] = $stageId;
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
                            ->where($conditions)
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

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {

       // $cumulativeGpaGrades = $entity['education_grades_cumulative_gpa'];
        $gradeGpa = TableRegistry::getTableLocator()->get('Gpa.EducationGradesGpa');
        $educationGradeId = $entity->main_education_grade_id;
        $academicPeriodId = $entity->academic_period_id;
        $checkRecord = $gradeGpa->find()
            ->where([
                $gradeGpa->aliasField('education_grade_id') => $educationGradeId,
                $gradeGpa->aliasField('academic_period_id') => $academicPeriodId,
                $gradeGpa->aliasField('gpa_grading_type_id IS NOT') => NULL
            ])
            ->toArray();
        if (empty($checkRecord)) {
            $message = __('Please add GPA Education Grade first');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            return false;
        } 

        $existingRecord = $this->find()
                        ->where([
                            $this->aliasField('main_education_grade_id') => $educationGradeId,
                        ])->first();
        if (!empty($existingRecord)) {
            $message = __('A Cumulative GPA Education Grade record already exists for the specified grade and Period.');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            return false;
        }

    }


    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['EducationGrades' => [
            'sort' => ['EducationGrades.id' => 'ASC']]]);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra) 
    {
        $this->field('education_grades_cumulative_gpa', [
            'type' => 'element',
            'element' => 'cumulative',
            'attr' => [
                'label' => 'Cumulative Gpa Grade Selection'
            ]
        ]);
        $this->setFieldOrder([
            'academic_period_id', 'gpa_education_programme_id','main_education_grade_id','education_grades_cumulative_gpa'
        ]);
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options) 
    { 
        $data = [];
        $CumulativeGpaGradesTable = TableRegistry::getTableLocator()->get('Gpa.EducationCumulativeGrades');
        $CumulativeGpaGrades = $entity->education_grades_cumulative_gpa ?? [];
        foreach ($CumulativeGpaGrades as $value) {
            // Extra validation to ensure non-zero entries
            if (!empty($value['education_grade_id']) && $value['education_grade_id'] != 0) {
                $data[] = [
                    'id' => Text::uuid(),
                    'main_education_grade_id' => $entity->main_education_grade_id,
                    'education_grade_id' => $value['education_grade_id'],
                    'created' => date('Y-m-d H:i:s'),
                    'created_user_id' => $entity['created_user_id'],
                    'modified' => date('Y-m-d H:i:s'),
                    'modified_user_id' => $entity['created_user_id'],
                ];
            }
        }
        if(!empty($entity->main_education_grade_id)){
                $data[] = [
                    'id' => Text::uuid(),
                    'main_education_grade_id' => $entity->main_education_grade_id,
                    'education_grade_id' => $entity->main_education_grade_id,
                    'created' => date('Y-m-d H:i:s'),
                    'created_user_id' => $entity['created_user_id'],
                    'modified' => date('Y-m-d H:i:s'),
                    'modified_user_id' => $entity['created_user_id'],
                ];
        }

        if (!empty($data)) {
            $entities = $CumulativeGpaGradesTable->newEntities($data);
            $CumulativeGpaGradesTable->saveMany($entities);
        }
        $checkRecord = $this->deleteAll(['education_grade_id' => 0]);
    }



    public function addBeforeSave($event, $entity, $options) 
    {
        if (!empty($entity->education_grades_cumulative_gpa)) {
            $filteredData = array_filter($entity->education_grades_cumulative_gpa, function($grade) {
                return !empty($grade['education_grade_id']) && $grade['education_grade_id'] != 0;
            });

            // Set the filtered data back to the entity to exclude any unwanted entries
            $entity->education_grades_cumulative_gpa = $filteredData;
        }
    }

    //POCOR-9078 - Restrict delete if associated InstitutionStudentsGpa records exist and delete associated cumulative GPA grades on deletion of Cumulative GPA Education Grade record
    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $InstitutionStudentsGpa = TableRegistry::getTableLocator()
            ->get('Institution.InstitutionStudentsGpa');

        // Get grade -- Display issue
        $gradeToBeDeleted = $this->GpaEducationGrades->get($entity->main_education_grade_id);
        $entity->showDeletedValueAs = $gradeToBeDeleted->name;

        // Student count for grenerated cgpa
        $count = $InstitutionStudentsGpa->find()
            ->where([
                'education_grade_id' => $entity->main_education_grade_id
            ])
            ->count();

        // Attach to extra for use in delete confirmation message
        $extra['associatedRecords']['InstitutionStudentsGpa'] = [
            'model' => 'InstitutionStudentsGpa',
            'count' => $count
        ];
    }

    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->InstitutionStudentsGpa = TableRegistry::get('Institution.InstitutionStudentsGpa');

        if ($this->checkGpaRecords($entity)) {

            $this->Alert->error(__('Delete operation is not allowed as there are other information linked to this record.'), [
                'type' => 'string',
                'reset' => true
            ]);
            $event->stopPropagation();
        }
    }

    public function checkGpaRecords($entity): bool
    {
        $educationGradeIds = $this->find()
            ->select(['education_grade_id'])
            ->where([
                'main_education_grade_id' => $entity->main_education_grade_id
            ])
            ->extract('education_grade_id')
            ->toArray();

        return $this->InstitutionStudentsGpa->exists([
            'education_grade_id IN' => $educationGradeIds,
            'cumulative_gpa IS NOT' => null
        ]);
    }

    public function afterDelete(
        EventInterface $event,
        Entity $entity,
        ArrayObject $options
    ) {
        $this->deleteAll([
            'main_education_grade_id' => $entity->main_education_grade_id
        ]);
    }
    //POCOR-9078 -- End

    //POCOR-8962
    public function onGetGpaEducationProgrammeId(EventInterface $event, Entity $entity)
    {
        if($this->action == 'index') {
            $programmeGradeName = $entity->education_grade->programme_name;
            return $programmeGradeName;
        }
    }

    public function onGetAcademicPeriodId(EventInterface $event, Entity $entity)
    {
        // Get the GPA table
        $gpaTable = TableRegistry::getTableLocator()->get('Gpa.EducationGradesGpa');

        // Get the main education grade ID from the entity
        $mainEducationGradeId = $entity->main_education_grade_id;

        // Fetch the academic period ID, if available
        $gpaRecord = $gpaTable->find()
            ->where([$gpaTable->aliasField('education_grade_id') => $mainEducationGradeId])
            ->first();

        if ($gpaRecord && $gpaRecord->academic_period_id) {
            // Fetch the academic period name
            $academicPeriodId = $gpaRecord->academic_period_id;
            $academicPeriod = $this->AcademicPeriods->find()
                ->where(['id' => $academicPeriodId])
                ->first();

            if ($academicPeriod) {
                $entity->name = $academicPeriod->name;
                return $entity->name;
            }
        }

        // Return null if no matching academic period was found
        return null;
    }


}
