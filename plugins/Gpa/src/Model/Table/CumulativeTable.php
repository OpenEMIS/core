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
            'foreignKey' => 'education_grade_id',
            'targetForeignKey' => 'education_grade_gpa_id',
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
            ->notEmpty('academic_period_id')
            ->notEmpty('gpa_education_grade_id')
            ->notEmpty('gpa_education_programme_id')
            ->notEmpty('gpa_grading_type_id');
            //->notEmpty('education_grade_id');
    }

   /* public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['ExaminationSubjects.EducationSubjects', 'ExaminationSubjects.ExaminationGradingTypes']);
    }*/


    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getAttribute('query')['academic_period_id']) ? $this->request->getAttribute('query')['academic_period_id'] : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        $extra['elements']['controls'] = ['name' => 'Gpa.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where($where);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getGpaTab();
        
    }

    /*public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }*/

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('gpa_education_programme_id', ['type' => 'hidden']);
       // $this->field('education_grade_id', ['type' => 'select']);
        $this->field('gpa_education_grade_id', ['type' => 'select']);
        $this->field('gpa_grading_type_id', ['type' => 'select']);

        $this->setFieldOrder(['academic_period_id', 'gpa_education_grade_id', 'gpa_grading_type_id']);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('gpa_education_programme_id', ['type' => 'select']);
        $this->field('education_grade_id');
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
        if ($action == 'add' || $action == 'edit') {

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
                $EducationGradesId = $this->find()->where(['id' => $recordId])->first()->education_grade_id;

                $EducationGrades = $this->GpaEducationGrades->find()->select(['id','name'])->where(['id' => $EducationGradesId])->first();
                //echo "<pre>"; print_r($params); die;
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

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'element';
            $attr['element'] = 'cumulative';

            if ($request->is(['post', 'put'])) {
                $academicPeriodId = $request->getData($this->aliasField('academic_period_id'));
                $educationProgrammeId = $request->getData($this->aliasField('gpa_education_programme_id'));

                if (!empty($academicPeriodId) || !empty($educationProgrammeId)) {
                    $query = $this->EducationGrades->
                                find('all')
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
                    //dd($results);
                    if(!empty($results)){
                        $attr['data'] = $results;
                    }
                }
            }
        }else if ($action == 'edit') {
            $attr['type'] = 'element';
            $attr['element'] = 'cumulative';
            $recordId = $this->paramsDecode($this->request->getAttribute('params')['pass'][1])['id'];

            if (!empty($gpaEducationGradeId)) {

                $institutionId = $this->getInstitutionID();
                $gpafindGrade = $this->find()
                    ->where([
                        $this->aliasField('id') => $recordId,
                    ])
                    ->first();

                $existingSubjectsInGrade = TableRegistry::getTableLocator()->get('Education.EducationGradesSubjects')
                    ->find('list', [
                        'keyField' => 'education_subject_id',
                        'valueField' => 'education_subject_id'
                    ])
                    ->where(['EducationGradesSubjects.education_grade_id' => $institutionGrade->education_grade_id])
                    ->toArray();

                $subjectQuery = TableRegistry::getTableLocator()->get('Education.EducationSubjects')
                    ->find()
                    ->find('order');

                if (!empty($existingSubjectsInGrade)) {
                    $subjectQuery->where([
                        'EducationSubjects.id IN' => $existingSubjectsInGrade
                    ]);
                }

                $subjectOptions = $subjectQuery->toArray();

                $institutionProgramGradeSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionProgramGradeSubjects')
                    ->find('list', [
                        'keyField' => 'education_grade_subject_id',
                        'valueField' => 'education_grade_subject_id'
                    ])
                    ->where([
                        'InstitutionProgramGradeSubjects.education_grade_id' => $institutionGrade->education_grade_id,
                        'InstitutionProgramGradeSubjects.institution_grade_id' => $programmeId
                    ])
                    ->enableHydration(false)
                    ->toArray();

                $attr['data'] = $subjectOptions;
                $attr['exists'] = $institutionProgramGradeSubjects;
            }
        } else if ($action == 'view') {
            echo "<pre>"; print_r($request); die;
            $attr['type'] = 'element';
            $attr['element'] = 'cumulative';
            $recordId = $this->paramsDecode($this->request->getAttribute('params')['pass'][1])['id'];
            if (!empty($recordId)) {
                $institutionGrade = $this->find()
                    ->where([
                        $this->aliasField('id') => $recordId,
                    ])
                    ->first();

                $existingSubjectsInGrade = TableRegistry::getTableLocator()->get('Institution.InstitutionProgramGradeSubjects')
                    ->find('list', [
                        'keyField' => 'education_grade_subject_id',
                        'valueField' => 'education_grade_subject_id'
                    ])
                    ->where([
                        'InstitutionProgramGradeSubjects.education_grade_id' => $institutionGrade->education_grade_id,
                        'InstitutionProgramGradeSubjects.institution_grade_id' => $programmeId
                    ])
                    ->enableHydration(false)
                    ->toArray();

                if (!empty($existingSubjectsInGrade)) {
                    $subjectQuery = TableRegistry::getTableLocator()->get('Education.EducationSubjects')
                        ->find()
                        ->find('order');
                    $subjectQuery->where([
                        'EducationSubjects.id IN' => $existingSubjectsInGrade
                    ]);
                    $subjectOptions = $subjectQuery->toArray();
                }

                $attr['data'] = $subjectOptions;
            }
        }

        return $attr;
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $errors = $entity->getErrors();
        if (empty($errors) || count($errors) == 1) {
            $cumulativeData = $data['Cumulative'];
            $academicPeriodId = $cumulativeData['academic_period_id'];
            $gpaEducationProgrammeId = $cumulativeData['gpa_education_programme_id'];
            $educationGradeId = $cumulativeData['gpa_education_grade_id'];
            $gpaGradingTypeId = $cumulativeData['gpa_grading_type_id'];

            $gpaGrades = $data['education_grade_id'];
            foreach ($gpaGrade as $key => $gradeId) {
                 if ($gradeId != '0' && $gradeId != 0) { 
                    echo "<pre>"; print_r($gradeId); die;
                    $dataToSave = [
                        'academic_period_id' => $academicPeriodId,
                        'gpa_education_grade_id' => $educationGradeId,
                        'gpa_grading_type_id' => $gpaGradingTypeId,
                        'education_grade_id' => $gradeId,
                    ];
                    $this->save($dataToSave);
                }
            }
        }
    }


    
}
