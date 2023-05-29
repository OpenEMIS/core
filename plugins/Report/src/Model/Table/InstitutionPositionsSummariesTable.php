<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;
use Cake\Datasource\ResultSetInterface;

class InstitutionPositionsSummariesTable extends AppTable
{
    private $features = [];

    public function initialize(array $config)
    {
        $this->table('institution_staff');
        parent::initialize($config);
        
        $this->belongsTo('InstitutionPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
        $this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades','foreignKey' => 'staff_position_grade_id']); //POCOR-7377
        $this->belongsTo('Staffs', ['className' => 'User.Users']);
        $this->belongsTo('Areas', ['className' => 'Institution.Areas']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('workflowSteps', ['className' => 'InstitutionPositions.workflowSteps']);

        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('AcademicPeriod.Period');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicperiodid = $requestData->academic_period_id;
        $area_level_id = $requestData->area_level_id;
        $statusFilter = $requestData->position_status;  //POCOR-7445

        $AcademicPeriodsTable = TableRegistry::get('academic_periods');

        $institution_id = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $selectedArea = $requestData->area_education_id;
        $where = [];
        if ($institution_id != 0) {
            $where[$this->aliasField('institution_id')] = $institution_id;
        }

        if ($statusFilter != 0) {
            $where[$this->aliasField('InstitutionPositions.status_id')] = $statusFilter; 
        }
        if ($academicperiodid != -1) {
            $where[$AcademicPeriodsTable->aliasField('id')] = $academicperiodid; 
        }

        //POCOR-7407 start
       if ($areaId != -1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
                $where['Institutions.area_id IN'] = $allselectedAreas;
        }
        $workflowStepsTable = TableRegistry::get('workflow_steps');
        $position = TableRegistry::get('Institution.InstitutionPositions');
        //POCOR-7407 end
        $query
        ->SELECT ([
           'start_year' =>'InstitutionPositionsSummaries.start_year',
           'end_year' =>'InstitutionPositionsSummaries.end_year',
           'id' =>'InstitutionPositionsSummaries.id',
           'area_code' =>'Areas.code',
           'area_name' =>'Areas.name',
           'category' => $workflowStepsTable->aliasField('name'),
           'institutions_code' =>'Institutions.code',
           'institutions_name' =>'Institutions.name',
           'institutions_id' =>'Institutions.id',
           'staff_position_titles' =>'StaffPositionTitles.name',
           'staff_position_grades' =>'StaffPositionGrades.name',
           'staff_name' =>'Staffs.first_name',
           'total_male' => "( SUM(CASE WHEN Staffs.gender_id = 1 THEN 1 ELSE 0 END) )",
           'total_female' => "( SUM(CASE WHEN Staffs.gender_id = 2 THEN 1 ELSE 0 END) )",
           'total' => "( SUM(CASE WHEN Staffs.gender_id in (1,2 ) THEN 1 ELSE 0 END) )",
        ])
        ->contain(['InstitutionPositions','InstitutionPositions.StaffPositionTitles','StaffPositionGrades','Institutions.Areas','Staffs' ]) //POCOR-7377

        ->innerJoin(
            [$AcademicPeriodsTable->alias() => $AcademicPeriodsTable->table()],
            [
                
                ['OR'=>[

                    'OR'=>[
                            [
                                $this->aliasField('end_date IS NOT NULL') ,
                                $this->aliasField('start_date <=') .$AcademicPeriodsTable->aliasField('start_date'),
                                $this->aliasField('end_date >=') .$AcademicPeriodsTable->aliasField('start_date'),
                            ],
                            [
                                $this->aliasField('end_date IS NOT NULL') ,
                                $this->aliasField('start_date <=') .$AcademicPeriodsTable->aliasField('end_date'),
                                $this->aliasField('end_date >=') .$AcademicPeriodsTable->aliasField('end_date'),
                            ],
                            [
                                $this->aliasField('end_date IS NOT NULL') ,
                                $this->aliasField('start_date >=') .$AcademicPeriodsTable->aliasField('start_date'),
                                $this->aliasField('end_date <=') .$AcademicPeriodsTable->aliasField('end_date'),
                            ]
                        ],
                    
                    
                        ['AND'=>

                            [
                                $this->aliasField('end_date IS NULL') ,
                                $this->aliasField('start_date <=') .$AcademicPeriodsTable->aliasField('end_date'),
                            // $this->aliasField('end_date >=') .$AcademicPeriodsTable->aliasField('start_date'),
                            ]
                        
                        ]
                ]
                ]
                

            ]

        )->LeftJoin([$position->alias() => $position->table()],
                    [$position->aliasField('id') . ' = '. $this->aliasField('institution_position_id')])
        ->LeftJoin([$workflowStepsTable->alias() => $workflowStepsTable->table()],
                    [$workflowStepsTable->aliasField('id') . ' = '. $position->aliasField('status_id')])

        ->where($where)
        ->group(['Institutions.id','StaffPositionTitles.id','StaffPositionGrades.id'])
        ->order(['Areas.name','Institutions.name','StaffPositionTitles.name','StaffPositionGrades.name']);
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key' => 'Areas.code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $newFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Name')
        ];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institutions_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institutions_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'StaffPositionTitles.name',
            'field' => 'staff_position_titles',
            'type' => 'string',
            'label' => __('Title')
        ];

        $newFields[] = [
            'key' => 'StaffPositionTitles.name',
            'field' => 'staff_position_titles',
            'type' => 'string',
            'label' => __('Category')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total_male',
            'type' => 'string',
            'label' => __('Total Male')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total_female',
            'type' => 'string',
            'label' => __('Total Female')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total',
            'type' => 'string',
            'label' => __('Total')
        ];

        $fields->exchangeArray($newFields);
    }

    //POCOR-7407
    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ]) 
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }

}