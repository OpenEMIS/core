<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
// Starts POCOR-6593
use Cake\ORM\TableRegistry;
// Ends POCOR-6593
class TrainingSessionsTable extends AppTable  {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_id']);
        $this->belongsTo('TrainingProviders', ['className' => 'Training.TrainingProviders', 'foreignKey' => 'training_provider_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
         // Starts POCOR-6593
        $this->belongsTo('TrainingSessionsTrainees', ['className' => 'Training.TrainingSessionsTrainees', 'foreignKey' => 'training_session_id']);
         $this->belongsTo('Areas', ['className' => 'Area.Areas', 'foreignKey' => 'area_id']);
          // Ends POCOR-6593
        $this->addBehavior('Excel', [
            'excludes' => ['code', 'name', 'assignee_id', 'status_id', 'training_course_id']
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {  
         // Starts POCOR-6593
        $requestData = json_decode($settings['process']['params']);
        $selectedStatus = $requestData->status;
        $areas = TableRegistry::get('areas');
        $TrainingCourses = TableRegistry::get('training_courses');
         $area_education_id=$requestData->area_education_id->_ids;

        
        $startDate=date("Y-m-d", strtotime($requestData->session_start_date));
        $endDate=date("Y-m-d", strtotime($requestData->session_end_date));
        $join=[];
         $join[' '] = [
            'type' => 'left',
            'table' => '( SELECT count(*) AS number,training_sessions_trainees.training_session_id FROM training_sessions_trainees GROUP BY training_session_id) AS count_trainees',
            'conditions' => ['TrainingSessions.id = count_trainees.training_session_id'],

        ];

        $res=$query
            
            ->join($join)
            ->leftJoin([$TrainingCourses->alias() => $TrainingCourses->table()], [
                $TrainingCourses->aliasField('id = ') . 'TrainingSessions.training_course_id'
            ])
            ->select(['course_code' => 'training_courses.code','number' => 'number'])
            ->where(['area_id IN' => $area_education_id ])
            ->where(['start_date >=' => $startDate ])
            ->where(['end_date <=' => $endDate ])
            ->order(['course_code', $this->aliasField('code')]);
        if ($selectedStatus != '-1') {
            $query->matching('WorkflowSteps.WorkflowStatuses', function ($q) use ($selectedStatus) {
                return $q->where(['WorkflowStatuses.id' => $selectedStatus]);
            });
        }
        //print_r($res->sql()); die;
         // Ends POCOR-6593
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        // starts POCOR-6593
        $newFields[] = [
            'key' => 'TrainingSessions.created_user_id',
            'field' => 'created_user_id',
            'type' => 'integer',
            'label' => 'Created User',
        ];
        
        $newFields[] = [
            'key' => 'TrainingSessions.status_id',
            'field' => 'status_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Courses.course_code',
            'field' => 'course_code',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Courses.training_course_id',
            'field' => 'training_course_id',
            'type' => 'integer',
            'label' => __('Course Name'),
        ];

        $newFields[] = [
            'key' => 'TrainingSessions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Session Code'),
        ];

        $newFields[] = [
            'key' => 'TrainingSessions.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Session Name'),
        ];
        $newFields = array_merge($newFields, $fields->getArrayCopy());
        $newFields[] = [
            'key' => 'training_sessions_trainees.number',
            'field' => 'number',
            'type' => 'string',
            'label' => __('Number of Participant'),
        ];
        $fields->exchangeArray($newFields);
         // Ends POCOR-6593
    }
}
