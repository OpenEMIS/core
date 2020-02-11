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
use Cake\I18n\Time;
use Workflow\Model\Behavior\WorkflowBehavior;

class ImportStaffAttendancesTable extends AppTable {
    private $institutionId = false;

    public function initialize(array $config) {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Staff', 'model'=>'InstitutionStaffAttendances',
            'backUrl' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'InstitutionStaffAttendances']]);

        $this->InstitutionStaffAttendances = TableRegistry::get('Staff.InstitutionStaffAttendances');
        $this->Institutions = TableRegistry::get('Institution.Institutions');
        $this->Staff = TableRegistry::get('Institution.Staff');
        $this->Users = TableRegistry::get('User.Users');
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $this->Workflows = TableRegistry::get('Workflow.Workflows');
        $this->WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
        $this->WorkflowsFilters = TableRegistry::get('Workflow.WorkflowsFilters');
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
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.import.onImportPopulateUsersData' => 'onImportPopulateUsersData',
            'Model.import.onImportPopulateAcademicPeriodsData' => 'onImportPopulateAcademicPeriodsData',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona) {
        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $tempRow['staff_id'] = $tempRow['openemis_no'];
        $tempRow['date'] = DateTime::createFromFormat('d/m/Y', $tempRow['date']);
        $institutionId = $this->ControllerAction->paramsDecode($this->request->params['institutionId']);
        $id = $institutionId['id'];
        $tempRow['institution_id'] = $id;
        unset($tempRow['openemis_no']);
        return true;
    }

    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')->select(['id', 'first_name', 'middle_name', 'third_name', 'last_name', $lookupColumn]);

        $allStaff = $this->Staff
                        ->find('all')
                        ->where([$this->Staff->aliasField('institution_id') => $this->institutionId])
                        ;
        // when extracting the staff_id from $allStaff collection, there will be no duplicates
        $allStaff = new Collection($allStaff->toArray());
        $modelData->where([
            'id IN' => $allStaff->extract('staff_id')->toArray()
        ]);

        $institution = $this->Institutions->get($this->institutionId);
        $institutionHeader = $this->getExcelLabel('Imports', 'institution_id') . ": " . $institution->name;
        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [
            $institutionHeader,
            $nameHeader,
            $columnHeader
        ];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $institution->name,
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    

    public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->getAvailableAcademicPeriods(false);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $startDateLabel = $this->getExcelLabel($lookedUpTable, 'start_date');
        $endDateLabel = $this->getExcelLabel($lookedUpTable, 'end_date');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $startDateLabel, $endDateLabel, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData as $row) {
                if ($row->academic_period_level_id == 1) { //validate that only period level "year" will be shown
                    $date = $row->start_date;
                    $data[$columnOrder]['data'][] = [
                        $row->name,
                        $row->start_date->format('d/m/Y'),
                        $row->end_date->format('d/m/Y'),
                        $row->{$lookupColumn}
                    ];
                }
            }
        }
    } 


    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData) {
        $process = function($model, $entity) use ($requestData) {
            $errors = $entity->errors();
            if (empty($errors)) {
                $this->_generate($requestData);
                return true;
            } else {
                return false;
            }
        };
        
    }

}