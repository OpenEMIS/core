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

class WorkflowReportBehavior extends Behavior {
	public function initialize(array $config) {
                $this->_table->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
                $this->_table->belongsTo('Assignees', ['className' => 'User.Users']);
	}

        public function implementedEvents() {
        $events = parent::implementedEvents();
        // $events['WorkflowReport.onExcelBeforeQuery'] = 'workflowBeforeQuery';
        $events['Model.excel.onExcelUpdateFields'] = 'onExcelUpdateFields';
        return $events;
    }

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
	{
                pr("WorkflowReportBehavior - onExcelUpdateFields");

                $requestData = json_decode($settings['process']['params']);

                //Re-order the column (Status followed by Assignee) - Start
                // pr('Before');
                // pr($fields);
                $statusTempArr = null;
                $assigneeTempArr = null;
                $hasEnteredStatusTempArr = false;
                $hasEnteredAssigneeTempArr = false;
                $currentIndex = 0;

                foreach($fields as $value) {

                        if($value['field'] == 'status_id') {
                                $statusTempArr = $value;
                                $hasEnteredStatusTempArr = true;
                                $fields[$currentIndex] = $fields[0];
                        $fields[0] = $statusTempArr;
                        }

                        if($value['field'] == 'assignee_id') {
                                $assigneeTempArr = $value;
                                $hasEnteredAssigneeTempArr = true;
                                $fields[$currentIndex] = $fields[1];
                        $fields[1] = $assigneeTempArr;
                        }

                        if($hasEnteredStatusTempArr && $hasEnteredAssigneeTempArr) {
                                break;
                        }
                        $currentIndex++;
                }
      
        // pr('After');
        // pr($fields);
        //Re-order the column (Status followed by Assignee) - End


	    // $this->_table->dispatchEvent('WorkflowReport.onExcelUpdateFields', [$settings, $fields]);
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
	{
        pr("WorkflowReportBehavior - onExcelBeforeQuery");

        $requestData = json_decode($settings['process']['params']);


        $category = $requestData->category;
        $startDate = $requestData->report_start_date;
        $endDate = $requestData->report_end_date;

        $reportStartDate = (new DateTime($startDate))->format('Y-m-d');
        $reportEndDate = (new DateTime($endDate))->format('Y-m-d');

        $query
        	->where([
	            $this->_table->aliasField('created') . ' <= ' => $reportEndDate,
	            $this->_table->aliasField('created') . ' >= ' => $reportStartDate
	        ]);

	    if ($category != -1) {
	    	$query
	    		->contain('Statuses')
	    		->where(['Statuses.category' => $category]);
	    }

	    // $this->_table->dispatchEvent('WorkflowReport.onExcelBeforeQuery', [$settings, $query]);
	}

        public function workflowBeforeQuery(Event $event, ArrayObject $settings, $query) {
        pr("WorkflowReportBehavior - workflowBeforeQuery");
        $requestData = json_decode($settings['process']['params']);
        // pr($requestData);

    }
}
