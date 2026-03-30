<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use DateTime;
//POCOR-9594: removed dead use PHPExcel_Worksheet — class no longer exists after PhpSpreadsheet upgrade

class ImportStudentMealsTable extends AppTable {
    private $institutionId = false;
    //POCOR-9594: start - store class/period/programme from encoded URL params
    private $institutionClassId = false;
    private $academicPeriodId   = false;
    private $mealProgrammeId    = false;
    //POCOR-9594: end

    public function initialize(array $config): void
    {
        $this->table('import_mapping');
        parent::initialize($config);
        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'InstitutionMealStudents']);
        $this->Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $this->Students = TableRegistry::getTableLocator()->get('Institution.Students');
        $this->Users = TableRegistry::getTableLocator()->get('User.Users');
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $this->InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $this->InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $this->InstitutionMealStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionMealStudents');
    }

    public function beforeAction($event): void
    {
        //POCOR-9594: replaced $this->request->session() (removed in CakePHP 5) with getInstitutionID()
        $this->institutionId      = $this->getInstitutionID();
        //POCOR-9594: start - load class/period/programme IDs encoded in pass[1] by MealRepository
        $this->institutionClassId = $this->getQueryString('institution_class_id');
        $this->academicPeriodId   = $this->getQueryString('academic_period_id');
        $this->mealProgrammeId    = $this->getQueryString('meal_programme_id');
        //POCOR-9594: end
        // Log::debug('@ImportStudentMealsTable::beforeAction institutionId=' . json_encode($this->institutionId) . ' institutionClassId=' . json_encode($this->institutionClassId) . ' academicPeriodId=' . json_encode($this->academicPeriodId) . ' mealProgrammeId=' . json_encode($this->mealProgrammeId)); //[TEMP-LOG]
        $this->systemDateFormat = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('date_format');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
            'Model.import.onImportPopulateUsersData' => 'onImportPopulateUsersData',//POCOR-6681
            'Model.import.onImportPopulateMealReceivedData' => 'onImportPopulateMealReceivedData',
            'Model.import.onImportPopulateMealBenefitData' => 'onImportPopulateMealBenefitData',
            'Model.import.onImportPopulateMealProgrammesData' => 'onImportPopulateMealProgrammesData', //POCOR-9594: fix event key — lookup_model is MealProgrammes (with 's')
            'Model.import.onImportPopulateMealProgrammeData' => 'onImportPopulateMealProgrammesData', //POCOR-9594: fix event key — lookup_model is MealProgrammes (with 's')
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons): void
    {
        if (empty($this->institutionClassId)) { //POCOR-9594: use stored param instead of raw query('class')
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

    public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        $request = $this->request;
        unset($request->query['class']);
    }

    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        //POCOR-9594: start - replace class dropdown with readonly display fields pre-filled from encoded URL params
        $classId     = $this->institutionClassId;
        $periodId    = $this->academicPeriodId;
        $programmeId = $this->mealProgrammeId;

        $className     = $classId     ? $this->InstitutionClasses->get($classId)->name : '';
        $periodName    = $periodId    ? $this->AcademicPeriods->get($periodId)->name    : '';
        $programmeName = '';
        if ($programmeId) {
            $prog = TableRegistry::getTableLocator()->get('Meal.MealProgrammes')->find()->where(['id' => $programmeId])->first();
            $programmeName = $prog ? $prog->name : '';
        }

        $this->ControllerAction->field('institution_class', ['type' => 'readonly', 'attr' => ['value' => $className]]);
        $this->ControllerAction->field('academic_period',   ['type' => 'readonly', 'attr' => ['value' => $periodName]]);
        $this->ControllerAction->field('meal_programme',    ['type' => 'readonly', 'attr' => ['value' => $programmeName]]);
        $this->ControllerAction->field('select_file',       ['visible' => !empty($classId)]);
        $this->ControllerAction->setFieldOrder(['institution_class', 'academic_period', 'meal_programme', 'select_file']);
        //POCOR-9594: end
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportCheckUnique(EventInterface $event, $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols) {
            $tempRow['entity'] = $this->InstitutionMealStudents->newEntity();

    }

    public function onImportUpdateUniqueKeys(EventInterface $event, ArrayObject $importedUniqueCodes, Entity $entity) {
    }
    //POCOR-6681 Starts
    public function onImportPopulateUsersData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $enrolledStatus = TableRegistry::getTableLocator()->get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;// for enrolled status //POCOR-6613 ends
        $InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $StudentData = TableRegistry::getTableLocator()->get('Institution.Students');
        $classId = $this->institutionClassId ?: $this->getQueryString('institution_class_id'); //POCOR-9594: use stored param instead of raw query('class')
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')->select(['id','openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', $lookupColumn]);
        $currentPeriodId = $this->academicPeriodId ?: $this->AcademicPeriods->getCurrent(); //POCOR-9594: use stored param, fall back to current period
        // Log::debug('@ImportStudentMealsTable::onImportPopulateUsersData classId=' . json_encode($classId) . ' currentPeriodId=' . json_encode($currentPeriodId) . ' institutionId=' . json_encode($this->institutionId)); //[TEMP-LOG]
        $allStudents = $this->InstitutionClassStudents
                        ->find('all')
                        // ->innerJoin([$StudentData->alias() => $StudentData->table()], [
                        //     $StudentData->aliasField('student_id = ') . $InstitutionClassStudents->aliasField('student_id')
                        //    ])
                        ->where([
                            $this->InstitutionClassStudents->aliasField('institution_id') => $this->institutionId,
                            $this->InstitutionClassStudents->aliasField('academic_period_id') => $currentPeriodId,
                            $this->InstitutionClassStudents->aliasField('institution_class_id') => $classId,
                            $this->InstitutionClassStudents->aliasField('student_status_id') => $enrolledStatus
                        ])
                        ;
        // when extracting the staff_id from $allStudents collection, there will be no duplicates
        $allStudents = new Collection($allStudents->toArray());

        $modelData->where([
            'id IN' => $allStudents->extract('student_id')->toArray()
        ]);


        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [
            $nameHeader,
            $columnHeader
        ];

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }//POCOR-6681 ends

    /**
     * Currently only populates students based on current academic period
     */


    public function onImportPopulateMealProgrammesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        Log::debug(__CLASS__ . __FUNCTION__);
        // Log::warning('@ImportStudentMealsTable::onImportPopulateMealProgrammesData ENTER institutionId=' . json_encode($this->institutionId) . ' lookupPlugin=' . json_encode($lookupPlugin) . ' lookupModel=' . json_encode($lookupModel) . ' lookupColumn=' . json_encode($lookupColumn) . ' columnOrder=' . json_encode($columnOrder)); //[TEMP-LOG]

        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);

        //POCOR-9594: start - query by meal_programme_id encoded in URL (via MealRepository); no need for MealInstitutionProgrammes institution join which fails when institutionId is false during template action
        $programmeId = $this->mealProgrammeId ?: $this->getQueryString('meal_programme_id');
        // Log::debug('@ImportStudentMealsTable::onImportPopulateMealProgrammesData programmeId=' . json_encode($programmeId) . ' mealProgrammeId=' . json_encode($this->mealProgrammeId)); //[TEMP-LOG]
        $modelData = [];
        if (!empty($programmeId)) {
            $modelData = $lookedUpTable->find('all')
                ->select(['id', 'name', $lookupColumn])
                ->where([$lookedUpTable->aliasField('id') => $programmeId]);
        }
        //POCOR-9594: end

        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [
            $nameHeader,
            $columnHeader
        ];

        if (!empty($modelData)) {
            //POCOR-9594: toArray() only if it's a query object (array when no institution programmes)
            $rows = is_array($modelData) ? $modelData : $modelData->toArray();
            // Log::warning('@ImportStudentMealsTable::onImportPopulateMealProgrammesData rows to write=' . json_encode(array_map(fn($r) => $r->toArray(), $rows))); //[TEMP-LOG]
            foreach($rows as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
        // Log::warning('@ImportStudentMealsTable::onImportPopulateMealProgrammesData EXIT data[columnOrder]=' . json_encode($data[$columnOrder])); //[TEMP-LOG]

    }

    public function onImportPopulateMealReceivedData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')->select(['id', 'name', $lookupColumn]);

        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [
            $nameHeader,
            $columnHeader
        ];

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateMealBenefitData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {

        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')->select(['id', 'name', $lookupColumn]);

        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [
            $nameHeader,
            $columnHeader
        ];


        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }

    }


    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {

        $userId =  $this->Users
                   ->find('all')
                   ->select('id')
                   ->where([
                            $this->Users->aliasField('openemis_no') => $tempRow['OpenEMIS_ID'],
                            ])
                   ->first();
       $tempRow['student_id'] = $userId->id;

        if (empty($tempRow['student_id'])) {
            $rowInvalidCodeCols['student_id'] = __('OpenEMIS ID was not defined');
            return false;
        }

        if (!$this->institutionId) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            return false;
        }

        $tempRow['institution_id'] = $this->institutionId;
        $currentPeriodId = $this->AcademicPeriods->getCurrent();
        $tempRow['academic_period_id'] = $currentPeriodId;
        $classId = $this->request->query('class');
        $tempRow['institution_class_id'] = $classId;

        if (empty($tempRow['date'])) {
            $rowInvalidCodeCols['date'] = __('This field cannot be left empty');
            return false;
        } else {
            // from string to dateObject
            $formattedDate = DateTime::createFromFormat('d/m/Y', $tempRow['date']);
            $tempRow['date'] = $formattedDate;

            $periods = $this->getAcademicPeriodByStartDate($tempRow['date']);

            if (!$periods) {
                $rowInvalidCodeCols['date'] = __('No matching academic period based on the start date');
                $tempRow['academic_period_id'] = false;
                return false;
            }
            $periods = new Collection($periods);
            $periodIds = $periods->extract('id');
            $periodIds = $periodIds->toArray();

            if (!in_array($currentPeriodId, $periodIds)) {
                $currentPeriod = [];
                $currentPeriod =  $this->AcademicPeriods->get($currentPeriodId);
                $rowInvalidCodeCols['date'] = __('Date:- '.$tempRow['date']->format('d/m/Y').' is not within current academic year: '.$currentPeriod->name);
                $tempRow['academic_period_id'] = false;
                return false;
            }
            $tempRow['academic_period_id'] = $currentPeriodId;
        }

        $student = $this->Students->find()->where([
            'academic_period_id' => $tempRow['academic_period_id'],
            'institution_id' => $tempRow['institution_id'],
            'student_id' => $tempRow['student_id'],
        ])->first();

        if (!$student) {
            $rowInvalidCodeCols['OpenEMIS_ID'] = __('No such student in the institution in current academic period.');
            $tempRow['OpenEMIS_ID'] = false;
            return false;
        }

        if($tempRow['meal_received_id'] !=  1 && empty($tempRow['meal_received_id'])) {
            $tempRow['meal_received_id'] = NULL;
        }

        if($tempRow['meal_benefit_id'] !=  1 && empty($tempRow['meal_benefit_id'])) {
            $tempRow['meal_benefit_id'] = NULL;
        }

        return true;
    }

    public function onImportGetPeriodId(EventInterface $event, $cellValue)
    {
        return $cellValue;
    }

    public function onImportSetModelPassedRecord(EventInterface $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow) {
        $flipped = array_flip($columns);
        $original = $originalRow->getArrayCopy();
        $key = $flipped['student_id'];
        $tempPassedRecord['data'][$key] = $original[$key];
    }

    public function onUpdateFieldClass(EventInterface $event, array $attr, $action, Request $request) {
        //die('15');
        if ($action == 'add') {
            $academicPeriodId = !is_null($request->query('period')) ? $request->query('period') : $this->AcademicPeriods->getCurrent();
            //POCOR-9594: replaced complex decode+session fallback with getInstitutionID()
            $institutionId = $this->getInstitutionID();

            $userId = $this->Auth->user('id');
            $AccessControl = $this->AccessControl;


            $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
            $query = $this->InstitutionClasses->find();

            if (!$AccessControl->isAdmin()) {
                if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles)) {
                    $classPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
                    $query->innerJoin(['ClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                        'ClassesSecondaryStaff.institution_class_id = InstitutionClasses.id'
                    ]);
                    if (!$classPermission) {
                        $query->where(['1 = 0'], [], true);
                    } else {
                        $query->where([
                            'OR' => [
                                ['InstitutionClasses.staff_id' => $userId],
                                ['ClassesSecondaryStaff.secondary_staff_id' => $userId]
                            ]
                        ]);
                    }
                }
            }

            $classOptions = $query
                ->find('list')
                ->where([
                    $this->InstitutionClasses->aliasField('academic_period_id') => $academicPeriodId,
                    $this->InstitutionClasses->aliasField('institution_id') => $institutionId])
                ->group([
                    $this->InstitutionClasses->aliasField('id')
                ])
                ->toArray();

            $attr['options'] = $classOptions;
            // useing onChangeReload to do visible
            $attr['onChangeReload'] = 'changeClass';
        }

        return $attr;
    }

}
