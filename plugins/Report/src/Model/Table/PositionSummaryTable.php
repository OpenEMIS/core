<?php
namespace Report\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class PositionSummaryTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('institution_positions');
        parent::initialize($config);
        
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
        $this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'Security.Users']);
        $this->hasMany('InstitutionStaff', ['className' => 'Institution.Staff']);

        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
       $this->addBehavior('Report.InstitutionSecurity');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $area_id = $requestData->area_id;
        $institution_id = $requestData->institution_id;

        if ($area_id == 0 && $institution_id != 0) {
            $where = ['Institutions.id' => $institution_id];
        } else if ($area_id != 0 && $institution_id != 0) {           
            $where = ['Institutions.id' => $institution_id,'Institutions.area_id' => $area_id]; 
        } else if ($area_id !=0 && $institution_id == 0) {
            $where = ['Institutions.area_id' => $area_id];
        } else {
            $where = [];
        }
        $InstitutionStaff = TableRegistry::get('Institution.InstitutionStaff');
        $Staff = TableRegistry::get('Security.Users');
        $Genders = TableRegistry::get('User.Genders');
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $UserIdentities = TableRegistry::get('User.Identities');
        
        $query
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',               
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'openemis_no' => $Staff->aliasField('openemis_no'),
                'first_name' => $Staff->aliasField('first_name'),
                'last_name' => $Staff->aliasField('last_name'),
                'gender' => $Genders->aliasField('name'),
            ])
            ->contain([
                'StaffPositionTitles' => [
                    'fields' => [
                        'StaffPositionTitles.id',
                        'StaffPositionTitles.name',
                        'StaffPositionTitles.type'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                        'Institutions.id',
                        'Institutions.name',
                        'Institutions.code'
                    ]
                ],
                'Institutions.Areas' => [
                    'fields' => [
                        'Areas.name',
                        'Areas.code'
                    ]
                ]
            ])
            ->leftJoin(
                    [$InstitutionStaff->alias() => $InstitutionStaff->table()],
                    [
                        $InstitutionStaff->aliasField('institution_position_id = ') . $this->aliasField('id'),
                        $InstitutionStaff->aliasField('institution_id = ') . $this->aliasField('institution_id')
                    ]
                )
            ->leftJoin(
                    [$Staff->alias() => $Staff->table()],
                    [
                        $Staff->aliasField('id = ') . $InstitutionStaff->aliasField('staff_id')
                    ]
                )
            ->leftJoin(
                    [$Genders->alias() => $Genders->table()],
                    [
                        $Genders->aliasField('id = ') . $Staff->aliasField('gender_id')
                    ]
                )
            ->where([$where])
            ->order(['institution_name']);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
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
            'label' => __('Area')
        ];

        $newFields[] = [
            'key' => 'StaffPositionTitles.id',
            'field' => 'staff_position_id',
            'type' => 'string',
            'label' => __('Position Title')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'first_name',
            'type' => 'string',
            'label' => __('First Name')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'last_name',
            'type' => 'string',
            'label' => __('Last Name')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStaffPositionId(Event $event, Entity $entity)
    {
        $options = $this->getSelectOptions('Staff.position_types');
        $staffPositionTitleType = '';

        if ($entity->has('staff_position_title')) {
            $staffPositionTitleType = $entity->staff_position_title->name;
            $staffType = $entity->staff_position_title->type;
            $type = array_key_exists($staffType, $options) ? $options[$staffType] : '';

            if (!empty($type)) {
                $staffPositionTitleType .= ' - ' . $type;
            }
        } else {
            Log::write('debug', $entity->name . ' has no staff_position_title...');
        }

        return $staffPositionTitleType;
    }

    public function onExcelGetInstitutionId(Event $event, Entity $entity)
    {
        return $entity->institution->code_name;
    }

    public function onExcelGetIsHomeroom(Event $event, Entity $entity)
    {
        $options = $this->getSelectOptions('general.yesno');
        return $options[$entity->is_homeroom];
    }

    public function onExcelGetStaffName(Event $event, Entity $entity)
    {
        if ($entity->has('_matchingData')) {
            return $entity->_matchingData['Users']->name;
        }
        return '';
    }
}
