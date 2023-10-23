<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Institution\Model\Table\InstitutionsTable as Institutions;
use Cake\Database\Connection;

class StaffBehavioursTable extends AppTable  {
    //institution_filters
    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;
    public function initialize(array $config) {
    
        parent::initialize($config);
        $this->addBehavior('Excel');
		$this->addBehavior('Report.ReportList');
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('Staff', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffBehaviourCategories', ['className' => 'Staff.StaffBehaviourCategories']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']); //POCOR-6670
        $this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);
        $this->addBehavior('Report.AreaList');//POCOR-7794
      
    }
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }
    // public function beforeAction(Event $event) {
    //     $this->fields = [];
    //     $this->ControllerAction->field('feature');
    //     $this->ControllerAction->field('format');
        
    // }
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $filter = $requestData->institution_filter;
        $areaId = $requestData->area_education_id;
        $superAdmin = $requestData->super_admin;
        $userId = $requestData->user_id;
        $where=[];
        //area filter
        $areaLevelId = $requestData->area_level_id; //POCOR-7794
        //POCOR-7794 start
        $areaList = [];
        if (
            $areaLevelId > 1 && $areaId > 1
        ) {
            $areaList = $this->getAreaList($areaLevelId, $areaId);
        } elseif ($areaLevelId > 1) {

            $areaList = $this->getAreaList($areaLevelId, 0);
        } elseif ($areaId > 1) {
            $areaList = $this->getAreaList(0, $areaId);
        }
        if (!empty($areaList)) {
            $where['Institutions.area_id IN'] = $areaList;
        }
        //POCOR-7794 end
        $Statuses1=TableRegistry::get('workflow_steps');
        $query->select([
             "academic_period_name"=>'AcademicPeriods.name',
             "institution_code"=>'Institutions.code',
             "institution_name"=>"Institutions.name",
             "date_of_behaviour"=>$this->aliasField('date_of_behaviour'),
             "time"=>$this->aliasField('time_of_behaviour'),
             "openemis_no"=>"Staff.openemis_no",
             "status_name"=>$Statuses1->aliasField('name'),
             "name"=> $this->find()->func()->concat([
                 'Staff.first_name' => 'literal',
                " ",
                'Staff.last_name' => 'literal'
             ]),
           
             "description"=>$this->aliasField('description'),
             "behaviour_category"=>"StaffBehaviourCategories.name",
             "behaviour_classification"=>"BehaviourClassifications.name"
            ]);
        $query->contain(['Institutions','Institutions.Areas','AcademicPeriods','Staff','StaffBehaviourCategories','BehaviourClassifications'])
        ->InnerJoin([$Statuses1->alias()=>$Statuses1->table()],[
                $Statuses1->aliasField('id')."=(`StaffBehaviours`.`status_id`)"
        ])      
        -> where([$where]);
     
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $row->time_of_behaviour= $row->time_of_behaviour->i18nFormat('HH:mm:ss');
                $row->behaviour_classification=$row->behaviour_classification->name;
             
                return $row;
            });
        });
       
        //institution_filter
        switch ($filter) {
            case self::NO_STUDENT:
                $StudentsTable = TableRegistry::get('Institution.Students');
                $academicPeriodId = $requestData->academic_period_id;

                $query
                    ->leftJoin(
                        [$StudentsTable->alias() => $StudentsTable->table()],
                        [
                            $StudentsTable->aliasField('institution_id') . ' = '. $this->aliasField('institution_id'),
                            $StudentsTable->aliasField('academic_period_id') => $academicPeriodId
                        ]
                    )
                    ->select(['student_count' => $query->func()->count('Students.id')])
                    ->group([$this->aliasField('institution_id')])
                    ->having(['student_count' => 0]);
                break;

            case self::NO_STAFF:
                $StaffTable = TableRegistry::get('institution_staff');
                $query->leftJoin(
                    [$StaffTable->alias() => $StaffTable->table()],
                    [
                        $StaffTable->aliasField('institution_id') . ' = '. $this->aliasField('institution_id'),
                    ]
                )
                    ->select(['staff_count' => $query->func()->count('Staff.id')])
                    ->group([$this->aliasField('institution_id')])
                    ->having(['staff_count' => 0]);
                break;

            case self::NO_FILTER:
                break;
        }
        // if (!$superAdmin) {
        //     $query->find('byAccess', ['user_id' => $userId, 'institution_field_alias' => $this->aliasField('id')]);
        // }
    
}
 public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
      
        $extraField[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period_name',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $extraField[] = [
            'key' => 'StaffBehaviour.Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
        $extraField[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $extraField[] = [
            'key' => 'StaffBehaviour.date_of_behaviour',
            'field' => 'date_of_behaviour',
            'type' => 'date',
            'label' => __('Date Of Behaviour')
        ];
        $extraField[] = [
            'key' => 'StaffBehaviour.time_of_behaviour',
            'field' => 'time_of_behaviour',
            'type' => 'string',
            'label' => __('Time Of Behaviour')
        ];
        $extraField[] = [
            'key' => 'Statuses.name',
            'field' => 'status_name',
            'type' => 'string',
            'label' => __('Status')
        ];
        $extraField[] = [
            'key' => 'Staff.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $extraField[] = [
            'key' => 'Staff.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Staff Name')
        ];
        $extraField[] = [
            'key' => 'StaffBehaviour.description',
            'field' => 'description',
            'type' => 'string',
            'label' => __('Description')
        ];
        $extraField[] = [
            'key' => 'BehaviourCategories',
            'field' => 'behaviour_category',
            'type' => 'string',
            'label' => __('Behaviour category')
        ];
        $extraField[] = [
            'key' => 'behaviour_classification',
            'field' => 'behaviour_classification',
            'type' => 'string',
            'label' => __('Behaviour Classification')
        ];
       
      
    
        // POCOR-6155
        $fields->exchangeArray($extraField);
    }
}