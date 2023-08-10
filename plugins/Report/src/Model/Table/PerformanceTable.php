<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use App\Model\Traits\OptionsTrait;

/**
 * 
 * This class is used to generate Performance report
 * Where Basic details of Student will be added in report
 * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
 * 
 */
class PerformanceTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        /**
         * Initializing the dependencies
         * @param array $config
         */
        $this->table('summary_assessment_item_results');//POCOR-6848-changed main table as suggest by client
        parent::initialize($config);

        //associations
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Providers', ['className' => 'Institution.Providers', 'foreignKey' => 'institution_provider_id']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects', 'foreignKey' => 'subject_id']);

        //Behaviors
        $this->addBehavior('Excel', [
            'excludes' => [],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('education_grade_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('assessment_period_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('academic_term', ['type' => 'hidden', 'attr' => ['required' => true]]);
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->alias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
            }
            return $attr;
        }
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['label'=>'Area Name','required' => true]]); //POCOR-7415
        $this->ControllerAction->field('institution_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('education_grade_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('assessment_period_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('academic_period_name', ['type' => 'hidden']);
        $this->ControllerAction->field('assessment_id', ['type' => 'hidden']);
        $this->ControllerAction->field('assessment_code', ['type' => 'hidden']);
        $this->ControllerAction->field('assessment_name', ['type' => 'hidden']);
        $this->ControllerAction->field('assessment_period_name', ['type' => 'hidden']);
        $this->ControllerAction->field('education_grade', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_code', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_name', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_provider_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_provider', ['type' => 'hidden']);
        $this->ControllerAction->field('area_name', ['type' => 'hidden']);
        $this->ControllerAction->field('count_students', ['type' => 'hidden']);
        $this->ControllerAction->field('count_marked_students', ['type' => 'hidden']);
        $this->ControllerAction->field('missing_marks', ['type' => 'hidden']);
        $this->ControllerAction->field('academic_term', ['type' => 'hidden', 'attr' => ['required' => true]]);
    }

    /**
     * Fetching academic period's level options
     *
     * @param  \Cake\Network\Request  $request
     * @return attr
     */
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            $academicPeriodOptions = $this->AcademicPeriods->getYearList();
            $currentPeriod = $this->AcademicPeriods->getCurrent();

            $attr['options'] = $academicPeriodOptions;
            $attr['type'] = 'select';
            $attr['select'] = false;

            if (empty($request->data[$this->alias()]['academic_period_id'])) {
                $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
            }
        }

        return $attr;
    }

    /**
     * Fetching area's level options
     *
     * @param  \Cake\Network\Request  $request
     * @return attr
     */
    public function onUpdateFieldAreaLevelId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
                $feature = $this->request->data[$this->alias()]['feature'];
                $Areas = TableRegistry::get('AreaLevel.AreaLevels');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $areaOptions = $Areas
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->order([$Areas->aliasField('level')]);

                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --', 0 => __('All Areas Level')] + $areaOptions->toArray();
                    $attr['onChangeReload'] = true;
                } else {
                    $attr['type'] = 'hidden';
                }
        }
        return $attr;
    }


    /**
     * Fetching area's options list.
     *
     * @param  \Cake\Network\Request  $request
     * @return attr
     */
    public function onUpdateFieldAreaEducationId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            //echo "<pre>";print_r();die();
            $areaLevel = $request->data[$this->alias()]['area_level_id'];
            if ($areaLevel > 0) {
                $condition[$this->Areas->aliasField('area_level_id')] = $areaLevel;
            }
            $areaOptions = $this->Areas->find('list', [
                                'keyField' => 'id',
                                'valueField' => 'code_name'
                            ])
                            ->where([$condition])
                            ->toArray();
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = ['' => '-- ' . __('Select') . ' --', 0 => __('All Areas')] + $areaOptions;
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    /**
     * Fetching Institution's options list based on area id.
     *
     * @param  \Cake\Network\Request  $request
     * @return attr
     */
    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $areaId = $request->data[$this->alias()]['area_education_id'];
            if ($areaId > 0) {
                $condition[$this->Institutions->aliasField('area_id')] = $areaId;
            }
            $institutionQuery = $this->Institutions
                            ->find('list', [
                                'keyField' => 'id',
                                'valueField' => 'code_name'
                            ])
                            ->where([$condition])
                            ->order([
                                $this->Institutions->aliasField('code') => 'ASC',
                                $this->Institutions->aliasField('name') => 'ASC'
                            ]);
            // if user is not super admin than list will be filtered
            $superAdmin = $this->Auth->user('super_admin');
            if (!$superAdmin) {
                $userId = $this->Auth->user('id');
                $institutionQuery->find('byAccess', ['userId' => $userId]);
            }
            $institutionList = $institutionQuery->toArray();
            $attr['type'] = 'select';
            $attr['select'] = false;

            if (count($institutionList) > 1) {
                $institutionOptions = ['' => '-- ' . __('Select') . ' --', 0 => __('All Institutions')] + $institutionList;
            } else {
                $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
            }
            
            $attr['type'] = 'chosenSelect';
            $attr['onChangeReload'] = true;
            $attr['attr']['multiple'] = false;
            $attr['options'] = $institutionOptions;
            $attr['attr']['required'] = true;
        }

        return $attr;
    }

    /**
     * Fetching education grade's options list based on institution id.
     *
     * @param  \Cake\Network\Request  $request
     * @return attr
     */
    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        $institutionId = $request->data[$this->alias()]['institution_id'];
        $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
        $gradeTable = $this->Institutions->InstitutionGrades;
        $institutionIds = [];
        if ($institutionId > 0) {
            $condition[$gradeTable->aliasField('institution_id')] = $institutionId;
        } else {
            $superAdmin = $this->Auth->user('super_admin');
            $userId = $this->Auth->user('id');
            if (!$superAdmin) {
                $institutionObj = $this->Institutions->find('byAccess', ['userId' => $userId])->toArray();
                if (!empty($institutionObj)) {
                    foreach ($institutionObj as $value) {
                        $institutionIds[] = $value->id;
                    }
                }
                $conditions[$gradeTable->aliasField('institution_id IN')] = $institutionIds;
            }
        }
        $gradeOptions = $this->EducationGrades
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'name'
                        ])
                        ->leftJoin([$gradeTable->alias() => $gradeTable->table()], [
                            $gradeTable->aliasField('education_grade_id = ') . $this->EducationGrades->aliasField('id')
                        ])
                        ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                        ->where([
                            'EducationSystems.academic_period_id' => $academicPeriodId,
                            $condition
                        ])
                        ->group([$this->EducationGrades->aliasField('name')])
                        ->order([
                            $this->EducationGrades->aliasField('name') => 'ASC'
                        ])
                        ->toArray();

        $attr['type'] = 'select';
        $attr['select'] = false;
        if (count($gradeOptions) > 1) {
            $grades = ['' => '-- ' . __('Select') . ' --', 0 => __('All Grades')] + $gradeOptions;
        } else {
            $grades = ['' => '-- ' . __('Select') . ' --'] + $gradeOptions;
        }
        $attr['options'] = $grades;
        $attr['onChangeReload'] = true;

        return $attr;
    }

    /**
     * Fetching Assessment Period's options list based on grade id.
     *
     * @param  \Cake\Network\Request  $request
     * @return attr
     */
    public function onUpdateFieldAssessmentPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $gradeId = $request->data[$this->alias()]['education_grade_id'];
        $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
        if ($gradeId > 0) {
            $condition[$this->Assessments->aliasField('education_grade_id')] = $gradeId;
        }
        if (!empty($academicPeriodId)) {
            $condition[$this->Assessments->aliasField('academic_period_id')] = $academicPeriodId;
        }
        $assessmentPeriodList = $this->AssessmentPeriods
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->leftJoin([$this->Assessments->alias() => $this->Assessments->table()], [
                            $this->Assessments->aliasField('id = ') . $this->AssessmentPeriods->aliasField('assessment_id')
                        ])
                        ->where([$condition])
                        ->toArray();

        $attr['type'] = 'select';
        $attr['select'] = false;
        if (count($assessmentPeriodList) > 1) {
            $assessmentPeriodOption = ['' => '-- ' . __('Select') . ' --', 0 => __('All Periods')] + $assessmentPeriodList;
        } else {
            $assessmentPeriodOption = ['' => '-- ' . __('Select') . ' --'] + $assessmentPeriodList;
        }
        $attr['options'] = $assessmentPeriodOption;
        $attr['onChangeReload'] = true;

        return $attr;
    }

    /**
     * Fetching Academic Term's options list based on grade id.
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     * @param  \Cake\Network\Request  $request
     * @return attr
     */
    public function onUpdateFieldAcademicTerm(Event $event, array $attr, $action, Request $request)
    {
        $assessmentPeriodId = $request->data[$this->alias()]['assessment_period_id'];
        if ($assessmentPeriodId > 0) {
            $condition[$this->AssessmentPeriods->aliasField('academic_term')] = $assessmentPeriodId;
        }
        
        $academicTermList = $this->AssessmentPeriods
                        ->find('list', [
                            'keyField' => 'academic_term',
                            'valueField' => 'academic_term'
                        ])
                        ->where([
                            $condition, 
                            $this->AssessmentPeriods->aliasField('academic_term !=') => 'NULL'
                        ])
                        ->toArray();
     
        $attr['type'] = 'select';
        $attr['select'] = false;
        if (count($academicTermList) > 1) {
            $assessmentTermOption = ['' => '-- ' . __('Select') . ' --', 0 => __('All Terms')] + $academicTermList;
        } else {
            $assessmentTermOption = ['' => '-- ' . __('Select') . ' --'] + $academicTermList;
        }
        $attr['options'] = $assessmentTermOption;
        $attr['onChangeReload'] = true;

        return $attr;

    }

    /**
     * Fetching Assessment Period's report content
     *
     * @param  \ArrayObject  $settings
     * @return query
     */
    public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $gradeId = $requestData->education_grade_id;
        $assessmentPeriodId = $requestData->assessment_period_id;
        $assessmentPeriodId = $requestData->assessment_period_id;
        $academicPeriodId = $requestData->academic_period_id;
        $superAdmin = $requestData->super_admin;
        $userId = $requestData->user_id;
        $academicTerm = $requestData->academic_term;//POCOR-6848
        $institutionIds = [];
        $conditions = [];
        if ($areaId > 0) {
            $conditions[$this->aliasField('area_id')] = $areaId;
        }
        if ($gradeId > 0) {
            $conditions[$this->aliasField('education_grade_id')] = $gradeId;
        }
        if ($institutionId > 0) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        } else {//Added condition to get only user's accessiable institution data
            if (!$superAdmin) {
                $institutionObj = $this->Institutions->find('byAccess', ['userId' => $userId])->toArray();
                if (!empty($institutionObj)) {
                    foreach ($institutionObj as $value) {
                        $institutionIds[] = $value->id;
                    }
                }
                $conditions[$this->aliasField('institution_id IN')] = $institutionIds;
            }
        }
        
        if ($assessmentPeriodId > 0) {
            $conditions[$this->aliasField('assessment_period_id')] = $assessmentPeriodId;
        }
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        /**POCOR-6848 starts - added condition to fetch data on the basis of selected academic term*/ 
        if (!empty($academicTerm)) {
            $conditions[$this->aliasField('academic_term')] = $academicTerm;
        }
        /**POCOR-6848 ends*/
        $query
            ->select([
                'academic_period_name' => $this->aliasField('academic_period_name'),
                'institution_code' => $this->aliasField('institution_code'),
                'institution_name' => $this->aliasField('institution_name'),
                'area_name' => $this->aliasField('area_name'),
                'education_grade_name' => $this->aliasField('education_grade'),
                'academic_term' => 'AssessmentPeriods.academic_term',
                'assessment_name' => $this->aliasField('assessment_name'),
                'assessment_period_name' => $this->aliasField('assessment_period_name'),
                'class_name' => $this->aliasField('institution_class_name'),//POCOR-6848
                'subject_name' => $this->aliasField('subject_name'),
                'total_students' =>  $this->aliasField('count_students'),
                'marks_entered' => $this->aliasField('count_marked_students'),
                'missing_marks' => $this->aliasField('missing_marks'),//POCOR-6848
            ])
            ->contain(['AssessmentPeriods'])
            ->where([$conditions,]);
    }

    /**
     * Display selected columns into Assessment Period's report
     *
     * @param  \ArrayObject  $settings
     * @return $fields
     */
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period_name',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'integer',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => 'Area Name',
        ];

        $newFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => 'Education Grade Name',
        ];

        $newFields[] = [
            'key' => 'AssessmentPeriods.academic_term',
            'field' => 'academic_term',
            'type' => 'string',
            'label' => 'Academic Term',
        ];

        $newFields[] = [
            'key' => 'Assessments.name',
            'field' => 'assessment_name',
            'type' => 'string',
            'label' => 'Assessment Name',
        ];

        $newFields[] = [
            'key' => 'AssessmentPeriods.name',
            'field' => 'assessment_period_name',
            'type' => 'string',
            'label' => 'Assessment Period Name',
        ];
        /**POCOR-6848 starts - added class name column into the report*/ 
        $newFields[] = [
            'key' => 'class_name',
            'field' => 'class_name',
            'type' => 'string',
            'label' => 'Class Name',
        ];
        /**POCOR-6848 ends*/ 
        $newFields[] = [
            'key' => 'EducationSubjects.name',
            'field' => 'subject_name',
            'type' => 'string',
            'label' => 'Subject Name',
        ];

        $newFields[] = [
            'key' => 'total_students',
            'field' => 'total_students',
            'type' => 'string',
            'label' => 'Total Students',
        ];

        $newFields[] = [
            'key' => 'marks_entered',
            'field' => 'marks_entered',
            'type' => 'string',
            'label' => 'Marks Entered',
        ];
        /**POCOR-6848 starts - added missing marks column into the report*/
        $newFields[] = [
            'key' => 'missing_marks',
            'field' => 'missing_marks',
            'type' => 'string',
            'label' => 'Missing Marks',
        ];
        /**POCOR-6848 ends*/
        $fields->exchangeArray($newFields);
    }
}