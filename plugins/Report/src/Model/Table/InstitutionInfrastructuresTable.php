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
use Report\Model\Table\InstitutionPositionsTable as InstitutionPositions;
use Cake\Database\Connection;

class InstitutionInfrastructuresTable extends AppTable
{
    use OptionsTrait;
    private $classificationOptions = [];

    // filter
    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;

    public function initialize(array $config)
    {
        
        $this->table('institutions');

        parent::initialize($config);

        $this->addBehavior('Excel', ['excludes' => ['security_group_id', 'logo_name'], 'pages' => false]);
        $this->addBehavior('Report.ReportList');
     

    }

   public function beforeAction(Event $event)
    { 
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

   public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        
        $newFields = [];
        
        $newFields[] = [
            'key' => 'InstitutionsInfrastructure.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'InstitutionsInfrastructure.code',
            'field' => 'code',
            'type' => 'string',
            'alias' => 'institution_code',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_code',
            'field' => 'land_infrastructure_code',
            'type' => 'string',
            'label' => __('Infrastructure Code')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_name',
            'field' => 'land_infrastructure_name',
            'type' => 'string',
            'label' => __('Infrastructure Name')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_area',
            'field' => 'land_infrastructure_area',
            'type' => 'string',
            'label' => __('Infrastructure Area')
        ];

        $newFields[] = [
            'key' => 'land_start_date',
            'field' => 'land_start_date',
            'type' => 'string',
            'label' => __('Start Date')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_type',
            'field' => 'land_infrastructure_type',
            'type' => 'string',
            'label' => __('Infrastructure Type')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_ownership',
            'field' => 'land_infrastructure_ownership',
            'type' => 'string',
            'label' => __('Infrastructure Ownership')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_condition',
            'field' => 'land_infrastructure_condition',
            'type' => 'string',
            'label' => __('Infrastructure Condition')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_status',
            'field' => 'land_infrastructure_status',
            'type' => 'string',
            'label' => __('Infrastructure Status')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_accessibility',
            'field' => 'land_infrastructure_accessibility',
            'type' => 'string',
            'label' => __('Accessibility')
        ];

       
      
        $fields->exchangeArray($newFields);
           
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $infrastructureLevel = $requestData->infrastructure_level;
        $infrastructureType = $requestData->infrastructure_type;
        
        $institutionLands = TableRegistry::get('Institution.InstitutionLands');
        $institutionFloors = TableRegistry::get('Institution.InstitutionFloors');
        $institutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
        $institutionRooms = TableRegistry::get('Institution.InstitutionRooms');
        $buildingTypes = TableRegistry::get('building_types');
        $infrastructureCondition = TableRegistry::get('infrastructure_conditions');
        $infrastructureStatus = TableRegistry::get('infrastructure_statuses');
        $infrastructureOwnerships = TableRegistry::get('infrastructure_ownerships');

       

        $conditions = [];
        if (!empty($infrastructureType)) {
            $conditions[$institutionLands->aliasField('land_type_id')] = $infrastructureType;
        }
        if (!empty($institutionId)) {
            $conditions[$this->aliasField('id')] = $institutionId;
        }

        $query
                    ->select(['land_infrastructure_code'=>$institutionLands->aliasField('code'),
                    'land_infrastructure_name'=>$institutionLands->aliasField('name'),
                    'land_infrastructure_area'=>$institutionLands->aliasField('area'),
                    'land_start_date'=>$institutionLands->aliasField('start_date'),
                    'land_infrastructure_type'=>$buildingTypes->aliasField('name'),
                    'land_infrastructure_condition'=>$infrastructureCondition->aliasField('name'),
                    'land_infrastructure_status'=>$infrastructureStatus->aliasField('name'),
                    'land_infrastructure_ownership'=>$infrastructureOwnerships->aliasField('name'),
                    'land_infrastructure_accessibility' => $institutionLands->aliasField('accessibility')
                    ])
                    ->innerJoin([$institutionLands->alias() => $institutionLands->table()], [
                        $institutionLands->aliasField('institution_id = ') . $this->aliasField('id'),
                    ])
                    ->innerJoin([$buildingTypes->alias() => $buildingTypes->table()], [
                        $institutionLands->aliasField('land_type_id = ') . $buildingTypes->aliasField('id'),
                    ])
                    ->innerJoin([$infrastructureCondition->alias() => $infrastructureCondition->table()], [
                        $institutionLands->aliasField('infrastructure_condition_id = ') . $infrastructureCondition->aliasField('id'),
                    ])
                    ->innerJoin([$infrastructureStatus->alias() => $infrastructureStatus->table()], [
                        $institutionLands->aliasField('land_status_id = ') . $infrastructureStatus->aliasField('id'),
                    ])
                    ->innerJoin([$infrastructureOwnerships->alias() => $infrastructureOwnerships->table()], [
                        $institutionLands->aliasField('land_status_id = ') . $infrastructureOwnerships->aliasField('id'),
                    ])
                    ->where($conditions);
    }
}
