<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use DateTime;
use PHPExcel_Worksheet;
use Cake\I18n\Time;
use Workflow\Model\Behavior\WorkflowBehavior;
use Cake\Log\Log; // POCOR-8944

class ImportStaffAttendancesTable extends AppTable
{
    private $institutionId = false;

    // POCOR-8944 start
    public function initialize(array $config): void
    {
        $this->setTable('import_mapping'); // POCOR-8944
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Staff', 'model'=>'InstitutionStaffAttendances',
            'backUrl' => ['plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'InstitutionStaffAttendances']]);

        $this->InstitutionStaffAttendances = TableRegistry::getTableLocator()->get('Staff.InstitutionStaffAttendances');
        $this->Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $this->Staff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $this->Users = TableRegistry::getTableLocator()->get('User.Users');
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');

        $this->Workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
        $this->WorkflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
        $this->WorkflowsFilters = TableRegistry::getTableLocator()->get('Workflow.WorkflowsFilters');

    }

    public function beforeAction($event) {
//        $session = $this->request->session();
//        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $this->ControllerAction->getQueryString('institution_id');
//        }
        $this->systemDateFormat = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('date_format');
    }


    public function implementedEvents(): array
    {
        // POCOR-8944 end
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

    // POCOR-8944 start
    public function onGetBreadcrumb(EventInterface $event, Request $request, Component $Navigation, $persona): void
    {
        $crumbTitle = $this->getHeader($this->getAlias());
        // POCOR-8944 end
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $tempRow['staff_id'] = $tempRow['openemis_no'];
        $tempRow['date'] = DateTime::createFromFormat('d/m/Y', $tempRow['date']);
        // POCOR-8944 start
        $institutionId = $this->ControllerAction->getQueryString('institution_id');
        $tempRow['institution_id'] = $institutionId;
        // POCOR-8944 end
        unset($tempRow['openemis_no']);
        return true;
    }

    public function onImportPopulateUsersData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel); // POCOR-8944
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



    public function onImportPopulateAcademicPeriodsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel); // POCOR-8944
        $modelData = $lookedUpTable->getAvailableAcademicPeriods(false);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $startDateLabel = $this->getExcelLabel($lookedUpTable, 'start_date');
        $endDateLabel = $this->getExcelLabel($lookedUpTable, 'end_date');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $startDateLabel, $endDateLabel, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData as $row) {
                if ($row->academic_period_level_id == 1) { //validate that only period level "year" will be shown
                    // POCOR-8944
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


    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $requestData): void // POCOR-8944
    {
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
