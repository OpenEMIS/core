<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
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

    public function initialize(array $config): void
    {
        /**
         * Initializing the dependencies
         * @param array $config
         */
        $this->setTable('summary_assessment_item_results');//POCOR-6848-changed main table as suggest by client
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

   public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data[$this->getAlias()]['feature'] == 'Report.Assessments') {
            $options['validate'] = 'assessments';
        }elseif($data[$this->getAlias()]['feature'] == 'Report.Performance'){
            $options['validate'] = 'performance';
        }elseif($data[$this->getAlias()]['feature'] == 'Report.OutcomesResult'){
            $options['validate'] = 'OutcomesResult';
        }
    }

    public function validationAssessments(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('institution_type_id') //POCOR-9451
            ->notEmpty('institution_id')
            ->notEmpty('education_grade_id')
            ->notEmpty('area_level_id')
            ->notEmpty('area_education_id')
            ->notEmpty('education_programme_id') //POCOR-9443
            ->notEmpty('assessment_period_id')
            ->notEmpty('academic_term');
       return $validator;
    }
    public function validationPerformance(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('institution_id')
            ->notEmpty('education_grade_id')
            ->notEmpty('area_level_id')
            ->notEmpty('area_education_id')
            ->notEmpty('education_programme_id'); //POCOR-9443
       return $validator;
    }

    public function validationOutcomesResult(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('institution_id')
            ->notEmpty('education_grade_id')
            ->notEmpty('area_level_id')
            ->notEmpty('area_education_id')
            ->notEmpty('outcome_period')
            ->notEmpty('education_programme_id'); //POCOR-9443
       return $validator;
    }
    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('institution_type_id', ['type' => 'hidden', 'attr' => ['required' => true]]);//POCOR-9451
        $this->ControllerAction->field('institution_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('education_programme_id', ['type' => 'hidden', 'attr' => ['required' => true]]); //POCOR-9443
        $this->ControllerAction->field('education_grade_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('assessment_period_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('academic_term', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('outcome_period', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('education_subject_id', ['type' => 'hidden', 'attr' => ['required' => false]]); //POCOR-9484
        $this->ControllerAction->field('Competencies_period', ['type' => 'hidden', 'attr' => ['required' => false]]); //POCOR-9077
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $option = $this->controller->getFeatureOptions($this->getAlias());
            $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $defaultFeatureValue = key($option);
                $this->request = $this->request->withData($this->getAlias() . '.feature', $defaultFeatureValue);
            }
        }
            return $attr;
    }

    public function addBeforeAction(EventInterface $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['label'=>'Area Name','required' => true]]); //POCOR-7415
        $this->ControllerAction->field('institution_type_id', ['type' => 'hidden', 'attr' => ['required' => true]]);//POCOR-9451
        $this->ControllerAction->field('institution_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
         $this->ControllerAction->field('education_programme_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
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
    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $academicPeriodOptions = $this->AcademicPeriods->getYearList();
            $currentPeriod = $this->AcademicPeriods->getCurrent();

            $attr['options'] = $academicPeriodOptions;
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['onChangeReload'] = true;

            if (empty($this->request->getData($this->getAlias())['academic_period_id'])) {
                $this->request->getData($this->getAlias())['academic_period_id'] = $currentPeriod;
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
    public function onUpdateFieldAreaLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
                $feature = $this->request->getData($this->getAlias())['feature'];
                $Areas = TableRegistry::getTableLocator()->get('Area.AreaLevels');
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
    public function onUpdateFieldAreaEducationId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $condition = [];
            $areaLevel = $request->getData($this->getAlias())['area_level_id'];
            if (!empty($areaLevel) && $areaLevel > 0) {
                $condition[$this->Areas->aliasField('area_level_id')] = $areaLevel;
            }
            $areaOptions = $this->Areas->find('list', [
                                'keyField' => 'id',
                                'valueField' => 'code_name'
                            ])
                            ->where($condition)
                            ->toArray();
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = ['' => '-- ' . __('Select') . ' --', 0 => __('All Areas')] + $areaOptions;
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }
    //POCOR-9451 start
    /**
     * Fetching Institution Type's options list.
     *
     * @param  \Cake\Http\ServerRequest  $request
     * @return attr
     */
    public function onUpdateFieldInstitutionTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature']) && $this->request->getData($this->getAlias())['feature'] == 'Report.Assessments') {
            $InstitutionTypes = TableRegistry::getTableLocator()->get('Institution.Types');
            
            $institutionTypeOptions = $InstitutionTypes
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'name'
                ])
                ->order([$InstitutionTypes->aliasField('name') => 'ASC'])
                ->toArray();
            
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = ['' => '-- ' . __('Select') . ' --', 0 => __('All Institution Types')] + $institutionTypeOptions;
            $attr['onChangeReload'] = true;
        }
        
        return $attr;
    }
    //POCOR-9451 end
    /**
     * Fetching Institution's options list based on area id.
     *
     * @param  \Cake\Network\Request  $request
     * @return attr
     */
    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData($this->getAlias())['feature'])) {
            $condition = [];
            $areaId = $request->getData($this->getAlias())['area_education_id'];
            if (!empty($areaId) && $areaId > 0) {
                // Same as Report.Institutions / Institution Positions Summaries: use nested set (lft/rght) to include selected area and all descendants
                $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                $areaEntity = $Areas->get($areaId);
                $areaIds = [$areaId];
                if ($areaEntity->has('lft') && $areaEntity->has('rght') && $areaEntity->lft !== null && $areaEntity->rght !== null) {
                    $areaFilter = $Areas->find('all')
                        ->select(['id' => $Areas->aliasField('id')])
                        ->where([
                            $Areas->aliasField('lft') . ' >=' => $areaEntity->lft,
                            $Areas->aliasField('rght') . ' <=' => $areaEntity->rght,
                        ])
                        ->toArray();
                    $areaIds = [];
                    foreach ($areaFilter as $area) {
                        $areaIds[] = $area->id;
                    }
                }
                $condition[$this->Institutions->aliasField('area_id') . ' IN'] = $areaIds;
            }
            //POCOR-9451 start
            $institutionTypeId = $request->getData($this->getAlias())['institution_type_id'];
            if (!empty($institutionTypeId) && $institutionTypeId > 0) {
                $condition[$this->Institutions->aliasField('institution_type_id')] = $institutionTypeId;
            }
            //POCOR-9451 end
            $institutionQuery = $this->Institutions
                            ->find('list', [
                                'keyField' => 'id',
                                'valueField' => 'code_name'
                            ])
                            ->where($condition)
                            ->order([
                                $this->Institutions->aliasField('code') => 'ASC',
                                $this->Institutions->aliasField('name') => 'ASC'
                            ]);
            // if user is not super admin then list will be filtered
            $superAdmin = $this->Auth->user('super_admin');
            if (!$superAdmin) {
                $userId = $this->Auth->user('id');
                $institutionQuery = $institutionQuery->find('byAccess', ['userId' => $userId]);
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

    //POCOR-9404
    public function onUpdateFieldEducationProgrammeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $request = $this->request;
            $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
            $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodId = !is_null($request->getData($this->aliasField('academic_period_id'))) ? $request->getData($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();
                $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('visible')
                    ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    ->order(['EducationCycles.order' => 'ASC', $EducationProgrammes->aliasField('order') => 'ASC'])
                    ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
                    ->toArray();
                $programmeOptionList = [0 => __('Select')] + $programmeOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $programmeOptionList;
                $attr['onChangeReload'] = 'true';
        return $attr;
    }

    /**
     * Fetching education grade's options list based on education programme id.
     *
     * @param  $request
     * @return attr
     */
    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $institutionId = $request->getData($this->getAlias())['institution_id'];
        $academicPeriodId = $this->request->getData($this->getAlias())['academic_period_id'];
        if(isset($academicPeriodId)){
            $academicPeriodId = $this->request->getData($this->getAlias())['academic_period_id'];
        }else{
            $academicPeriodId = '';
        }
        $selectedProgramme = isset($this->request->getData()[$this->getAlias()]['education_programme_id']) 
            ? $this->request->getData()[$this->getAlias()]['education_programme_id'] 
            : null; //POCOR-9404
        
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
        //The grade displayed here, how many grades are assigned in the institution_grade table
        $gradeOptions = $this->EducationGrades
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'name'
                        ])
                        ->leftJoin([$gradeTable->getAlias() => $gradeTable->getTable()], [
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
                        ]) ->where([$this->EducationGrades->aliasField('education_programme_id IS') => $selectedProgramme])
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
     * @param  \Cake\ServerRequest\Request  $request
     * @return attr
     */
    public function onUpdateFieldAssessmentPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if($this->request->getData()['Performance']['feature'] == 'Report.Performance' || $this->request->getData()['Performance']['feature'] == 'Report.Assessments'){
            $gradeId = $request->getData($this->getAlias())['education_grade_id'];
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodId = $request->getData($this->getAlias())['academic_period_id'] ?? $AcademicPeriods->getCurrent();
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
                            ->leftJoin([$this->Assessments->getAlias() => $this->Assessments->getTable()], [
                                $this->Assessments->aliasField('id = ') . $this->AssessmentPeriods->aliasField('assessment_id')
                            ])
                            ->where([$condition])
                            ->toArray();
           
            if (count($assessmentPeriodList) >= 1) {
                $assessmentPeriodOption = $assessmentPeriodList;
            } else {
                $assessmentPeriodOption = ['' => '-- ' . __('Select') . ' --'] + $assessmentPeriodList;
            }
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['type'] = 'chosenSelect';
            $attr['onChangeReload'] = false;
            $attr['attr']['multiple'] = true;
            $attr['options'] = $assessmentPeriodOption;
            return $attr;
        }
    }

    /**
     * Fetching Academic Term's options list based on grade id.
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     * @param  \Cake\ServerRequest\Request  $request
     * @return attr
     */
    public function onUpdateFieldAcademicTerm(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if($this->request->getData()['Performance']['feature'] == 'Report.Performance' || $this->request->getData()['Performance']['feature'] == 'Report.Assessments'){
            $assessmentPeriodId = $this->request->getData($this->getAlias())['assessment_period_id'];
            //POCOR-9484 query changes
            $query = $this->AssessmentPeriods
                ->find()
                ->select(['academic_term'])
                ->where([
                    $this->AssessmentPeriods->aliasField('academic_term IS NOT') => null,
                    $this->AssessmentPeriods->aliasField('academic_term !=') => ''
                ]);

            if ($assessmentPeriodId > 0) {
                $query->where([
                    $this->AssessmentPeriods->aliasField('id IN') => $assessmentPeriodId['_ids']
                ]);
            }

            $academicTerm = $query
                ->distinct([$this->AssessmentPeriods->aliasField('academic_term')])
                ->enableHydration(false)
                ->toArray();

            $academicTermList = [];
            foreach ($academicTerm as $val) {
                $academicTermList[$val['academic_term']] = $val['academic_term'];
            }

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

    }

    /**
     * Fetching Assessment Period's report content
     *
     * @param  \ArrayObject  $settings
     * @return query
     */
    public function onExcelBeforeQuery (EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $gradeId = $requestData->education_grade_id;
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
            $conditions[$this->aliasField('assessment_period_id IN')] = $assessmentPeriodId['_ids']; //POCOR-9575
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
    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'format':
                return __('Format');
            case 'academic_period_id':
                return __('Academic Period');
            case 'area_level_id':
                return __('Area Level');
            case 'institution_id':
                return __('Institution');
            case 'assessment_period_id':
                return __('Assessment Period');
            case 'education_grade_id':
                return __('Education Grade');
            case 'academic_term':
                return __('Academic Term');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateFieldOutcomePeriod(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $requestData = $request->getData();

        if (
            isset($requestData['Performance']['feature']) &&
            $requestData['Performance']['feature'] === 'Report.OutcomesResult'
        ) {
            $data = $requestData[$this->getAlias()] ?? [];
            $academicPeriodId = $data['academic_period_id'] ?? null;

            if (!$academicPeriodId) {
                return $attr; // No period selected, return default attr
            }

            $outcomePeriodsTable = TableRegistry::getTableLocator()->get('Outcome.OutcomePeriods');

            $outcomePeriodList = $outcomePeriodsTable
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'name'
                ])
                ->where([
                    $outcomePeriodsTable->aliasField('academic_period_id') => $academicPeriodId
                ])
                ->toArray();

            $attr['type'] = 'select';
            $attr['select'] = false;

            if (count($outcomePeriodList) > 1) {
                $attr['options'] = ['' => '-- ' . __('Select') . ' --', 0 => __('All Periods')] + $outcomePeriodList;
            } else {
                $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $outcomePeriodList;
            }

            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

    //POCOR-9484
    public function onUpdateFieldEducationSubjectId(EventInterface $event, array $attr, $action, $request)
    {
        $requestData = $request->getData();
        if (
            isset($requestData['Performance']['feature']) &&
            $requestData['Performance']['feature'] == 'Report.Assessments'
        ) {
            $academicPeriodId   = $request->getData($this->aliasField('academic_period_id'));
            $assessmentPeriodId = $request->getData($this->aliasField('assessment_period_id'));
            $academicTerm       = $request->getData($this->aliasField('academic_term'));
            $AssessmentItems   = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems');
            $Assessments       = TableRegistry::getTableLocator()->get('Assessment.Assessments');
            $AssessmentPeriods = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriods');
            $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');

            $conditions = [];
            if (!empty($academicPeriodId)) {
                $conditions[$Assessments->aliasField('academic_period_id')] = $academicPeriodId;
            }
            if (!empty($assessmentPeriodId)) {
                if (is_array($assessmentPeriodId) && isset($assessmentPeriodId['_ids'])) {
                    $conditions[$AssessmentPeriods->aliasField('id IN')] = $assessmentPeriodId['_ids']; //POCOR-9575
                } else {
                    $conditions[$AssessmentPeriods->aliasField('id')] = $assessmentPeriodId;
                }
            }

            if (!empty($academicTerm) && $academicTerm > 0) {
                $conditions[$AssessmentPeriods->aliasField('academic_term')] = $academicTerm;
            }

            $subjects = $AssessmentItems->find()
                ->select([
                    'id'   => $EducationSubjects->aliasField('id'),
                    'name' => $EducationSubjects->aliasField('name')
                ])
                ->innerJoin(
                    [$Assessments->getAlias() => $Assessments->getTable()],
                    "{$Assessments->aliasField('id')} = {$AssessmentItems->aliasField('assessment_id')}"
                )

                ->leftJoin(
                    [$AssessmentPeriods->getAlias() => $AssessmentPeriods->getTable()],
                    "{$AssessmentPeriods->aliasField('assessment_id')} = {$Assessments->aliasField('id')}"
                )

                ->innerJoin(
                    [$EducationSubjects->getAlias() => $EducationSubjects->getTable()],
                    "{$EducationSubjects->aliasField('id')} = {$AssessmentItems->aliasField('education_subject_id')}"
                )

                ->where($conditions)
                ->group([
                    $EducationSubjects->aliasField('id'),
                    $EducationSubjects->aliasField('name')
                ])
                ->order([$EducationSubjects->aliasField('name') => 'ASC'])
                ->enableHydration(false)
                ->toArray();
            $allowedSubjects = [];
            foreach ($subjects as $val) {
                $allowedSubjects[$val['id']] = $val['name'];
            }
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['type'] = 'chosenSelect';
            $attr['onChangeReload'] = false;
            $attr['attr']['multiple'] = true;
            $attr['options'] = $allowedSubjects;
        }
        return $attr;
    }

    //POCOR-9077
    public function onUpdateFieldCompetenciesPeriod(EventInterface $event, array $attr,$action,ServerRequest $request) 
    {
        $requestData = $request->getData();

        if (
            isset($requestData['Performance']['feature']) &&
            $requestData['Performance']['feature'] === 'Report.PerformanceCompetencies'
        ) {
            $academicPeriodId = $request->getData($this->aliasField('academic_period_id'));
            $institutionId    = $request->getData($this->aliasField('institution_id'));
            $educationGradeId = $request->getData($this->aliasField('education_grade_id'));

            $CompetencyPeriods = TableRegistry::getTableLocator()
                ->get('Competency.CompetencyPeriods');

            $query = $CompetencyPeriods->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->innerJoin(
                ['InstitutionCompetencyResults' => 'institution_competency_results'],
                [
                    'InstitutionCompetencyResults.competency_period_id = CompetencyPeriods.id'
                ]
            )
            ->where([
                'InstitutionCompetencyResults.academic_period_id' => $academicPeriodId
            ])
            ->distinct(['CompetencyPeriods.id']);

            if (!empty($institutionId) && $institutionId != -1) {
                $query->where([
                    'InstitutionCompetencyResults.institution_id' => $institutionId
                ]);
            }

            if (!empty($educationGradeId) && $educationGradeId != -1) {
                $query
                    ->innerJoin(
                        ['InstitutionClassStudents' => 'institution_class_students'],
                        [
                            'InstitutionClassStudents.student_id = InstitutionCompetencyResults.student_id',
                            'InstitutionClassStudents.institution_id = InstitutionCompetencyResults.institution_id',
                            'InstitutionClassStudents.academic_period_id = InstitutionCompetencyResults.academic_period_id'
                        ]
                    )
                    ->where([
                        'InstitutionClassStudents.education_grade_id' => $educationGradeId
                    ]);
            }

            $attr['options'] = [-1 => __('All Competencies Period')] + $query->toArray();
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

}