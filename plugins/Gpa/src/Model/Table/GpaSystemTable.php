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
        $this->belongsTo('GpaEducationGrades', ['className' => 'Education.EducationGrades','foreignKey' => 'education_grade_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades','foreignKey' => 'education_grade_id']);
        $this->belongsTo('GpaGradingTypes', ['className' => 'Gpa.GpaGradingTypes' ,'foreignKey' => 'gpa_grading_type_id']);
        $this->hasMany('InstitutionStudentsGpa', ['className' => 'Institution.InstitutionStudentsGpa', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->notEmpty('name')
            ->notEmpty('academic_period_id')
            ->notEmpty('education_grade_id')
            ->notEmpty('gpa_education_programme_id')
            ->notEmpty('gpa_grading_type_id')
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', true]
            ]);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $where = [];
        $nullVal = 0;
        
        $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        if ($selectedAcademicPeriod !== NULL) {
            $where[$this->aliasField('gpa_grading_type_id IS NOT')] = NULL;
        } else {
            $where[$this->aliasField('gpa_grading_type_id IS')] = NULL;
        }
        $extra['elements']['controls'] = ['name' => 'Gpa.controls', 'data' => [], 'options' => [], 'order' => 1];
        $query->where($where);
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->controller->getGpaTab(); 
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('gpa_education_programme_id', ['type' => 'hidden']);
        $this->field('main_education_grade_id', ['type' => 'hidden']);
        $this->field('start_date', ['attr' => ['label' => __('Start Date')]]);
        $this->field('end_date', ['attr' => ['label' => __('End Date')]]);
        $this->field('education_grade_id', ['type' => 'select']);
        $this->field('gpa_grading_type_id', ['type' => 'select']);

        $this->setFieldOrder(['name','academic_period_id', 'education_grade_id', 'gpa_grading_type_id','start_date','end_date']);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('academic_period_id', ['type' => 'select','entity' => $entity]);
        $this->field('gpa_education_programme_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('start_date', ['attr' => ['label' => __('Start Date')]]);
        $this->field('end_date', ['attr' => ['label' => __('End Date')]]);
        $this->field('education_grade_id', ['type' => 'select']);
        $this->field('gpa_grading_type_id', ['type' => 'select']);
        $this->setFieldOrder(['name','academic_period_id', 'start_date','end_date','gpa_education_programme_id','education_grade_id', 'gpa_grading_type_id']);
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
                $getId = $this->paramsDecode($this->request->getParam('pass')[1]);
                $recordId = $getId['id'];
                $academic_period_id = $this->find()->where(['id' => $recordId])->first()->academic_period_id;
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
        if ($action == 'view') {
            $attr['visible'] = false;
        } else if ($action == 'add' || $action == 'edit') {
            $EducationProgrammes = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');
            $AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
            $academicPeriodId = !is_null($request->getData($this->aliasField('academic_period_id'))) ? $request->getData($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();
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

            }else {
                //since programme_id is not stored, then during edit need to get from grade
                $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('visible')
                    ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    ->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
                    ->where(['EducationSystems.academic_period_id IS' => $academicPeriodId])
                    ->toArray();
                $getId = $this->paramsDecode($this->request->getParam('pass')[1]);
                $recordId = $getId['id'];
                $gradeId = $this->find()->where([$this->aliasField('id') => $recordId])->first()->education_grade_id;
                $programmeId = $this->EducationGrades->find()->where([$this->EducationGrades->aliasField('id') => $gradeId])->first()->education_programme_id;
                $EducationProgrammes = $EducationProgrammes->find()->select(['id','name'])->where([$EducationProgrammes->aliasField('id') => $programmeId])->first();
                $attr['type'] = 'select';
                $attr['options'] = $programmeOptions;
                $attr['default'] = $EducationProgrammes->id;
                $attr['onChangeReload'] = 'changeEducationProgrammeId';
            }
        }
        return $attr;
    }

    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $request = $this->request;
        if ($action == 'add' || $action == 'edit' ) {
            $selectedProgramme = isset($request->getData()[$this->getAlias()]['gpa_education_programme_id']) 
            ? $request->getData()[$this->getAlias()]['gpa_education_programme_id'] 
            : null;
            if ($action == 'add') {
                $gradeOptions = $this->GpaEducationGrades
                    ->find('list')
                    ->find('visible')
                    ->contain(['EducationProgrammes'])
                    ->where([$this->GpaEducationGrades->aliasField('education_programme_id IS') => $selectedProgramme])
                    ->order(['EducationProgrammes.order' => 'ASC', $this->GpaEducationGrades->aliasField('order') => 'ASC'])
                    ->toArray();
                $attr['options'] = $gradeOptions;
                $attr['onChangeReload'] = 'changeEducationGrade';

            } else {
                $getId = $this->paramsDecode($this->request->getParam('pass')[1]);
                $recordId = $getId['id'];
                $EducationGradesId = $this->find()->where(['id' => $recordId])->first()->education_grade_id;
                
                if(empty($selectedProgramme)){
                 $programmeId = $this->GpaEducationGrades->find()->where(['id' => $EducationGradesId])->first()->education_programme_id;   
             }else{
                $programmeId = $selectedProgramme;
             }
                $gradeOptions = $this->GpaEducationGrades
                        ->find('list')
                        ->find('visible')
                        ->contain(['EducationProgrammes'])
                        ->where([$this->GpaEducationGrades->aliasField('education_programme_id IS') => $programmeId])
                        ->order(['EducationProgrammes.order' => 'ASC', $this->GpaEducationGrades->aliasField('order') => 'ASC'])
                        ->toArray();
                $EducationGradesId = $this->find()->where(['id' => $recordId])->first()->education_grade_id;
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'education_grade_id') {
            return __('Education Grade');
        } else if ($field == 'gpa_grading_type_id') {
            return  __('Grading Type');
        }else if ($field == 'gpa_education_programme_id') {
            return  __('Education programme');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $data) 
    {
        $request = \Cake\Routing\Router::getRequest();

        if ($request && isset($request->getData()['GpaSystem'])) {
            $gpaData = $request->getData()['GpaSystem'];
            if (isset($gpaData['start_date'])) {
                $entity->start_date = date('Y-m-d', strtotime($gpaData['start_date']));
            }
            if (isset($gpaData['end_date'])) {
                $entity->end_date = date('Y-m-d', strtotime($gpaData['end_date']));
            }
        }
    }

    
    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data) 
    {
        if (isset($entity->start_date) && isset($entity->end_date)) {
            $entity->start_date = date('Y-m-d', strtotime($entity->start_date));
            $entity->end_date = date('Y-m-d', strtotime($entity->end_date));
        }
        $Grades = $entity['education_grades_cumulative_gpa'];
        
    }

    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        //POCOR-9148[START]

        // $this->Gpa = TableRegistry::getTableLocator()->get('Gpa.GpaSystem');
        // $this->Cumulative = TableRegistry::getTableLocator()->get('Gpa.Cumulative');
        // $this->InstitutionStudentsGpa = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentsGpa');

        // // Check if any associated records exist in related tables
        // $associatedRecordsExist =  
        //     $this->InstitutionStudentsGpa->exists([
        //         'education_grades_gpa_id' => $entity->id, 
        //         'education_grade_id' => $entity->education_grade_id
        //     ]) || 
        //     $this->Cumulative->exists([
        //         'main_education_grade_id' => $entity->education_grade_id
        //     ]);
            
        // // If associated records exist, show alert message and abort deletion
        // if (!empty($associatedRecordsExist)) {
        //     $message = __('Delete operation is not allowed. Gpa information linked to this record.');
        //     $this->Alert->error($message, ['type' => 'string', 'reset' => true]);

        //     $url = $this->request->referer(); // Get the referring URL
        //     $event->stopPropagation(); // Stop further propagation of the event
        //     return $entity;
        // }

        if ($this->checkGpaRecords($entity)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        }

        //POCOR-9148[END]
    }

    public function checkGpaRecords($entity)
    {
        $InstitutionStudentsGpa = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentsGpa')->find()->where(['education_grades_gpa_id' =>$entity->id])->count();


        if ($InstitutionStudentsGpa) {
            $result = true;
        }
        return $result;
    }

}

