<?php
namespace Report\Model\Table;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\Event\Event;
use Cake\I18n\Date;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\ORM\ResultSet;
use DateTime;

class SpecialNeedsFacilitiesTable extends ControllerActionTable
{
    use OptionsTrait;
    const LAND = 'Land';
    const FLOOR = 'Floor';
    const ROOM = 'Room';
    const BUILDING = 'Building';

    const IN_USE = 1;
    const UPDATE_DETAILS = 1;// In Use
    const END_OF_USAGE = 2;
    const CHANGE_IN_TYPE = 3;

    private $Levels = null;
    private $levelOptions = [];
    private $landLevel = null;

    private $canUpdateDetails = true;
    private $currentAcademicPeriod = null;

    public function initialize(array $config)
    {
        $this->table('institution_lands');
        parent::initialize($config);

        $this->belongsTo('LandStatuses', ['className' => 'Infrastructure.InfrastructureStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('LandTypes', ['className' => 'Infrastructure.LandTypes']);
        $this->belongsTo('InfrastructureOwnership', ['className' => 'FieldOption.InfrastructureOwnerships']);
        $this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);
        $this->belongsTo('PreviousLands', ['className' => 'Institution.InstitutionLands', 'foreignKey' => 'previous_institution_land_id']);
        $this->hasMany('InstitutionBuildings', ['className' => 'Institution.InstitutionBuildings', 'dependent' => true]);

        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        $this->addBehavior('Institution.InfrastructureShift');

        $this->Levels = TableRegistry::get('Infrastructure.InfrastructureLevels');
        $this->levelOptions = $this->Levels->find('list')->toArray();
        $this->accessibilityOptions = $this->getSelectOptions('InstitutionAssets.accessibility');
        $this->accessibilityTooltip = $this->getMessage('InstitutionInfrastructures.accessibilityOption');
        $this->effectiveDateTooltip = $this->getMessage('InstitutionInfrastructures.effectiveDate');
        $this->setDeleteStrategy('restrict');
        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

   public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        
        $InstitutionFloors = TableRegistry::get('Institution.InstitutionFloors');
        $InstitutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
        $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');
        $requestData = json_decode($settings['process']['params']);
      
        $institutionTypeId = $requestData->institution_type_id;
        $institution_id = $requestData->institution_id;

        $conditionsLands = [];
        $conditionsFloors = [];
        $conditionsRooms = [];
        $conditionsBuildings = [];
        

        $institutions = TableRegistry::get('Institution.Institutions');
        $institutionIds = $institutions->find('list', [
                                                    'keyField' => 'id',
                                                    'valueField' => 'id'
                                                ])
                        ->where(['institution_type_id' => $institutionTypeId])
                        ->toArray();
        
      
        if (!empty($institutionTypeId) && $institution_id == 0) {

            if($query->repository['registryAlias'] ='Report.SpecialNeedsFacilities' ){
                $conditionsLands[$this->aliasField('institution_id IN')] = $institutionIds;
            }
            
            if($InstitutionFloors){
                $conditionsFloors[$InstitutionFloors->aliasField('institution_id IN')] = $institutionIds;
            }  

            if($InstitutionRooms ){
                $conditionsRooms[$InstitutionRooms->aliasField('institution_id IN')] = $institutionIds;
            } 

            if($InstitutionBuildings){
                $conditionsBuildings[$InstitutionBuildings->aliasField('institution_id IN')] = $institutionIds;
            } 
        }

         if (!empty($institution_id)) {

            if($query->repository['registryAlias'] ='Report.SpecialNeedsFacilities' ){
                $conditionsLands[$this->aliasField('institution_id')] = $institution_id;
            }

            if($InstitutionFloors){
               $conditionsFloors[$InstitutionFloors->aliasField('institution_id')] = $institution_id;
            }

            if($InstitutionRooms){
                $conditionsRooms[$InstitutionRooms->aliasField('institution_id')] = $institution_id;
            }

            if($InstitutionBuildings){
                 $conditionsBuildings[$InstitutionBuildings->aliasField('institution_id')] = $institution_id;
            }

        }

         $query
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'code' => $this->aliasField('code'),
                'name' => $this->aliasField('name'),
                'accessibility' => $this->aliasField('accessibility'),
                'infrastructure_condition_name' => 'InfrastructureConditions.name',
                'infrastructure_status_name' => 'LandStatuses.name',
                'infrastructure_type' => 'LandTypes.name',
                'infrastructure_ownership' => 'InfrastructureOwnership.name',
                'infrastructure_level' => $query->func()->concat([self::LAND])
            ])
            ->where([$this->aliasField('accessibility') => 1,$conditionsLands])
            ->contain(['InfrastructureConditions', 'LandStatuses', 'LandTypes', 'Institutions', 'InfrastructureOwnership'])
            ->union(
                $InstitutionFloors->find()
                    ->select([
                        'institution_code' => 'Institutions.code',
                        'institution_name' => 'Institutions.name',
                        'code' => $InstitutionFloors->aliasField('code'),
                        'name' => $InstitutionFloors->aliasField('name'),
                        'accessibility' => $InstitutionFloors->aliasField('accessibility'),
                        'infrastructure_condition_name' => 'InfrastructureConditions.name',
                        'infrastructure_status_name' => 'FloorStatuses.name',
                        'infrastructure_type' => 'FloorTypes.name',
                        'infrastructure_ownership' => $query->func()->concat([""]),
                        'infrastructure_level' => $query->func()->concat([self::FLOOR])
                    ])
                    ->where([$InstitutionFloors->aliasField('accessibility') => 1,$conditionsFloors])
                    ->contain(['InfrastructureConditions', 'FloorStatuses', 'FloorTypes', 'Institutions'])
            )            
            ->union(
                $InstitutionRooms->find()
                    ->select([
                        'institution_code' => 'Institutions.code',
                        'institution_name' => 'Institutions.name',
                        'code' => $InstitutionRooms->aliasField('code'),
                        'name' => $InstitutionRooms->aliasField('name'),
                        'accessibility' => $InstitutionRooms->aliasField('accessibility'),
                        'infrastructure_condition_name' => 'InfrastructureConditions.name',
                        'infrastructure_status_name' => 'RoomStatuses.name',
                        'infrastructure_type' => 'RoomTypes.name',
                        'infrastructure_ownership' => $query->func()->concat([""]),
                        'infrastructure_level' => $query->func()->concat([self::ROOM])
                    ])
                    ->where([$InstitutionRooms->aliasField('accessibility') => 1,$conditionsRooms])
                    ->contain(['InfrastructureConditions', 'RoomStatuses', 'RoomTypes', 'Institutions'])
            )
            
