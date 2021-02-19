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

class ImportStudentMealsTable extends AppTable {
    private $institutionId = false;

    public function initialize(array $config) {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'InstitutionMealStudents']);
        $this->Institutions = TableRegistry::get('Institution.Institutions');
        $this->Students = TableRegistry::get('Institution.Students');
        $this->Users = TableRegistry::get('User.Users');
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $this->InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $this->InstitutionMealStudents = TableRegistry::get('Institution.InstitutionMealStudents');
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
            'Model.import.onImportPopulateMealReceivedData' => 'onImportPopulateMealReceivedData',
            'Model.import.onImportPopulateMealBenefitData' => 'onImportPopulateMealBenefitData',
            'Model.import.onImportPopulateMealProgrammeData' => 'onImportPopulateMealProgrammeData',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        //die('3');
        $request = $this->request;
        if (empty($request->query('class'))) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        $request = $this->request;
        unset($request->query['class']);
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        //die('5');
        $this->dependency = [];
        $this->dependency["class"] = ["select_file"];

        $this->ControllerAction->field('class', ['type' => 'select']);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['class', 'select_file']);

        //Assumption - onChangeReload must be named in this format: change<field_name>. E.g changeClass
        $currentFieldName = strtolower(str_replace("change", "", $entity->submit));

        if (isset($this->request->data[$this->alias()])) {

            $unsetFlag = false;
            $aryRequestData = $this->request->data[$this->alias()];

            foreach ($aryRequestData as $requestData => $value) {
                if (isset($this->dependency[$requestData]) && $value) {
                    $aryDependencies = $this->dependency[$requestData];
                    foreach ($aryDependencies as $dependency) {
                        $this->request->query = $this->request->data[$this->alias()];
                        $this->ControllerAction->field($dependency, ['visible' => true]);
                    }
                }
            }
        }
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols) {
            $tempRow['entity'] = $this->InstitutionMealStudents->newEntity();
           
    }

    public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {}

    /**
     * Currently only populates students based on current academic period
     */


    public function onImportPopulateMealProgrammesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {

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

    public function onImportPopulateMealReceivedData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
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

    public function onImportPopulateMealBenefitData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {

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
            $rowInvalidCodeCols['student_id'] = __('No such student in the institution');
            $tempRow['student_id'] = false;
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

    public function onImportGetPeriodId(Event $event, $cellValue)
    {
        return $cellValue;
    }

    public function onImportSetModelPassedRecord(Event $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow) {
        $flipped = array_flip($columns);
        $original = $originalRow->getArrayCopy();
        $key = $flipped['student_id'];
        $tempPassedRecord['data'][$key] = $original[$key];
    }

    public function onUpdateFieldClass(Event $event, array $attr, $action, Request $request) {
        //die('15');
        if ($action == 'add') {
            $academicPeriodId = !is_null($request->query('period')) ? $request->query('period') : $this->AcademicPeriods->getCurrent();
            $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

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
