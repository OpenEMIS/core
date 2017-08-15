<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use DateTime;
use PHPExcel_Worksheet;

class ImportStudentAttendancesTable extends AppTable {
    private $institutionId = false;

    public function initialize(array $config) {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'InstitutionStudentAbsences']);

        $this->StudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $this->Institutions = TableRegistry::get('Institution.Institutions');
        $this->Students = TableRegistry::get('Institution.Students');
        $this->Users = TableRegistry::get('User.Users');
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
    }

    public function beforeAction($event) {
        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
        $this->systemDateFormat = TableRegistry::get('Configuration.ConfigItems')->value('date_format');
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
            'Model.import.onImportPopulateUsersData' => 'onImportPopulateUsersData',
            'Model.import.onImportPopulateAbsenceTypesData' => 'onImportPopulateAbsenceTypesData',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols) {
        $tempRow['entity'] = $this->StudentAbsences->newEntity();
        $tempRow['full_day'] = 1;
        $tempRow['institution_id'] = false;
        $tempRow['academic_period_id'] = false;
    }

    public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {}

    /**
     * Currently only populates students based on current academic period
     */
    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $currentPeriodId = $this->AcademicPeriods->getCurrent();
        if (!$currentPeriodId) {
            $array = $this->AcademicPeriods->getAvailableAcademicPeriods();
            reset($array);
            $currentPeriodId = key($array);
        }
        $currentPeriod = $this->AcademicPeriods->get($currentPeriodId);
        $allStudents = $this->Students
                            ->find('all')
                            ->select([
                                'student_id',
                                'EducationGrades.name','EducationGrades.order',
                                'Users.first_name', 'Users.middle_name', 'Users.third_name', 'Users.last_name', 'Users.'.$lookupColumn
                            ])
                            ->where([
                                $this->Students->aliasField('academic_period_id') => $currentPeriodId,
                                $this->Students->aliasField('institution_id') => $this->institutionId,
                                'Users.id IS NOT NULL',
                            ])
                            ->contain([
                                'EducationGrades',
                                'Users'
                            ])
                            // ->join([
                            //  'InstitutionClasseStudents' => [
                            //      'table' => 'institution_class_students',
                            //      'alias' => 'InstitutionClassStudents',
                            //      // 'type' => 'LEFT',
                            //      'conditions' => 'InstitutionClassStudents.student_id = '.$this->Students->aliasField('student_id'),
                            //  ],
                            // ])
                            ->order(['EducationGrades.order'])
                            ;
        $institution = $this->Institutions->get($this->institutionId);
        $institutionHeader = $this->getExcelLabel('Imports', 'institution_id') . ": " . $institution->name;
        $periodHeader = $this->getExcelLabel($lookedUpTable, 'academic_period_id') . ": " . $currentPeriod->name;
        $gradeHeader = $this->getExcelLabel($lookedUpTable, 'education_grade_id');
        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 5;
        $data[$columnOrder]['data'][] = [
            $institutionHeader,
            $periodHeader,
            $gradeHeader,
            $nameHeader,
            $columnHeader
        ];
        if (!empty($allStudents)) {
            foreach($allStudents->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $institution->name,
                    $currentPeriod->name,
                    $row->education_grade->name,
                    $row->user->name,
                    $row->user->{$lookupColumn}
                ];
            }
        }
    }

    // public function onImportPopulateUsersDataBasedOnAllAcademicPeriods(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
    //  $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
    //  $editablePeriods = $this->AcademicPeriods->getAvailableAcademicPeriods();
    //  $allStudents = $this->Students
    //                      ->find('all')
    //                      ->select([
    //                          'EducationGrades.name', 'AcademicPeriods.name', 'AcademicPeriods.order', 'Users.id', 'Users.first_name', 'Users.middle_name', 'Users.third_name', 'Users.last_name', 'Users.'.$lookupColumn
    //                      ])
    //                      ->where([
    //                          $this->Students->aliasField('academic_period_id').' IN' => array_keys($editablePeriods),
    //                          $this->Students->aliasField('institution_id') => $this->institutionId
    //                      ])
    //                      ->contain([
    //                          'EducationGrades',
    //                          'AcademicPeriods',
    //                          'Users'
    //                      ])
    //                      ->order(['AcademicPeriods.order'])
    //                      ;
    //  $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
    //  $periodHeader = $this->getExcelLabel($lookedUpTable, 'academic_period_id');
    //  $gradeHeader = $this->getExcelLabel($lookedUpTable, 'education_grade_id');
    //  $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
    //  $data[$sheetName][] = [
    //      $nameHeader,
    //      $periodHeader,
    //      $gradeHeader,
    //      $columnHeader
    //  ];
    //  if (!empty($allStudents)) {
    //      foreach($allStudents->toArray() as $row) {
    //          $data[$sheetName][] = [
    //              $row->Users->name,
    //              $row->AcademicPeriods->name,
    //              $row->EducationGrades->name,
    //              $row->Users->{$lookupColumn}
    //          ];
    //      }
    //  }
    // }

    public function onImportPopulateAbsenceTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
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

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
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
        if (!$currentPeriodId) {
            $array = $this->AcademicPeriods->getAvailableAcademicPeriods();
            reset($array);
            $currentPeriodId = key($array);
        }
        $isEditable = $this->AcademicPeriods->getAvailableAcademicPeriods($currentPeriodId);
        if (!$isEditable) {
            $rowInvalidCodeCols['academic_period_id'] = __('No data changes can be made for the current academic period');
            $tempRow['academic_period_id'] = false;
            return false;
        }

        if (empty($tempRow['start_date'])) {
            $rowInvalidCodeCols['start_date'] = __('This field cannot be left empty');
            return false;
        } else {
            // from string to dateObject
            $formattedDate = DateTime::createFromFormat('d/m/Y', $tempRow['start_date']);
            $tempRow['start_date'] = $formattedDate;

            $periods = $this->getAcademicPeriodByStartDate($tempRow['start_date']);
            if (!$periods) {
                $rowInvalidCodeCols['academic_period_id'] = __('No matching academic period based on the start date');
                $tempRow['academic_period_id'] = false;
                return false;
            }
            $periods = new Collection($periods);
            $periodIds = $periods->extract('id');
            $periodIds = $periodIds->toArray();
            if (!in_array($currentPeriodId, $periodIds)) {
                $rowInvalidCodeCols['academic_period_id'] = __('Date is not within current academic period');
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
            $rowInvalidCodeCols['student_id'] = __('No such student in the institution');
            $tempRow['student_id'] = false;
            return false;
        }

        return true;
    }

    public function onImportSetModelPassedRecord(Event $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow) {
        $flipped = array_flip($columns);
        $original = $originalRow->getArrayCopy();
        $key = $flipped['student_id'];
        $tempPassedRecord['data'][$key] = $original[$key];
    }

}