            ->union(
                $InstitutionBuildings->find()
                    ->select([
                        'institution_code' => 'Institutions.code',
                        'institution_name' => 'Institutions.name',
                        'code' => $InstitutionBuildings->aliasField('code'),
                        'name' => $InstitutionBuildings->aliasField('name'),
                        'accessibility' => $InstitutionBuildings->aliasField('accessibility'),
                        'infrastructure_condition_name' => 'InfrastructureConditions.name',
                        'infrastructure_status_name' => 'BuildingStatuses.name',
                        'infrastructure_type' => 'BuildingTypes.name',
                        'infrastructure_ownership' => 'InfrastructureOwnership.name',
                        'infrastructure_level' => $query->func()->concat([self::BUILDING])
                    ])
                    ->where([$InstitutionBuildings->aliasField('accessibility') => 1,$conditionsBuildings])
                    ->contain(['InfrastructureConditions', 'BuildingStatuses', 'BuildingTypes', 'Institutions', 'InfrastructureOwnership'])
        ) ;
       
    }

    public function onExcelRenderAccessibility(Event $event, Entity $entity, $attr)
    {
        if ($entity->accessibility == 1) {
            return 'Accessible';
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => '',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Infrastructure Code')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Infrastructure Name')
        ];

        

        $newFields[] = [
            'key' => '',
            'field' => 'accessibility',
            'type' => 'accessibility',
            'label' => __('Accessibility')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'infrastructure_condition_name',
            'type' => 'string',
            'label' => __('Infrastructure Condition')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'infrastructure_status_name',
            'type' => 'string',
            'label' => __('Infrastructure Status')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'infrastructure_type',
            'type' => 'string',
            'label' => __('Infrastructure Type')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'infrastructure_ownership',
            'type' => 'string',
            'label' => __('Infrastructure Ownership')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'infrastructure_level',
            'type' => 'string',
            'label' => __('Infrastructure Level')
        ];

        $fields->exchangeArray($newFields);
    }
}
