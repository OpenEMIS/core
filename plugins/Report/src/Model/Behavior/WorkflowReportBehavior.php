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

        $localFields = (array) $fields;

        foreach ($localFields as $currentIndex => $value) {
            if($value['field'] == 'status_id') {
                $statusTempArr = $value;
                $hasEnteredStatusTempArr = true;
                unset($localFields[$currentIndex]);
            }

            if($value['field'] == 'assignee_id') {
                $assigneeTempArr = $value;
                $hasEnteredAssigneeTempArr = true;
                unset($localFields[$currentIndex]);
            }

            if($hasEnteredStatusTempArr && $hasEnteredAssigneeTempArr) {
                break;
            }
        }

        $localFields = array_values($localFields);
        array_unshift($localFields , $assigneeTempArr);
        array_unshift($localFields , $statusTempArr);
        $fields->exchangeArray($localFields);
        // Re-order the column (Status followed by Assignee) - End
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {   
        $requestData = json_decode($settings['process']['params']);
        if($requestData->model == 'Report.WorkflowStudentTransferIn'){
            $category = $requestData->category;
            $institution_id = $requestData->institution_id;

            if ($category != -1) {
                $query
                    ->contain('Statuses')
                    ->where(['Statuses.category' => $category, 'WorkflowStudentTransferIn.institution_id'=>$institution_id])
                    ->toArray();
                if(!empty($query)){
                    $query
                    ->contain('Statuses')
                    ->where(['Statuses.category' => $category, 'WorkflowStudentTransferIn.institution_id'=>$institution_id]);
                }
                else{
                    return true;
                }
            }else if($category == -1){
                $query
                    ->contain('Statuses')
                    ->where(['WorkflowStudentTransferIn.institution_id'=>$institution_id]);
            }
            else{
                return true;
            }
        }else{
            $category = $requestData->category;
            if ($category != -1) {
                $query
                    ->contain('Statuses')
                    ->where(['Statuses.category' => $category]);
            }
        }
    }
}
