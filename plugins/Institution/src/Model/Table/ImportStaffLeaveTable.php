<?php
namespace Institution\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use App\Model\Table\AppTable;
use Cake\Controller\Component;
use Cake\I18n\Date;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;

class ImportStaffLeaveTable extends AppTable
{
    private $institutionId;
    private $staffId;

    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);
        
        $this->addBehavior('Import.Import', [
            'plugin' => 'Institution',
            'model' => 'StaffLeave'
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['Model.import.onImportPopulateStaffLeaveTypesData'] = 'onImportPopulateStaffLeaveTypesData';
        $events['Model.import.onImportPopulateWorkflowStepsData'] = 'onImportPopulateWorkflowStepsData';
        // $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function beforeAction($event)
    {
        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }

        if ($session->check('Staff.Staff.id')) {
            $this->staffId = $session->read('Staff.Staff.id');
        }
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        // $plugin = $toolbarButtons['back']['url']['plugin'];
        // $controller = $toolbarButtons['back']['url']['controller'];
        // if ($plugin == 'Directory' || $plugin == 'Profile') {
        //     $toolbarButtons['back']['url']['action'] = 'StaffLeave';
        // } elseif ($plugin == 'Staff') {
        //     $toolbarButtons['back']['url']['action'] = 'StaffLeave';
        // }
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportPopulateStaffLeaveTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $order = [$lookedUpTable->aliasField('name')];
        $selectFields = [
            $lookedUpTable->aliasField('name'),
            $lookedUpTable->aliasField($lookupColumn)
        ];

        $result = $lookedUpTable
            ->find('all')
            ->select($selectFields)
            ->order($order)
            ->all();

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];

        if (!$result->isEmpty()) {
            $modelData = $result->toArray();
            foreach ($modelData as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateWorkflowStepsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        // $order = [$lookedUpTable->aliasField('name')];
        $Workflows = TableRegistry::get('Workflow.Workflows');
        $WorkflowsFilters = TableRegistry::get('Workflow.WorkflowsFilters');
        $StaffLeaveTypes = TableRegistry::get('Staff.StaffLeaveTypes');

        $workflowResult = $Workflows
            ->find()
            ->select([
                'workflow_id' => $Workflows->aliasField('id'),
                'workflow_name' => $Workflows->aliasField('name'),
                'workflow_step_id' => $lookedUpTable->aliasField('id'),
                'workflow_step_name' => $lookedUpTable->aliasField('name'),
                'leave_type_id' => $StaffLeaveTypes->aliasField('id'),
                'leave_type_name' => $StaffLeaveTypes->aliasField('name')
            ])
            ->matching('WorkflowModels', function ($q) {
                return $q->where(['WorkflowModels.model' => 'Institution.StaffLeave']);
            })
            ->matching($lookupModel)
            ->leftJoin(
                [$WorkflowsFilters->alias() => $WorkflowsFilters->table()],
                [$Workflows->aliasField('id = ') . $WorkflowsFilters->aliasField('workflow_id')]
            )
            ->leftJoin(
                [$StaffLeaveTypes->alias() => $StaffLeaveTypes->table()],
                [$StaffLeaveTypes->aliasField('id = ') . $WorkflowsFilters->aliasField('filter_id')]
            )
            ->all();

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [__('Staff Leave Type Id'), __('Workflow'), $translatedReadableCol, $translatedCol];

        if (!$workflowResult->isEmpty()) {
            $modelData = $workflowResult->toArray();

            foreach ($modelData as $row) {
                $leaveTypeId = is_null($row->leave_type_id) ? 0 : $row->leave_type_id;

                $data[$columnOrder]['data'][] = [
                    $leaveTypeId,
                    $row->workflow_name,
                    $row->workflow_step_name,
                    $row->workflow_step_id
                ];
            }
        }
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $tempRow['assignee_id'] = -1;
        $tempRow['institution_id'] = $this->institutionId;
        $tempRow['staff_id'] = $this->staffId;
        // pr('event');
        // pr($event);
        // pr('references');
        // pr($references);
        // pr('tempRow'); // ?
        // pr($tempRow); // ?
        // pr('originalRow');
        // pr($originalRow);
        // pr('rowInvalidCodeCols');
        // pr($rowInvalidCodeCols);
        // die;
        return true;
    }
}
