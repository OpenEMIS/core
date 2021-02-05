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
        //POCOR-5905 starts
        $institutionCommitteeMeeting = TableRegistry::get('institution_committee_meeting');
        $institutions = TableRegistry::get('institutions');

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

        $query
            ->select([
                'name' => $this->aliasField('name'),
                'meeting_date' => $institutionCommitteeMeeting->aliasField('meeting_date'),
                'start_time' => $institutionCommitteeMeeting->aliasField('start_time'),
                'end_time' => $institutionCommitteeMeeting->aliasField('end_time'),
                'comment' => $institutionCommitteeMeeting->aliasField('comment'),
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'committee_type' => 'InstitutionCommitteeTypes.name',
                'code' => 'Institutions.code',
                'instituion_name' => 'Institutions.name'
            ])
            ->contain([
                'Institutions' => [
                    'fields' => [
                        'Institutions.code',
                        'Institutions.name'
                    ]
                ],
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
            ->leftJoin([$institutionCommitteeMeeting->alias() => $institutionCommitteeMeeting->table()], [
                $institutionCommitteeMeeting->aliasField('institution_committee_id = ') . $this->aliasField('id')
            ])
            ->where([
                $this->aliasField('academic_period_id') => $requestData->academic_period_id,
                $this->aliasField('institution_id').' IN ('.$institutions. ')'
            ]);
            //POCOR-5905 ends
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
        $institutionCommitteeMeeting = TableRegistry::get('institution_committee_meeting');
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
        //POCOR-5905 starts
        $newFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period_id',
            'type' => 'string',
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
            'key' => $institutionCommitteeMeeting->aliasField('meeting_date'),
            'field' => 'meeting_date',
            'type' => 'date',
            'label' => __('Date of Meeting')
        ];

        $newFields[] = [
            'key' => $institutionCommitteeMeeting->aliasField('start_time'),
            'field' => 'start_time',
            'type' => 'string',
            'label' => __('Start Time')
        ];

        $newFields[] = [
            'key' => $institutionCommitteeMeeting->aliasField('end_time'),
            'field' => 'end_time',
            'type' => 'string',
            'label' => __('End Time')
        ];

        $newFields[] = [
            'key' => $institutionCommitteeMeeting->aliasField('comment'),
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];
        //POCOR-5905 ends      
        $fields->exchangeArray($newFields);
    }    
}
