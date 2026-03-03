<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use DateTime;
use Cake\Utility\Inflector;
use Cake\Log\Log;

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
        $this->addBehavior('Institution.InstitutionTab'); //POCOR-9584: provides getInstitutionID() — reads pass[1], avoids session multi-tab risk
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
        //POCOR-9584: getInstitutionID() reads from URL pass[1]; safe for multi-tab (removed session read)
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

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->getAlias());
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'AssessmentItemResults'];
        $Navigation->substituteCrumb($crumbTitle, 'AssessmentItemResults', $url);
        $Navigation->addCrumb($crumbTitle);
    }

    public function onImportCheckUnique(EventInterface $event, $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols) {
        $tempRow['entity'] = $this->AssessmentItemResults->newEntity();
    }

    public function onImportUpdateUniqueKeys(EventInterface $event, ArrayObject $importedUniqueCodes, Entity $entity) {}

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        if (isset($buttons[1])) {
            $buttons[1]['url'] = $this->ControllerAction->url('index');
            $buttons[1]['url']['action'] = 'Assessments';
        }
        $request = $this->request;
        //POCOR-9584: renamed class_name → institution_class_id
        if (empty($request->getQuery('institution_class_id'))) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

    public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        $request = $this->request;
        $query = $request->getQuery();
        unset($query['institution_class_id']); //POCOR-9584: renamed class_name → institution_class_id
        $this->request = $request->withQueryParams($query);
    }

    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        //POCOR-9584: start - renamed fields to DB column names; added academic_period_id; fixed withQueryParams to merge not replace
        $this->dependency = [];
        $this->dependency['institution_class_id'] = ['education_subject_id', 'select_file'];

        $this->ControllerAction->field('academic_period_id', ['type' => 'string']);
        $this->ControllerAction->field('institution_class_id', ['type' => 'select']);
        $this->ControllerAction->field('education_subject_id', ['type' => 'select']);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['academic_period_id', 'institution_class_id', 'education_subject_id', 'select_file']);

        $currentFieldName = strtolower(str_replace('change', '', $entity->submit));

        if (isset($this->request->getData()[$this->getAlias()])) {
            $alias = $this->getAlias();
            $aryRequestData = $this->request->getData()[$alias];

            foreach ($aryRequestData as $requestData => $value) {
                if (isset($this->dependency[$requestData]) && $value) {
                    $aryDependencies = $this->dependency[$requestData];

                    // Merge POST data into existing URL params — preserves academic_period_id and other URL params
                    $mergedParams = array_merge($this->request->getQueryParams(), $aryRequestData);
                    $this->request = $this->request->withQueryParams($mergedParams);

                    foreach ($aryDependencies as $dependency) {
                        $this->ControllerAction->field($dependency, ['visible' => true]);
                    }
                }
            }
        }
        //POCOR-9584: end
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if (isset($toolbarButtons['back'])) {
            $toolbarButtons['back']['url'] = $this->ControllerAction->url('index');
            $toolbarButtons['back']['url']['action'] = 'Assessments';
        }
    }

    //POCOR-9584: renamed from onUpdateFieldClassName → onUpdateFieldInstitutionClassId to match DB column name
    public function onUpdateFieldInstitutionClassId(EventInterface $event, array $attr, $action, ServerRequest $request) {
        if ($action == 'add') {
            $institutionId = $this->getInstitutionID(); //POCOR-9584: canonical — reads pass[1], avoids session multi-tab risk
            //POCOR-9584: start - guard: no institution in URL yet → return empty options (avoids null IS error in CakePHP5)
            if (empty($institutionId)) {
                $attr['options'] = [];
                return $attr;
            }
            //POCOR-9584: end

            //POCOR-9584: renamed period → academic_period_id; reads table request (updated by addAfterAction) as fallback
            $academicPeriodId = $request->getQuery('academic_period_id')
                ?? $this->request->getQuery('academic_period_id')
                ?? $this->AcademicPeriods->getCurrent();

            $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
            $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
            $classNameOption = $InstitutionClasses->find('list', [
                                    'keyField' => 'id',
                                    'valueField' => 'name'
                                ])
                                ->leftJoin([$InstitutionClassGrades->getAlias() => $InstitutionClassGrades->getTable()],[
                                    $InstitutionClassGrades->aliasField('institution_class_id = ') . $this->InstitutionClasses->aliasField('id')
                                ])
                                ->leftJoin([$this->EducationGrades->getAlias() => $this->EducationGrades->getTable()],[
                                    $this->EducationGrades->aliasField('id = ') . $this->InstitutionClassGrades->aliasField('education_grade_id')
                                ])
                                ->leftJoin([$this->Assessments->getAlias() => $this->Assessments->getTable()], [
                                    $this->Assessments->aliasField('education_grade_id = ') . $this->EducationGrades->aliasField('id')
                                ])
                                ->where([
                                    $InstitutionClasses->aliasField('institution_id') => $institutionId,
                                    $InstitutionClasses->aliasField('academic_period_id') => $academicPeriodId,
                                    $this->Assessments->aliasField('academic_period_id') => $academicPeriodId
                                ])
                                ->toArray();

            $attr['options'] = $classNameOption;
            $attr['onChangeReload'] = 'changeInstitutionClassId'; //POCOR-9584: renamed from changeClassName
        }

        return $attr;
    }

    //POCOR-9584: renamed from onUpdateFieldEducationSubject → onUpdateFieldEducationSubjectId to match DB column name
    public function onUpdateFieldEducationSubjectId(EventInterface $event, array $attr, $action, ServerRequest $request) {
        if ($action == 'add') {
            $institutionId = $this->getInstitutionID(); //POCOR-9584: canonical — reads pass[1], avoids session multi-tab risk
            //POCOR-9584: renamed class_name → institution_class_id; also check table request updated by addAfterAction
            $classId = $request->getData()['ImportAssessmentItemResults']['institution_class_id']
                ?? $this->request->getQuery('institution_class_id')
                ?? null;
            //POCOR-9584: start - guard: no class selected yet → return empty options (avoids null operator error in CakePHP5)
            if (empty($classId)) {
                $attr['options'] = [];
                return $attr;
            }
            //POCOR-9584: end
            //POCOR-9584: renamed period → academic_period_id
            $academicPeriodId = $request->getQuery('academic_period_id')
                ?? $this->request->getQuery('academic_period_id')
                ?? $this->AcademicPeriods->getCurrent();
            $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
            $InstitutionSubjectStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStaff');
            $superAdmin = $this->Auth->user('super_admin');
            $where = [];
            if ($superAdmin != 1) {
                $where[$InstitutionSubjectStaff->aliasField('staff_id')] = $this->Auth->user('id');
            }

            $educationSubjectOption = $this->EducationSubjects->find('list', [
                                        'keyField' => 'id',
                                        'valueField' => 'name'
                                    ])
                                    ->leftJoin([$this->InstitutionSubjects->getAlias() => $this->InstitutionSubjects->getTable()],[
                                        $this->InstitutionSubjects->aliasField('education_subject_id = ') . $this->EducationSubjects->aliasField('id')
                                    ])
                                    ->leftJoin([$InstitutionClassSubjects->getAlias() => $InstitutionClassSubjects->getTable()],[
                                        $InstitutionClassSubjects->aliasField('institution_subject_id = ') . $this->InstitutionSubjects->aliasField('id')
                                    ])
                                    ->leftJoin([$InstitutionSubjectStaff->getAlias() => $InstitutionSubjectStaff->getTable()],[
                                        $InstitutionSubjectStaff->aliasField('institution_subject_id = ') . $this->InstitutionSubjects->aliasField('id')
                                    ])
                                    ->where([
                                        $InstitutionClassSubjects->aliasField('institution_class_id') => $classId,
                                        $where
                                    ])
                                    ->toArray();

            $attr['options'] = $educationSubjectOption;
            $attr['onChangeReload'] = 'changeEducationSubjectId'; //POCOR-9584: renamed from changeEducationSubject
        }

        return $attr;
    }

    //POCOR-9584: show academic_period_id as read-only string — period is set from URL when navigating from Assessments page
    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request) {
        if ($action == 'add') {
            $academicPeriodId = $request->getQuery('academic_period_id')
                ?? $this->request->getQuery('academic_period_id')
                ?? $this->AcademicPeriods->getCurrent();
            $period = $this->AcademicPeriods->find()
                ->select(['id', 'name'])
                ->where(['id' => $academicPeriodId])
                ->first();
            $attr['value'] = $period ? $period->name : '';
            $attr['type'] = 'string';
        }
        return $attr;
    }

    public function onImportPopulateEducationSubjectsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $subjectId = $this->request->getQuery('education_subject_id'); //POCOR-9584: renamed education_subject → education_subject_id
        $academicPeriodId = $this->request->getQuery('academic_period_id') ?? $this->AcademicPeriods->getCurrent(); //POCOR-9584: renamed period → academic_period_id
        $institutionId = $this->getInstitutionID(); //POCOR-9584: canonical — reads pass[1], avoids session multi-tab risk

        $EducationSubjectsResults = $this->EducationSubjects->find()
                        ->select([
                            $this->EducationSubjects->aliasField('id'),
                            $this->EducationSubjects->aliasField('code'),
                            $this->EducationSubjects->aliasField('name')
                        ])
                        ->where([$this->EducationSubjects->aliasField('id') => $subjectId]);

        $translatedReadableCol = $this->getExcelLabel($EducationSubjectsResults, 'Name');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Name', $translatedCol];

        $modelData = $EducationSubjectsResults->find('all')
        ->select([
            'name',
            'code'
        ]);

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->code
                ];
            }
        }
    }

    public function onImportPopulateAssessmentPeriodsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $classId = $this->request->getQuery('institution_class_id'); //POCOR-9584: renamed class_name → institution_class_id
        $educationData = $this->InstitutionClassGrades->find()
                        ->select([$this->InstitutionClassGrades->aliasField('education_grade_id')])
                        ->where([$this->InstitutionClassGrades->aliasField('institution_class_id') => $classId])
                        ->first();
        $educationGradeId = $educationData->education_grade_id;
        $academicPeriodId = $this->request->getQuery('academic_period_id') ?? $this->AcademicPeriods->getCurrent(); //POCOR-9584: renamed period → academic_period_id

        $Assessments = TableRegistry::getTableLocator()->get('Assessment.Assessments');
        $AssessmentPeriods = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriods');

        $assessmentPeriodsResult = $AssessmentPeriods->find()
                        ->select([
                            $AssessmentPeriods->aliasField('id'),
                            $AssessmentPeriods->aliasField('code'),
                            $AssessmentPeriods->aliasField('name')
                        ])
                        ->leftJoin([$Assessments->getAlias() => $Assessments->getTable()], [
                            $AssessmentPeriods->aliasField('assessment_id = ') . $Assessments->aliasField('id')
                        ])
                        ->where([
                            $Assessments->aliasField('academic_period_id') => $academicPeriodId,
                            $Assessments->aliasField('education_grade_id') => $educationGradeId
                        ]);

        $translatedReadableCol = $this->getExcelLabel($assessmentPeriodsResult, 'Name');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Name', $translatedCol];

        $modelData = $assessmentPeriodsResult->find('all')
        ->select([
            'name',
            'code'
        ]);

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->code
                ];
            }
        }
    }

    public function onImportPopulateInstitutionClassesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $classId = $this->request->getQuery('institution_class_id'); //POCOR-9584: renamed class_name → institution_class_id; fixed deprecated array access
        $classData = $this->InstitutionClasses->find()
                        ->select([
                            $this->InstitutionClasses->aliasField('id'),
                            $this->InstitutionClasses->aliasField('name')
                        ])
                        ->where([$this->InstitutionClasses->aliasField('id') => $classId]);

        $translatedReadableCol = $this->getExcelLabel($classData, 'Name');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = ['Name', $translatedCol];

        $modelData = $classData->find('all')
        ->select([
            'name',
            'id'
        ]);

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->id
                ];
            }
        }
    }

    public function onImportGetAssessmentPeriodsId(EventInterface $event, $cellValue)
    {
        /*POCOR-6377 starts*/
        $academicPeriodId = $this->AcademicPeriods->getCurrent();
        $Assessments = TableRegistry::getTableLocator()->get('Assessment.Assessments');
        $dataRecord = $this->AssessmentPeriods->find()
                    ->select([$this->AssessmentPeriods->aliasField('id')])
                    ->leftJoin([$Assessments->getAlias() => $Assessments->getTable()], [
                        $this->AssessmentPeriods->aliasField('assessment_id = ') . $Assessments->aliasField('id')
                    ])
                    ->where([
                        $Assessments->aliasField('academic_period_id') => $academicPeriodId,
                        $this->AssessmentPeriods->aliasField('code') => $cellValue
                    ])->first();
        /*POCOR-6377 ends*/
        $assessmentPeriodsId = $dataRecord->id;

        return $assessmentPeriodsId;
    }

    public function onImportGetInstitutionClassesId(EventInterface $event, $cellValue)
    {
        $record = $this->InstitutionClasses->find()
                ->select([$this->InstitutionClasses->aliasField('id')])
                ->where([$this->InstitutionClasses->aliasField('id') => $cellValue])
                ->first();

        $classId = $record->id;
        return $classId;
    }

    public function onImportGetEducationSubjectsId(EventInterface $event, $cellValue)
    {
        $data = $this->EducationSubjects->find()->select([$this->EducationSubjects->aliasField('id')])->where([$this->EducationSubjects->aliasField('code') => $cellValue])->first();

        $educationSubjectsId = $data->id;

        return $educationSubjectsId;
    }

    public function onImportGetUsersId(EventInterface $event, $cellValue)
    {
        $record = $this->Users->find()->select([$this->Users->aliasField('id')])->where([$this->Users->aliasField('openemis_no') => $cellValue])->first();

        $userId = $record->id;

        return $userId;
    }

    public function onImportPopulateUsersData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        //POCOR-6613 starts
        $enrolledStatus = TableRegistry::getTableLocator()->get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id; // for enrolled status //POCOR-6613 ends
        $classId = $this->request->getQuery('institution_class_id'); //POCOR-9584: renamed class_name → institution_class_id; fixed getQuery[] array access → getQuery()
        $academicPeriodId = $this->request->getQuery('academic_period_id') ?? $this->AcademicPeriods->getCurrent(); //POCOR-9584: renamed period → academic_period_id
        $InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $Users = TableRegistry::getTableLocator()->get('User.Users');
        $studentData = $InstitutionClassStudents->find()
                        ->where([
                            $InstitutionClassStudents->aliasField('institution_class_id') => $classId,
                            $InstitutionClassStudents->aliasField('academic_period_id') => $academicPeriodId,
                            $InstitutionClassStudents->aliasField('student_status_id') => $enrolledStatus //POCOR-6613
                        ])->toArray();
        $studentIds = [];
        if (!empty($studentData)) {
            foreach ($studentData as $value) {
                $studentIds[] = $value->student_id;
            }

            $UsersData = $Users->find()
                            ->select([
                                $Users->aliasField('id'),
                                $Users->aliasField('first_name'),
                                $Users->aliasField('middle_name'),
                                $Users->aliasField('third_name'),
                                $Users->aliasField('last_name'),
                                $Users->aliasField('openemis_no')
                            ])
                            ->where([$Users->aliasField('id IN') => $studentIds]);

            $translatedReadableCol = $this->getExcelLabel($UsersData, 'Name');

            $data[$columnOrder]['lookupColumn'] = 2;
            $data[$columnOrder]['data'][] = ['Name', $translatedCol];

            $modelData = $UsersData->find('all')
            ->select([
                'first_name',
                'middle_name',
                'third_name',
                'last_name',
                'openemis_no'
            ]);

            if (!empty($modelData)) {
                foreach($modelData->toArray() as $row) {
                    $name = $row->first_name . ' ' . $row->middle_name . ' ' . $row->third_name . ' ' . $row->last_name;
                    $data[$columnOrder]['data'][] = [
                        $name,
                        $row->openemis_no
                    ];
                }
            }
        }
    }

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {

        $academicPeriodId = $this->request->getQuery('academic_period_id') ?? $this->AcademicPeriods->getCurrent(); //POCOR-9584: renamed period → academic_period_id; prefer URL param
        $tempRow['institution_id'] = $this->getInstitutionID(); //POCOR-9584: canonical — reads pass[1], avoids session multi-tab risk
        /*POCOR-6528 starts*/
        $this->AssessmentItemsGradingTypes = TableRegistry::getTableLocator()->get('Institution.AssessmentItemsGradingTypes');
        $this->AssessmentGradingTypes = TableRegistry::getTableLocator()->get('Institution.AssessmentGradingTypes');
        /*POCOR-6528 ends*/
        $tempRow['academic_period_id'] = $academicPeriodId;
        $classId = $this->request->getQuery('institution_class_id'); //POCOR-9584: renamed class_name → institution_class_id
        $educationData = $this->InstitutionClassGrades->find()
                        ->select([$this->InstitutionClassGrades->aliasField('education_grade_id')])
                        ->where([$this->InstitutionClassGrades->aliasField('institution_class_id') => $classId])
                        ->first();
        $educationGradeId = $educationData->education_grade_id;
        $tempRow['education_grade_id'] = $educationGradeId;
        $assessment = $this->AssessmentPeriods->find()
                        ->select([$this->AssessmentPeriods->aliasField('assessment_id'), $this->AssessmentPeriods->aliasField('date_disabled')])
                        ->where([$this->AssessmentPeriods->aliasField('id') => $tempRow['assessment_period_id']])
                        ->first();
        $tempRow['assessment_id'] = $assessment->assessment_id;
        $tempRow['institution_classes_id'] = $tempRow['class_id'];
        /*POCOR-6528 starts*/
        $maxvalue = $this->Assessments->find()
        ->select(['maximumvalue' => $this->AssessmentGradingTypes->aliasField('max')])
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
                                ]) // starts POCOR-6682 i've replace to code to ID because wrong code id pick
        //END:POCOR-6640
        ->InnerJoin([$this->AssessmentPeriods->getAlias() => $this->AssessmentPeriods->getTable()],[
                                    $this->AssessmentPeriods->aliasField('assessment_id =') . $this->Assessments->aliasField('id'),
                                    $this->AssessmentPeriods->aliasField('id = ') . $this->AssessmentItemsGradingTypes->aliasField('assessment_period_id') // starts POCOR-6682
                                ])
        ->InnerJoin([$this->InstitutionClassGrades->getAlias() => $this->InstitutionClassGrades->getTable()],[
                                    $this->InstitutionClassGrades->aliasField('education_grade_id =') . $this->Assessments->aliasField('education_grade_id')
                                ])
        ->where([$this->InstitutionClassGrades->aliasField('institution_class_id') => $classId,
                    $this->AssessmentItems->aliasField('education_subject_id') => $tempRow['education_subject_id'], // starts POCOR-6682
                    $this->AssessmentItemsGradingTypes->aliasField('assessment_period_id') => $tempRow['assessment_period_id'] // starts POCOR-6682
                ])
        ->first();
        //START: POCOR-6602

        $today_date = date('Y-m-d');
        if (!empty($assessment)) {
            if (strtotime($today_date) > strtotime($assessment->date_disabled)) {
                $rowInvalidCodeCols['marks'] = __('Date of assement period is expired.');
                $tempRow['marks'] = false;
                return false;
            }
        }
        //END: POCOR-6602
        $maxval = $maxvalue->maximumvalue;
        $value = preg_replace('~\.0+$~', '', $maxval);
        /*POCOR-6528 ends*/
        /*POCOR-6486 starts*/
        $enteredMarks = $tempRow['marks'];
        if (!empty($enteredMarks) && $enteredMarks > 100) {
            $rowInvalidCodeCols['marks'] = __('Marks Should be between 0 to 100');
            $tempRow['marks'] = false;
            return false;

        /*POCOR-6528 starts*/
        } elseif (!empty($enteredMarks) && $enteredMarks > $maxval) {
            $rowInvalidCodeCols['marks'] = __('Marks Should be less then to max Marks');
            $tempRow['marks'] = false;
            return false;
        } elseif (!empty($enteredMarks) && $enteredMarks <= $maxval) { // starts POCOR-6682
            return true;
        } // end POCOR-6682
        /*POCOR-6528 ends*/
        /*POCOR-6486 ends*/
        return true;
    }

    //POCOR-9584: renamed from addEditOnChangeClassName → addEditOnChangeInstitutionClassId to match renamed field
    public function addEditOnChangeInstitutionClassId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->getAlias();
        $classId = $data[$alias]['institution_class_id'] ?? null; //POCOR-9584: renamed class_name → institution_class_id
        $data['class_id'] = $classId;
        // Merge class selection into existing URL params — preserves academic_period_id
        $this->request = $this->request->withQueryParams(
            array_merge($this->request->getQueryParams(), ['institution_class_id' => $classId])
        );
    }

    //POCOR-9584: handler for when education subject changes — stores selection in query params for template download
    public function addEditOnChangeEducationSubjectId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->getAlias();
        $subjectId = $data[$alias]['education_subject_id'] ?? null; //POCOR-9584: renamed education_subject → education_subject_id
        // Merge subject selection into existing URL params — preserves academic_period_id and institution_class_id
        $this->request = $this->request->withQueryParams(
            array_merge($this->request->getQueryParams(), ['education_subject_id' => $subjectId])
        );
    }
}
