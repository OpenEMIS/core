<?php
namespace Institution\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Workflow\Model\Behavior\WorkflowBehavior;

class ImportInstitutionPositionsTable extends AppTable
{
    use OptionsTrait;

    private $institutionId;

    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);
        
        $this->addBehavior('Import.Import', [
            'plugin' => 'Institution',
            'model' => 'InstitutionPositions'
        ]);

        $this->Workflows = TableRegistry::get('Workflow.Workflows');
        $this->WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
        $this->WorkflowsFilters = TableRegistry::get('Workflow.WorkflowsFilters');
        $this->InstitutionPositions = TableRegistry::get('Institution.InstitutionPositions');
        $this->StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
        $this->StaffPositionTitlesGrades = TableRegistry::get('Institution.StaffPositionTitlesGrades');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['Model.import.onImportGetIsHomeroomId'] = 'onImportGetIsHomeroomId';
        $events['Model.import.onImportPopulateStaffPositionTitlesData'] = 'onImportPopulateStaffPositionTitlesData';
        $events['Model.import.onImportPopulateIsHomeroomData'] = 'onImportPopulateIsHomeroomData';
        $events['Model.import.onImportPopulateWorkflowStepsData'] = 'onImportPopulateWorkflowStepsData';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $session = $request->session();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }

        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportGetIsHomeroomId(Event $event, $cellValue)
    {
        $options = $this->getSelectOptions('general.yesno');
        foreach ($options as $key => $value) {
            if ($cellValue == $key) {
                return $cellValue;
            }
        }
        return null;
    }

    public function onImportPopulateStaffPositionTitlesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);

        $staffTitleResults = $lookedUpTable
            ->find()
            ->innerJoinWith('SecurityRoles')
            ->select([
                'type' => $lookedUpTable->aliasField('type'),
                'id' => $lookedUpTable->aliasField('id'),
                'name' => $lookedUpTable->aliasField('name')
            ])
            ->order([
                $lookedUpTable->aliasField('type') => 'DESC',
                $lookedUpTable->aliasField('order'),
            ])
            ->autoFields(false)
            ->all();

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [__('Staff Type'), $translatedReadableCol, $translatedCol];

        if (!$staffTitleResults->isEmpty()) {
            $modelData = $staffTitleResults->toArray();

            foreach ($modelData as $row) {
                $teachingType = $row->type == 0 ? __('Non-Teaching') : __('Teaching');

                $data[$columnOrder]['data'][] = [
                    $teachingType,
                    $row->name,
                    $row->id
                ];
            }
        }
    }

    public function onImportPopulateIsHomeroomData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $translatedReadableCol = $this->getExcelLabel('Is Homeroom', 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];

        $options = $this->getSelectOptions('general.yesno');
        foreach ($options as $key => $value) {
            $data[$columnOrder]['data'][] = [
                $value,
                $key
            ];
        }
    }

    public function onImportPopulateWorkflowStepsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);

        $workflowResult = $this->Workflows
            ->find()
            ->select([
                'workflow_id' => $this->Workflows->aliasField('id'),
                'workflow_step_id' => $lookedUpTable->aliasField('id'),
                'workflow_step_name' => $lookedUpTable->aliasField('name')
            ])
            ->matching('WorkflowModels', function ($q) {
                return $q->where(['WorkflowModels.model' => 'Institution.InstitutionPositions']);
            })
            ->matching($lookedUpTable->alias())
            ->order([
                $this->Workflows->aliasField('name'),
                $lookupModel.'.category'
            ])
            ->all();

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];

        if (!$workflowResult->isEmpty()) {
            $modelData = $workflowResult->toArray();

            foreach ($modelData as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->workflow_step_name,
                    $row->workflow_step_id
                ];
            }
        }
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        if (!$this->institutionId) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            return false;
        }

        $tempRow['assignee_id'] = WorkflowBehavior::AUTO_ASSIGN;
        $tempRow['institution_id'] = $this->institutionId;

        if (!isset($tempRow['position_no'])) {
            $tempRow['position_no'] = $this->InstitutionPositions->getUniquePositionNo($this->institutionId);
        } elseif (strlen($tempRow['position_no']) > 30) {
            $rowInvalidCodeCols['position_no'] = __('Position number cannot be longer than 30');
            $tempRow['position_no'] = false;
            return false;
        }

        $positionTitleEntity = $this->StaffPositionTitles
            ->find()
            ->select([$this->StaffPositionTitles->aliasField('type')])
            ->where([
                $this->StaffPositionTitles->aliasField('id') => $tempRow['staff_position_title_id']
            ])
            ->first();

        // Selected title is teaching type, need to check if the homeroom is set (Teaching = 1, Non-Teaching = 0)
        if ($positionTitleEntity->type == 1) {
            if (!isset($tempRow['is_homeroom'])) {
                $rowInvalidCodeCols['is_homeroom'] = __('This field cannot be left empty');
                $tempRow['is_homeroom'] = false;
                return false;
            }
        }


        $result = $this->StaffPositionTitlesGrades
            ->find()
            ->where([
                'AND' => [
                    $this->StaffPositionTitlesGrades->aliasField('staff_position_title_id') => $tempRow['staff_position_title_id'],
                    'OR' => [
                        [$this->StaffPositionTitlesGrades->aliasField('staff_position_grade_id') => $tempRow['staff_position_grade_id']],
                        [$this->StaffPositionTitlesGrades->aliasField('staff_position_grade_id') => -1]
                    ]
                ]
            ])
            ->all();

        if ($result->isEmpty()) {
            $rowInvalidCodeCols['staff_position_grade_id'] = __('Selected value does not match with Staff Position Grade Type');
            return false;
        }

        return true;
    }
}
