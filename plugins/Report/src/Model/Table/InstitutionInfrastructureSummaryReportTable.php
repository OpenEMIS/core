<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\I18n\Date;
use DateTime;


/**
 * @author Rishabh Sharma rishabh.sharma1@mail.valuecoders.com
 * Develop Institution Infrastructure Summary Report 
 * 
 */

class InstitutionInfrastructureSummaryReportTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institutions');
        parent::initialize($config);
        $this->addBehavior('Excel', [
            'excludes' => [],
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $areaLevelId = $requestData->area_level_id;
        $areaId = $requestData->area_education_id;
        $institution_id = $requestData->institution_id;
        $institution_status_id = $requestData->institution_status_id;
        $academic_period_id = $requestData->academic_period_id;
        $AreaLvlT = TableRegistry::get('area_levels'); 
	    $AreaLvlData = $AreaLvlT->find('all')->where(['id' => $areaLevelId])->first();
        $AreaT = TableRegistry::get('areas');                
        //Level-1
        if($areaId != -1){
            $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $areaId])->toArray();
        }else{
            $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['area_level_id' => $areaLevelId])->toArray();
        }
        
        $childArea =[];
        $childAreaMain = [];
        $childArea3 = [];
        $childArea4 = [];
        foreach($AreaData as $kkk =>$AreaData11 ){
            $childArea[$kkk] = $AreaData11->id;
        }
        //level-2
        foreach($childArea as $kyy =>$AreaDatal2 ){
            $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
            foreach($AreaDatas as $ky =>$AreaDatal22 ){
                $childAreaMain[$kyy.$ky] = $AreaDatal22->id;
            }
        }
        //level-3
        if(!empty($childAreaMain)){
            foreach($childAreaMain as $kyy =>$AreaDatal3 ){
                $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                foreach($AreaDatass as $ky =>$AreaDatal222 ){
                    $childArea3[$kyy.$ky] = $AreaDatal222->id;
                }
            }
        }   
        //level-4
        if(!empty($childAreaMain)){
            foreach($childArea3 as $kyy =>$AreaDatal4 ){
                $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                    $childArea4[$kyy.$ky] = $AreaDatal44->id;
                }
            }
        }
        $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
        array_push($mergeArr,$areaId);
        $mergeArr = array_unique($mergeArr);
        $finalIds = implode(',',$mergeArr);
        $finalIds = explode(',',$finalIds);
		
        $where = [];
        if ($areaId != -1) {
            $where['area_id in'] = $finalIds;
        }
        if (!empty($academic_period_id)) {
            $where['academic_periods.id'] = $academic_period_id;
        }
        if ($institution_id != 0) {
            $where['institutions.id'] = $institution_id;
        }
        if (!empty($institution_status_id)) {
            $where['institutions.institution_status_id'] = $institution_status_id;
        }
        
        $join=[];
        $conditions=[];

        $join['areas'] = [
            'type' => 'inner',
            'table' => 'areas',
            'conditions' => [
                'areas.id = institutions.area_id' 
            ]
        ];

        $join['area_levels'] = [
            'type' => 'inner',
            'table' => 'area_levels',
            'conditions' => [
                'area_levels.id = areas.area_level_id' 
            ]
        ];

        $join['institution_genders'] = [
            'type' => 'inner',
            'table' => 'institution_genders',
            'conditions' => [
                'institution_genders.id = institutions.institution_gender_id' 
            ]
        ];

        $join['institution_providers'] = [
            'type' => 'inner',
            'table' => 'institution_providers',
            'conditions' => [
                'institution_providers.id = institutions.institution_provider_id' 
            ]
        ];

        $join['academic_periods'] = [
            'type' => 'inner',
            'table' => 'academic_periods',
            'conditions' => [
                'OR'=>[
                    'OR'=>[
                        "(institutions.`date_closed` IS NOT NULL AND institutions.`date_opened` <= academic_periods.`start_date` AND institutions.`date_closed` >= academic_periods.`start_date`)",
                        "(institutions.`date_closed` IS NOT NULL AND institutions.`date_opened` <= academic_periods.`end_date` AND institutions.`date_closed` >= academic_periods.`end_date`)",
                        "(institutions.`date_closed` IS NOT NULL AND institutions.`date_opened` >= academic_periods.`start_date` AND institutions.`date_closed` <= academic_periods.`end_date`)"
                    ],
                    "(institutions.`date_closed` IS NULL AND institutions.`date_opened` <= academic_periods.`end_date`)"
                ]
            ]
        ];

        $join['lands_info'] = [
            'type' => 'left',
            'table' => "(SELECT institution_lands.institution_id	
            ,COUNT(DISTINCT(institution_lands.id)) land_counter
        FROM institution_lands
        WHERE institution_lands.land_status_id = 1
        AND institution_lands.academic_period_id = $academic_period_id
        GROUP BY institution_lands.institution_id)",
            'conditions' => [
                'lands_info.institution_id = institutions.id' 
            ]
        ];

        $join['buildings_info'] = [
            'type' => 'left',
            'table' => "(SELECT institution_buildings.institution_id	
            ,COUNT(DISTINCT(institution_buildings.id)) building_counter
        FROM institution_buildings
        WHERE institution_buildings.building_status_id = 1
        AND institution_buildings.academic_period_id = $academic_period_id
        GROUP BY institution_buildings.institution_id)",
            'conditions' => [
                'buildings_info.institution_id = institutions.id' 
            ]
        ];

        $join['floors_info'] = [
            'type' => 'left',
            'table' => "(SELECT institution_floors.institution_id	
            ,COUNT(DISTINCT(institution_floors.id)) floor_counter
        FROM institution_floors
        WHERE institution_floors.floor_status_id = 1
        AND institution_floors.academic_period_id = $academic_period_id
        GROUP BY institution_floors.institution_id)",
            'conditions' => [
                'floors_info.institution_id = institutions.id' 
            ]
        ];

        $join['rooms_info'] = [
            'type' => 'left',
            'table' => "(SELECT institution_rooms.institution_id	
            ,COUNT(DISTINCT(CASE WHEN room_types.classification = 1 THEN institution_rooms.id END)) classroom_counter
            ,COUNT(DISTINCT(CASE WHEN room_types.classification = 0 THEN institution_rooms.id END)) non_classroom_counter
        FROM institution_rooms
        INNER JOIN room_types
        ON room_types.id = institution_rooms.room_type_id
        WHERE institution_rooms.room_status_id = 1
        AND institution_rooms.academic_period_id = $academic_period_id
        GROUP BY institution_rooms.institution_id)",
            'conditions' => [
                'rooms_info.institution_id = institutions.id' 
            ]
        ];

        $query
            ->select([
                'academic_period' => 'academic_periods.name',
                'institution_code' => 'institutions.code',
                'institution_name' => 'institutions.name',
                'institution_gender' => 'institution_genders.name',
                'institution_provider' => 'institution_providers.name',
                'total_number_of_lands' => "(IFNULL(lands_info.land_counter, 0))",
                'total_number_of_buildings' => "(IFNULL(buildings_info.building_counter, 0))",
                'total_number_of_floors' => "(IFNULL(floors_info.floor_counter, 0))",
                'total_number_of_rooms_classrooms' => "(IFNULL(rooms_info.classroom_counter, 0))",
                'total_number_of_rooms_non_classrooms' => "(IFNULL(rooms_info.non_classroom_counter, 0))"
            ])
            ->from(['institutions' => 'institutions'])
            ->join($join)
            ->where($where);
    }
    

     public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
     { 
        $newFields[] = [
            'key' => 'academic_period',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $newFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'institution_gender',
            'field' => 'institution_gender',
            'type' => 'string',
            'label' => __('Institution Gender')
        ];

        $newFields[] = [
            'key' => 'institution_provider',
            'field' => 'institution_provider',
            'type' => 'string',
            'label' => __('Institution Provider')
        ];

        $newFields[] = [
            'key' => 'total_number_of_lands',
            'field' => 'total_number_of_lands',
            'type' => 'string',
            'label' => __('Total Number of Land')
        ];

        $newFields[] = [
            'key' => 'total_number_of_buildings',
            'field' => 'total_number_of_buildings',
            'type' => 'string',
            'label' => __('Total Number of Buildings')
        ];

        $newFields[] = [
            'key' => 'total_number_of_floors',
            'field' => 'total_number_of_floors',
            'type' => 'string',
            'label' => __('Total Number of Floors')
        ];

        $newFields[] = [
            'key' => 'total_number_of_rooms_classrooms',
            'field' => 'total_number_of_rooms_classrooms',
            'type' => 'string',
            'label' => __('Total Number of Rooms (classrooms)')
        ];

        $newFields[] = [
            'key' => 'total_number_of_rooms_non_classrooms',
            'field' => 'total_number_of_rooms_non_classrooms',
            'type' => 'string',
            'label' => __('Total Number of Rooms (non-classrooms)')
        ];

        $fields->exchangeArray($newFields);
    }
}
