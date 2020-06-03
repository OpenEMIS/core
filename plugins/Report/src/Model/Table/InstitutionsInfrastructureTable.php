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

class InstitutionsInfrastructureTable extends AppTable
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
        $this->ControllerAction->field('feature', ['select' => false]);
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
            'label' => __('Land Infrastructure Code')
        ];

        $newFields[] = [
            'key' => 'floors_infrastructure_code',
            'field' => 'floors_infrastructure_code',
            'type' => 'string',
            'label' => __('Floors Infrastructure Code')
        ];

        $newFields[] = [
            'key' => 'buildings_infrastructure_code',
            'field' => 'buildings_infrastructure_code',
            'type' => 'string',
            'label' => __('Buildings Infrastructure Code')
        ];

        $newFields[] = [
            'key' => 'rooms_infrastructure_code',
            'field' => 'rooms_infrastructure_code',
            'type' => 'string',
            'label' => __('Rooms Infrastructure Code')
        ];
       
            $fields->exchangeArray($newFields);
           
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $filter = $requestData->institution_filter;
        $superAdmin = $requestData->super_admin;
        $userId = $requestData->user_id;
        $institutionLands = TableRegistry::get('Institution.InstitutionLands');
        $institutionFloors = TableRegistry::get('Institution.InstitutionFloors');
        $institutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
        $institutionRooms = TableRegistry::get('Institution.InstitutionRooms');

        $query
                    ->select(['land_infrastructure_code'=>$institutionLands->aliasField('code'),
                    'floors_infrastructure_code'=>$institutionFloors->aliasField('code'),
                    'buildings_infrastructure_code'=>$institutionBuildings->aliasField('code'),
                    'rooms_infrastructure_code'=>$institutionRooms->aliasField('code')
                    ])
                    ->innerJoin([$institutionLands->alias() => $institutionLands->table()], [
                        $institutionLands->aliasField('institution_id = ') . $this->aliasField('id'),
                    ])
                    ->innerJoin([$institutionFloors->alias() => $institutionFloors->table()], [
                        $institutionFloors->aliasField('institution_id = ') . $this->aliasField('id'),
                    ])
                    ->innerJoin([$institutionBuildings->alias() => $institutionBuildings->table()], [
                        $institutionBuildings->aliasField('institution_id = ') . $this->aliasField('id'),
                    ])
                    ->innerJoin([$institutionRooms->alias() => $institutionRooms->table()], [
                        $institutionRooms->aliasField('institution_id = ') . $this->aliasField('id'),
                    ])
                    ;

        if (!$superAdmin) {
            $query->find('byAccess', ['user_id' => $userId, 'institution_field_alias' => $this->aliasField('id')]);
        }
    }
}
