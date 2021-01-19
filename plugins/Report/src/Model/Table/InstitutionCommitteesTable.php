<?php
namespace Report\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Log\Log;
use Cake\I18n\Time;

use Cake\Network\Request;
use Cake\ORM\TableRegistry;

class InstitutionCommitteesTable extends AppTable
{
    use OptionsTrait;

    // position filter
    const ALL_POSITION = 0;
    const POSITION_WITH_STAFF = 1;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('InstitutionCommitteeTypes', ['className' => 'Institutions.InstitutionCommitteeTypes']);
        $this->hasMany('InstitutionCommitteeAttachments', [
            'className' => 'Institutions.InstitutionCommitteeAttachments',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
		
		$academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
		$conditions = [];
		
		if (!empty($academicPeriodId)) {
            $conditions['AcademicPeriods.id'] = $academicPeriodId;
        }
        
        if ($institutionId > 0) {
            $conditions[$this->aliasField('id')] = $institutionId;
        }

        $query
            ->select([
                /*'name' => $this->aliasField('name'),
                'meeting_date' => $this->aliasField('meeting_date'),
                'start_time' => $this->aliasField('start_time'),
                'end_time' => $this->aliasField('end_time'),
                'comment' => $this->aliasField('comment'),
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'committee_type' => 'InstitutionCommitteeTypes.name',*/
                'code' => 'Institutions.code',
                'instituion_name' => 'Institutions.name',
                'area_id' => 'Institutions.area_id'
            ])
            ->contain([
                'Institutions'/* => [
                    'fields' => [
                        'Institutions.code',
                        'Institutions.name'
                    ]
                ]*/,
                'AcademicPeriods' => [
                    'fields' => [
                        'AcademicPeriods.name'
                    ]
                ],
                'InstitutionCommitteeTypes' => [
                    'fields' => [
                        'InstitutionCommitteeTypes.name'
                    ]
                ]
            ])
            ->where($conditions);

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                
                $areaLevel = '';
                $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
                $areaLevelId = $ConfigItems->value('institution_area_level_id');
                $row['area_level'] = '';
                if($areaLevelId != 2){
                    $AreaTable = TableRegistry::get('Area.AreaLevels');
                    $value = $AreaTable->find()
                                ->where([$AreaTable->aliasField('level') => $areaLevelId])
                                ->first();
                
                    if (!empty($value->name)) {
                        $areaLevel = $value->name;
                    }

                    $row['area_level'] = $areaLevel;
                }
                return $row;
            });
        });
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $areas1 = TableRegistry::get('areas');
                $areasData = $areas1
                            ->find()
                            ->where([$areas1->alias('id')=>$row->area_id])
                            ->first();
                $row['area_code'] = '';            
                $row['area_name'] = '';
                if(!empty($areasData)){
                    $areas = TableRegistry::get('areas');
                    $areaLevels = TableRegistry::get('area_levels');
                    $institutions = TableRegistry::get('institutions');
                    $val = $areas
                                ->find()
                                ->select([
                                    $areas->aliasField('code'),
                                    $areas->aliasField('name'),
                                    ])
                                ->leftJoin(
                                    [$areaLevels->alias() => $areaLevels->table()],
                                    [
                                        $areas->aliasField('area_level_id  = ') . $areaLevels->aliasField('id')
                                    ]
                                )
                                ->leftJoin(
                                    [$institutions->alias() => $institutions->table()],
                                    [
                                        $areas->aliasField('id  = ') . $institutions->aliasField('area_id')
                                    ]
                                )    
                                ->where([
                                    $areaLevels->aliasField('level !=') => 2,
                                    $areas->aliasField('id') => $areasData->parent_id
                                ])->first();
                    
                    if (!empty($val->name) && !empty($val->code)) {
                        $row['area_code'] = $val->code;
                        $row['area_name'] = $val->name;
                    }
                }            
                
                return $row;
            });
        });

    }

    public function onExcelRenderStartTime(Event $event, Entity $entity, array $attr)
    {
        $entity->start_time = $entity->start_time->i18nFormat('h:mm:ss a');
        return $entity->start_time;        
    }

    public function onExcelRenderEndTime(Event $event, Entity $entity, array $attr)
    {
        $entity->end_time = $entity->end_time->i18nFormat('h:mm:ss a');
        return $entity->end_time;        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'instituion_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'area_level',
            'field' => 'area_level',
            'type' => 'string',
            'label' => __('Area Level')
        ];
        //add columns starts
        $newFields[] = [
            'key' => 'area_code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];
        
        $newFields[] = [
            'key' => 'area_name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Name')
        ];
        //add columns ends
        
        

        /*$newFields[] = [
            'key' => '',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => __('Academic Period')
        ];
        
        $newFields[] = [
            'key' => 'InstitutionCommitteeTypes.name',
            'field' => 'committee_type',
            'type' => 'string',
            'label' => __('Type')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Name')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'meeting_date',
            'type' => 'date',
            'label' => __('Date of Meeting')
        ];

        $newFields[] = [
            'key' => 'start_time',
            'field' => '',
            'type' => 'start_time',
            'label' => __('Start Time')
        ];

        $newFields[] = [
            'key' => 'end_time',
            'field' => '',
            'type' => 'end_time',
            'label' => __('End Time')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];*/
              
        $fields->exchangeArray($newFields);
    }    
}
