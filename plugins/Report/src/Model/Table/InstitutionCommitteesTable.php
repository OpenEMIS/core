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
use Cake\ORM\TableRegistry;
use Cake\Network\Request;


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
        $institutions = TableRegistry::get('institutions');
        $areaId = $requestData->area_education_id;
        $where = [];
        if($requestData->institution_id == 0){
            $institutions_Arr = $institutions
                                    ->find('list', [
                                        'keyField' => 'id',
                                        'valueField' => 'name',
                                    ])
                                    ->select([
                                        'id' => $institutions->aliasField('id'), 
                                        'name' => $institutions->aliasField('name')])
                                    ->toArray();
            if(!empty($institutions_Arr)){
                $institutions = implode(',', array_keys($institutions_Arr));
            }
        }else{
            $institutions = $requestData->institution_id;
        }
        if ($areaId != -1) {
            $where['Institutions.area_id'] = $areaId;
        }

        $query
            ->select([
                'code' => 'Institutions.code',
                'instituion_name' => 'Institutions.name',
                'name' => $this->aliasField('name'),
                'chairperson' => $this->aliasField('chairperson'),
                'telephone' => $this->aliasField('telephone'),
                'instituion_name' => 'Institutions.name',
                'area_id' => 'Institutions.area_id',
                'area_name' => 'Areas.name',
                'area_code' => 'Areas.code',
            ])
            ->contain([
                'Institutions',
                'Institutions.Areas',
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
            ->where([
                $this->aliasField('academic_period_id') => $requestData->academic_period_id,
                $this->aliasField('institution_id').' IN ('.$institutions. ')',
                $where
            ]);
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
        //add columns POCOR-5394 starts
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
            'key' => '',
            'field' => 'name',
            'type' => 'string',
            'label' => __('School Board Name')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'chairperson',
            'type' => 'string',
            'label' => __('Chairperson')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'telephone',
            'type' => 'string',
            'label' => __('Contact No')
        ];
        //add columns POCOR-5394 ends 
        $fields->exchangeArray($newFields);
    }    
}
