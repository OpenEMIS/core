<?php

namespace Report\Model\Behavior;

use ArrayObject;
use DateTime;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;

class WorkflowReportBehavior extends Behavior
{
    public function initialize(array $config)
    {
        $this->_table->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->_table->belongsTo('Assignees', ['className' => 'User.Users']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.excel.onExcelBeforeQuery'] = 'onExcelBeforeQuery';
        $events['Model.excel.onExcelUpdateFields'] = 'onExcelUpdateFields';
        return $events;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $requestData = json_decode($settings['process']['params']);

        // Re-order the column (Status followed by Assignee) - Start
        $statusTempArr = null;
        $assigneeTempArr = null;
        $hasEnteredStatusTempArr = false;
        $hasEnteredAssigneeTempArr = false;

        $localFields = (array)$fields;

        $openEmis_field = array(

            '0' => array(
                'key' => '',
                'field' => 'openemis_no',
                'type' => 'integer',
                'label' => 'OpenEMIS ID',
                'style' => Array
                (),

                'formatting' => 'GENERAL'
            )
        );
        array_splice($localFields, 3, 0, $openEmis_field);

        foreach ($localFields as $currentIndex => $value) {
            if ($value['field'] == 'status_id') {
                $statusTempArr = $value;
                $hasEnteredStatusTempArr = true;
                unset($localFields[$currentIndex]);
            }

            if ($value['field'] == 'assignee_id') {
                $assigneeTempArr = $value;
                $hasEnteredAssigneeTempArr = true;
                unset($localFields[$currentIndex]);
            }

            if ($hasEnteredStatusTempArr && $hasEnteredAssigneeTempArr) {
                break;
            }
        }

        $localFields = array_values($localFields);
        array_unshift($localFields, $assigneeTempArr);
        array_unshift($localFields, $statusTempArr);
        $fields->exchangeArray($localFields);
        // Re-order the column (Status followed by Assignee) - End
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        //POCOR-7433 start
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $requestData = json_decode($settings['process']['params']);
        $requestData->report_start_date = date('Y-m-d h', strtotime($requestData->report_start_date));
        $requestData->report_end_date = date('Y-m-d h', strtotime($requestData->report_end_date));
        $settings['process']['params'] = json_encode($requestData);
        //POCOR-7433 end

        /*POCOR-6296 starts*/
        $academicPeriodId = $requestData->academic_period_id;
        $areaId = 0;
        if (isset($requestData->area)) {
            $areaId = $requestData->area;
        }
        $institution_id = 0;
        if (isset($requestData->institution_id)) {
            $institution_id = $requestData->institution_id;
        }
        $conditions = [];
        if ($areaId != 0) {
            $conditions['Institutions.area_id'] = $areaId;
        }
        if ($institution_id > 0) {
            $conditions['Institutions.id'] = $institution_id;
        }
        $superAdmin = $requestData->super_admin;
        $userId = $requestData->user_id;
        $institutionIds = [];
        if (!$superAdmin) {
            //POCOR-7433
            if ($institution_id == 0) {
                $InstitutionsTable = TableRegistry::get('Institution.Institutions');
                $instituitionData = $InstitutionsTable->find('byAccess', ['userId' => $userId])->toArray();
                if (isset($instituitionData)) {
                    foreach ($instituitionData as $key => $value) {
                        $institutionIds[] = $value->id;
                    }
                }
                if ($institutionIds != []) {
                    $conditions['Institutions.id IN'] = $institutionIds;
                }
            }
        }
        //echo "<pre>";print_r($conditions);die();  
        /*POCOR-6296 ends*/
        if ($requestData->model == 'Report.WorkflowStudentTransferIn' || $requestData->model == 'Report.WorkflowStudentTransferOut') {
            $category = $requestData->category;
            $where = [];
            if ($areaId != 0) {
                $where['OR'][] = ['Institutions.area_id' => $areaId];
                $where['OR'][] = ['PreviousInstitutions.area_id' => $areaId];
            }
            if (!empty($institution_id) && $institution_id > 0) {
                $where['OR'][] = ['Institutions.id' => $institution_id];
                $where['OR'][] = ['PreviousInstitutions.id' => $institution_id];
            }
            if ($category != -1) {
                $query
                    ->contain('Statuses', 'Institutions', 'PreviousInstitutions')
                    ->where(['Statuses.category' => $category,
                        $where,
                        'AcademicPeriods.id' => $academicPeriodId
                    ]);
                $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                    return $results->map(function ($row) {
                        $row['openemis_no'] = $row->user->openemis_no;
                        return $row;
                    });
                });
            } else {
                $query
                    ->contain('Statuses', 'Institutions', 'PreviousInstitutions')
                    ->where([$where, 'AcademicPeriods.id' => $academicPeriodId]);

                $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                    return $results->map(function ($row) {

                        $row['openemis_no'] = $row->user->openemis_no;
                        return $row;
                    });
                });
            }
        }

        if ($requestData->model == 'Report.WorkflowInstitutionCase') {
            $category = $requestData->category;
            if ($category != -1) {
                $query
                    ->contain('Statuses', 'Institutions')
                    ->where(['Statuses.category' => $category]);
            } else { //POCOR-6296
                $query
                    ->contain('Statuses', 'Institutions');

            }
            //POCOR-7433(if condition)
            if ($conditions != []) {
                $query->where([$conditions]);
            }
        } /*POCOR-6296 starts*/ elseif ($requestData->model == 'Report.WorkflowStaffTransferIn' || $requestData->model == 'Report.WorkflowStaffTransferOut') {

            $category = $requestData->category;
            $newConditions = [];
            if ($areaId != 0) {
                $newConditions['OR'][] = ['NewInstitutions.area_id' => $areaId];
                $newConditions['OR'][] = ['PreviousInstitutions.area_id' => $areaId];
            }
            if (!empty($institution_id) && $institution_id > 0) {
                $newConditions['OR'][] = ['NewInstitutions.id' => $institution_id];
                $newConditions['OR'][] = ['PreviousInstitutions.id' => $institution_id];
            }
            if ($category != -1) {
                $query
                    ->contain('Statuses', 'NewInstitutions', 'PreviousInstitutions')
                    ->where(['Statuses.category' => $category]);
            } else { //POCOR-6296
                $query
                    ->contain('Statuses', 'NewInstitutions', 'PreviousInstitutions');

            }
            //POCOR-7433(if condition)
            if ($newConditions != []) {
                $query->where([$newConditions]);
            }
        } else {
            $query
                ->contain('Statuses');

            $category = $requestData->category;
            if ($category != -1) {
                $query->where(['Statuses.category' => $category]);
            }
            if ($requestData->model != 'Report.WorkflowStaffLicense') {
                $query->leftJoin([$InstitutionsTable->alias() => $InstitutionsTable->table()]);//POCOR-7433
            }
            //POCOR-7433(if condition)
            if ($conditions != []) {
                $query->where([$conditions]);
            }
        }/*POCOR-6296 ends*/

    }
}
