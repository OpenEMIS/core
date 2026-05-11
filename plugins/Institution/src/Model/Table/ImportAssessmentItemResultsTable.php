<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Cake\Utility\Text;

class ImportAssessmentItemResultsTable extends AppTable {
    private $institutionId = false;

    public function initialize(array $config): void {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin'=>'Institution',
            'model'=>'AssessmentItemResults',
            'backUrl' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Assessments']
        ]);
        $this->addBehavior('Institution.InstitutionTab'); //POCOR-9584: provides getInstitutionID()

        // register table once
        $this->AssessmentItemResults = TableRegistry::getTableLocator()->get('Institution.AssessmentItemResults');
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $this->EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
        $this->EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $this->InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
        $this->Assessments = TableRegistry::getTableLocator()->get('Assessment.Assessments');
        $this->AssessmentItems = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems');
        $this->AssessmentPeriods = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriods');
        $this->InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
        $this->Student = TableRegistry::getTableLocator()->get('Security.Users');
        $this->InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $this->InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
        $this->Users = TableRegistry::getTableLocator()->get('User.Users');
    }

    public function beforeAction($event) {
        $this->institutionId = $this->getInstitutionID();
        $this->systemDateFormat = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('date_format');
    }

    public function implementedEvents(): array {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
            'Model.import.onImportPopulateAssessmentPeriodsData' => 'onImportPopulateAssessmentPeriodsData',
            'Model.import.onImportPopulateEducationSubjectsData' => 'onImportPopulateEducationSubjectsData',
            'Model.import.onImportPopulateUsersData' => 'onImportPopulateUsersData',
            'Model.import.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component\NavigationComponent $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->getAlias());
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'AssessmentItemResults'];
        $Navigation->substituteCrumb($crumbTitle, 'AssessmentItemResults', $url);
        $Navigation->addCrumb($crumbTitle);
    }

    public function onImportCheckUnique(EventInterface $event, $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols) {
        $tempRow['entity'] = $this->AssessmentItemResults->newEntity([]);
    }

    public function onImportUpdateUniqueKeys(EventInterface $event, ArrayObject $importedUniqueCodes, Entity $entity) {}

    public function validationDefault(\Cake\Validation\Validator $validator): \Cake\Validation\Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->notEmptyString('academic_period_id')
            ->notEmptyString('institution_class_id')
            ->notEmptyString('education_subject_id')
            ->notEmptyString('select_file');
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        if (isset($buttons[1])) {
            $buttons[1]['url'] = $this->ControllerAction->url('index');
            $buttons[1]['url']['action'] = 'Assessments';
        }
        $request = $this->request;
        //POCOR-9584: buttons visible only when selection is complete
        $alias = $this->getAlias();
        $educationSubjectId = $request->getData($alias . '.education_subject_id') 
            ?? $this->request->getQuery('education_subject_id'); 

        if (empty($educationSubjectId)) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

    public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        $request = $this->request;
        // Intellectual clear: only on fresh GET without selection
        $classId = $request->getData($this->getAlias() . '.institution_class_id')
            ?? $this->request->getQuery('institution_class_id');

        if ($request->is('post') || ($request->is('get') && $classId)) {
            return;
        }

        $query = $request->getQueryParams();
        unset($query['academic_period_id']);
        unset($query['institution_class_id']);
        unset($query['education_subject_id']);
        $this->request = $request->withQueryParams($query);
    }

    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        //POCOR-9584: standardized fields to DB columns; implemented sequential dependency and reset logic
        $this->dependency = [];
        $this->dependency['academic_period_id'] = ['institution_class_id'];
        $this->dependency['institution_class_id'] = ['education_subject_id'];
        $this->dependency['education_subject_id'] = ['select_file'];

        $this->ControllerAction->field('academic_period_id', ['type' => 'select', 'visible' => true]);
        $this->ControllerAction->field('institution_class_id', ['type' => 'select', 'visible' => true]);
        $this->ControllerAction->field('education_subject_id', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['academic_period_id', 'institution_class_id', 'education_subject_id', 'select_file']);

        $currentFieldName = strtolower(str_replace('change', '', (string)$entity->submit));
        $alias = $this->getAlias();

        if (isset($this->request->getData()[$alias])) {
            $unsetFlag = false;
            $aryRequestData = $this->request->getData()[$alias];

            foreach ($aryRequestData as $requestData => $value) {
                $query = $this->request->getQueryParams();
                $data = $this->request->getData();

                if ($unsetFlag) {
                    unset($query[$requestData]);
                    $data[$alias][$requestData] = 0;
                }

                if ($currentFieldName == str_replace('_', '', $requestData)) {
                    $unsetFlag = true;
                }

                $this->request = $this->request->withQueryParams($query);
                $this->request = $this->request->withParsedBody($data);
            }

            // Set visibility and populate query params for template download
            $aryRequestData = $this->request->getData()[$alias];
            foreach ($aryRequestData as $requestData => $value) {
                if (isset($this->dependency[$requestData]) && !empty($value)) {
                    // Populate current level into query string
                    $this->request = $this->request->withQueryParams(
                        array_merge($this->request->getQueryParams(), [$requestData => $value])
                    );
                    foreach ($this->dependency[$requestData] as $dependency) {
                        $this->ControllerAction->field($dependency, ['visible' => true]);
                    }
                }
            }
        }
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if (isset($toolbarButtons['back'])) {
            $toolbarButtons['back']['url'] = $this->ControllerAction->url('index');
            $toolbarButtons['back']['url']['action'] = 'Assessments';
        }
    }

    public function onUpdateFieldInstitutionClassId(EventInterface $event, array $attr, $action, ServerRequest $request) {
        if ($action == 'add') {
            $institutionId = $this->getInstitutionID();
            if (empty($institutionId)) {
                $attr['options'] = [];
                return $attr;
            }

            $alias = $this->getAlias();
            $academicPeriodId = $request->getData($alias . '.academic_period_id')
                ?? $request->getQuery('academic_period_id')
                ?? $this->AcademicPeriods->getCurrent();

            $classNameOption = $this->InstitutionClasses->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                                ->leftJoin(['InstitutionClassGrades' => 'institution_class_grades'],['InstitutionClassGrades.institution_class_id = InstitutionClasses.id'])
                                ->leftJoin(['EducationGrades' => 'education_grades'],['EducationGrades.id = InstitutionClassGrades.education_grade_id'])
                                ->leftJoin(['Assessments' => 'assessments'], ['Assessments.education_grade_id = EducationGrades.id'])
                                ->where([
                                    'InstitutionClasses.institution_id' => $institutionId,
                                    'InstitutionClasses.academic_period_id' => $academicPeriodId,
                                    'Assessments.academic_period_id' => $academicPeriodId
                                ])
                                ->toArray();

            $attr['options'] = $classNameOption;
            $attr['onChangeReload'] = 'changeInstitutionClassId';
        }
        return $attr;
    }

    public function onUpdateFieldEducationSubjectId(EventInterface $event, array $attr, $action, ServerRequest $request) {
        if ($action == 'add') {
            $alias = $this->getAlias();
            $classId = $request->getData($alias . '.institution_class_id')
                ?? $request->getQuery('institution_class_id');

            if (empty($classId)) {
                $attr['options'] = [];
                return $attr;
            }

            $academicPeriodId = $request->getData($alias . '.academic_period_id')
                ?? $request->getQuery('academic_period_id')
                ?? $this->AcademicPeriods->getCurrent();
            
            $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
            $InstitutionSubjectStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStaff');
            $superAdmin = $this->Auth->user('super_admin');
            $where = [];
            if ($superAdmin != 1) {
                $where['InstitutionSubjectStaff.staff_id'] = $this->Auth->user('id');
            }

            $educationSubjectOption = $this->EducationSubjects->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                                    ->leftJoin(['InstitutionSubjects' => 'institution_subjects'],['InstitutionSubjects.education_subject_id = EducationSubjects.id'])
                                    ->leftJoin(['InstitutionClassSubjects' => $InstitutionClassSubjects->getTable()],['InstitutionClassSubjects.institution_subject_id = InstitutionSubjects.id'])
                                    ->leftJoin(['InstitutionSubjectStaff' => $InstitutionSubjectStaff->getTable()],['InstitutionSubjectStaff.institution_subject_id = InstitutionSubjects.id'])
                                    ->where([
                                        'InstitutionClassSubjects.institution_class_id' => $classId,
                                        'InstitutionSubjects.academic_period_id' => $academicPeriodId,
                                        $where
                                    ])
                                    ->toArray();

            $attr['options'] = $educationSubjectOption;
            $attr['onChangeReload'] = 'changeEducationSubjectId';
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request) {
        if ($action == 'add') {
            $attr['options'] = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            $attr['onChangeReload'] = 'changeAcademicPeriodId';
        }
        return $attr;
    }

    public function onImportPopulateEducationSubjectsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $alias = $this->getAlias();
        $subjectId = $this->request->getData($alias . '.education_subject_id') 
            ?? $this->request->getQuery('education_subject_id');

        $EducationSubjectsResults = $this->EducationSubjects->find()
                        ->select(['id', 'code', 'name'])
                        ->where(['id' => $subjectId]);

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Name', $translatedCol];
        foreach($EducationSubjectsResults as $row) {
            $data[$columnOrder]['data'][] = [$row->name, $row->code];
        }
    }

    public function onImportPopulateAssessmentPeriodsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $alias = $this->getAlias();
        $classId = $this->request->getData($alias . '.institution_class_id') 
            ?? $this->request->getQuery('institution_class_id');
        $academicPeriodId = $this->request->getData($alias . '.academic_period_id') 
            ?? $this->request->getQuery('academic_period_id') 
            ?? $this->AcademicPeriods->getCurrent();
        $educationData = $this->InstitutionClassGrades->find()
                        ->where(['institution_class_id' => $classId])
                        ->first();
        $educationGradeId = $educationData->education_grade_id;

        $assessmentPeriodsResult = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriods')->find()
                        ->select(['AssessmentPeriods.id', 'AssessmentPeriods.code', 'AssessmentPeriods.name'])
                        ->innerJoin(['Assessments' => 'assessments'], ['AssessmentPeriods.assessment_id = Assessments.id'])
                        ->where([
                            'Assessments.academic_period_id' => $academicPeriodId,
                            'Assessments.education_grade_id' => $educationGradeId
                        ]);

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Name', $translatedCol];
        foreach($assessmentPeriodsResult as $row) {
            $data[$columnOrder]['data'][] = [$row->name, $row->code];
        }
    }

    public function onImportPopulateInstitutionClassesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $alias = $this->getAlias();
        $classId = $this->request->getData($alias . '.institution_class_id') 
            ?? $this->request->getQuery('institution_class_id');

        $classData = $this->InstitutionClasses->find()
                        ->select(['id', 'name'])
                        ->where(['id' => $classId]);

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Name', $translatedCol];
        foreach($classData as $row) {
            $data[$columnOrder]['data'][] = [$row->name, $row->id];
        }
    }

    public function onImportGetAssessmentPeriodsId(EventInterface $event, $cellValue)
    {
        $academicPeriodId = $this->AcademicPeriods->getCurrent();
        $dataRecord = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriods')->find()
                    ->innerJoin(['Assessments' => 'assessments'], ['AssessmentPeriods.assessment_id = Assessments.id'])
                    ->where([
                        'Assessments.academic_period_id' => $academicPeriodId,
                        'AssessmentPeriods.code' => $cellValue
                    ])->first();
        return $dataRecord->id;
    }

    public function onImportGetInstitutionClassesId(EventInterface $event, $cellValue)
    {
        $record = $this->InstitutionClasses->find()->where(['id' => $cellValue])->first();
        return $record->id;
    }

    public function onImportGetEducationSubjectsId(EventInterface $event, $cellValue)
    {
        $data = $this->EducationSubjects->find()->where(['code' => $cellValue])->first();
        return $data->id;
    }

    public function onImportGetUsersId(EventInterface $event, $cellValue)
    {
        $record = $this->Users->find()->where(['openemis_no' => $cellValue])->first();
        return $record->id;
    }

    public function onImportPopulateUsersData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $alias = $this->getAlias();
        $classId = $this->request->getData($alias . '.institution_class_id') 
            ??  $this->request->getQuery('institution_class_id');
        $academicPeriodId = $this->request->getData($alias . '.academic_period_id') 
            ??  $this->request->getQuery('academic_period_id') 
            ?? $this->AcademicPeriods->getCurrent();

        $enrolledStatus = TableRegistry::getTableLocator()->get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;

        $studentIds = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents')->find()
                        ->where([
                            'institution_class_id' => $classId,
                            'academic_period_id' => $academicPeriodId,
                            'student_status_id' => $enrolledStatus
                        ])->extract('student_id')->toArray();

        if (!empty($studentIds)) {
            $UsersData = $this->Users->find()
                            ->select(['id', 'first_name', 'middle_name', 'third_name', 'last_name', 'openemis_no'])
                            ->where(['id IN' => $studentIds]);

            $data[$columnOrder]['lookupColumn'] = 2;
            $data[$columnOrder]['data'][] = ['Name', $translatedCol];
            foreach($UsersData as $row) {
                $name = $row->first_name . ' ' . $row->middle_name . ' ' . $row->third_name . ' ' . $row->last_name;
                $data[$columnOrder]['data'][] = [$name, $row->openemis_no];
            }
        }
    }

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
        $alias = $this->getAlias();
        $requestData = $this->request->getData($alias);
        
        $tempRow['academic_period_id'] = $requestData['academic_period_id'];
        $tempRow['institution_class_id'] = $requestData['institution_class_id'];
        $tempRow['education_subject_id'] = $requestData['education_subject_id'];
        $tempRow['institution_id'] = $this->getInstitutionID();

        $classId = $tempRow['institution_class_id'];
        $educationData = $this->InstitutionClassGrades->find()->where(['institution_class_id' => $classId])->first();
        $tempRow['education_grade_id'] = $educationData->education_grade_id;

        $assessment = $this->AssessmentPeriods->find()
                        ->where(['id' => $tempRow['assessment_period_id']])
                        ->first();
        $tempRow['assessment_id'] = $assessment->assessment_id;

        /*$maxvalue = TableRegistry::getTableLocator()->get('Assessment.Assessments')->find()
            ->select(['maximumvalue' => 'AssessmentGradingTypes.max'])
            ->innerJoin(['AssessmentItems' => 'assessment_items'], ['AssessmentItems.assessment_id = Assessments.id'])
            ->innerJoin(['AssessmentItemsGradingTypes' => 'assessment_items_grading_types'], [
                'AssessmentItemsGradingTypes.assessment_id = AssessmentItems.assessment_id',
                'AssessmentItemsGradingTypes.education_subject_id = AssessmentItems.education_subject_id'
            ])
            ->innerJoin(['AssessmentGradingTypes' => 'assessment_grading_types'], [
                'AssessmentGradingTypes.id = AssessmentItemsGradingTypes.assessment_grading_type_id'
            ])
            ->innerJoin(['AssessmentPeriods' => 'assessment_periods'], [
                'AssessmentPeriods.assessment_id = Assessments.id',
                'AssessmentPeriods.id = AssessmentItemsGradingTypes.assessment_period_id'
            ])
            ->innerJoin(['InstitutionClassGrades' => 'institution_class_grades'], ['InstitutionClassGrades.education_grade_id = Assessments.id'])
            ->where([
                'InstitutionClassGrades.institution_class_id' => $classId,
                'AssessmentItems.education_subject_id' => $tempRow['education_subject_id'],
                'AssessmentItemsGradingTypes.assessment_period_id' => $tempRow['assessment_period_id']
            ])->first();*/
            $this->AssessmentItemsGradingTypes = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemsGradingTypes');
		$this->AssessmentGradingTypes = TableRegistry::getTableLocator()->get('Assessment.AssessmentGradingTypes');
        $maxvalue = $this->Assessments->find()
		->select(['maximumvalue' => 'AssessmentGradingTypes.max'])
		 ->InnerJoin([$this->AssessmentItems->getAlias() => $this->AssessmentItems->getTable()],[
                                    $this->AssessmentItems->aliasField('assessment_id = ') . $this->Assessments->aliasField('id')
                                ])
		->InnerJoin([$this->AssessmentItemsGradingTypes->getAlias() => $this->AssessmentItemsGradingTypes->getTable()],[
                                    $this->AssessmentItemsGradingTypes->aliasField('assessment_id = ') . $this->AssessmentItems->aliasField('assessment_id'),
									
                                    $this->AssessmentItemsGradingTypes->aliasField('education_subject_id = ') . $this->AssessmentItems->aliasField('education_subject_id')
                                ])
        //START:POCOR-6640
		// ->InnerJoin([$this->AssessmentGradingTypes->getAlias() => $this->AssessmentGradingTypes->getTable()],[
        //                             $this->AssessmentGradingTypes->aliasField('id =') . $this->AssessmentItemsGradingTypes->aliasField('assessment_grading_type_id')
        ->InnerJoin([$this->AssessmentGradingTypes->getAlias() => $this->AssessmentGradingTypes->getTable()],[
                                   $this->AssessmentGradingTypes->aliasField('id =') . $this->AssessmentItemsGradingTypes->aliasField('assessment_grading_type_id')
                                ])// starts POCOR-6682 i've replace to code to ID because wrong code id pick
        //END:POCOR-6640
		->InnerJoin([$this->AssessmentPeriods->getAlias() => $this->AssessmentPeriods->getTable()],[
                                    $this->AssessmentPeriods->aliasField('assessment_id =') . $this->Assessments->aliasField('id'),

                                    $this->AssessmentPeriods->aliasField('id = ') . $this->AssessmentItemsGradingTypes->aliasField('assessment_period_id')	// starts POCOR-6682
                                ])									
		->InnerJoin([$this->InstitutionClassGrades->getAlias() => $this->InstitutionClassGrades->getTable()],[
                                    $this->InstitutionClassGrades->aliasField('education_grade_id =') . $this->Assessments->aliasField('education_grade_id')
                                ])
		->where([$this->InstitutionClassGrades->aliasField('institution_class_id') => $classId,
                    $this->AssessmentItems->aliasField('education_subject_id') => $tempRow['education_subject_id'],// starts POCOR-6682
                    $this->AssessmentItemsGradingTypes->aliasField('assessment_period_id') => $tempRow['assessment_period_id']// starts POCOR-6682
                ])
		->first();
        if ($assessment && strtotime(date('Y-m-d')) > strtotime($assessment->date_disabled)) {
            $rowInvalidCodeCols['marks'] = __('Date of assessment period is expired.');
            return false;
        }

        if (!empty($tempRow['marks']) && ($tempRow['marks'] > 100 || $tempRow['marks'] > $maxvalue->maximumvalue)) {
            $rowInvalidCodeCols['marks'] = __('Invalid marks.');
            return false;
        }
        return true;
    }

    public function addEditOnChangeAcademicPeriodId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $this->request = $this->request->withQueryParams(array_merge($this->request->getQueryParams(), ['academic_period_id' => $data[$this->getAlias()]['academic_period_id']]));
    }

    public function addEditOnChangeInstitutionClassId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $this->request = $this->request->withQueryParams(array_merge($this->request->getQueryParams(), ['institution_class_id' => $data[$this->getAlias()]['institution_class_id']]));
    }

    public function addEditOnChangeEducationSubjectId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $this->request = $this->request->withQueryParams(array_merge($this->request->getQueryParams(), ['education_subject_id' => $data[$this->getAlias()]['education_subject_id']]));
    }
}
