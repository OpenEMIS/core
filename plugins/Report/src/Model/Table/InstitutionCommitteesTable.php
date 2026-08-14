<?php
namespace Report\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;


class InstitutionCommitteesTable extends AppTable
{
    use OptionsTrait;

    // position filter
    const ALL_POSITION = 0;
    const POSITION_WITH_STAFF = 1;

    public function initialize(array $config): void
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

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $where = [];

        $institutionIds = [];
        if (is_object($institutionId) && isset($institutionId->_ids)) {
            $institutionIds = array_values(array_filter((array)$institutionId->_ids, function ($id) {
                return $id !== '' && $id !== null && $id !== '0' && $id !== 0;
            }));
        } elseif (is_array($institutionId) && isset($institutionId['_ids'])) {
            $institutionIds = array_values(array_filter((array)$institutionId['_ids'], function ($id) {
                return $id !== '' && $id !== null && $id !== '0' && $id !== 0;
            }));
        } elseif ($institutionId == 0 || $institutionId === '0') {
            $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $institutionIds = array_keys($Institutions
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->toArray());
        } elseif (!empty($institutionId) && $institutionId != 0 && $institutionId != '0') {
            $institutionIds = [(int)$institutionId];
        }

        if ($areaId != -1) {
            $where['Institutions.area_id'] = $areaId;
        }

        $conditions = [
            $this->aliasField('academic_period_id') => $requestData->academic_period_id,
            $where
        ];
        if (!empty($institutionIds)) {
            $conditions[$this->aliasField('institution_id') . ' IN'] = $institutionIds;
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
            ->where($conditions);
    }

    public function onExcelRenderStartTime(EventInterface $event, Entity $entity, array $attr)
    {
        $entity->start_time = $entity->start_time->i18nFormat('h:mm:ss a');
        return $entity->start_time;        
    }

    public function onExcelRenderEndTime(EventInterface $event, Entity $entity, array $attr)
    {
        $entity->end_time = $entity->end_time->i18nFormat('h:mm:ss a');
        return $entity->end_time;        
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
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
