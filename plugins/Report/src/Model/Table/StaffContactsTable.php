<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class StaffContactsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('user_contacts');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('ContactTypes', ['className' => 'User.ContactTypes']);
		
		$this->addBehavior('Excel');
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		return $attr;
	}

    public function onExcelGetIdentityNumber(Event $event, Entity $entity)
    {
        $IdentityNumber = '';
        $userIdentities = TableRegistry::get('User.Identities');
        $result = $userIdentities
                    ->find()
                    ->where([
                        $userIdentities->aliasField('security_user_id') => $entity->security_user_id,
                    ])
                    ->contain(['IdentityTypes'])
                    ->select([
                    'IdentityTypes' => $userIdentities->IdentityTypes->aliasField('name'),
                    'IdentityNumber' => $userIdentities->aliasField('number'),
                        ])
                    ->toArray();

        if(!empty($result)) {
            foreach ($result as $single) {
                if ($single->IdentityNumber == end($result)->IdentityNumber) {
                    $IdentityNumber .= $single->IdentityTypes .' - '. $single->IdentityNumber;
                } else {
                    $IdentityNumber .= $single->IdentityTypes .' - '. $single->IdentityNumber . ', ';
                }
            }
        }

        return $IdentityNumber;
    }    

    public function onExcelGetNationality(Event $event, Entity $entity)
    {
        $Nationalities = '';
        $userNationalities = TableRegistry::get('User.UserNationalities');
        $result = $userNationalities
                    ->find()
                    ->where([
                        $userNationalities->aliasField('security_user_id') => $entity->security_user_id,
                    ]) 
                    ->select([
                    'name' => $userNationalities->NationalitiesLookUp->aliasField('name')
                        ])
                    ->contain(['NationalitiesLookUp'])
                    ->toArray();

        if(!empty($result)) {
            foreach ($result as $single) {
                if ($single->name == end($result)->name) {
                    $Nationalities .= $single->name;
                } else {
                    $Nationalities .= $single->name . ', ';
                }
            }
        }

        return $Nationalities;
    }

    public function onExcelGetTeachingStatus(Event $event, Entity $entity)
    {
        $teachingStatus = '';
        $securityRoles = TableRegistry::get('Security.SecurityRoles');
        $staffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
        $Status = TableRegistry::get('Security.SecurityGroupUsers');

        $query = $staffPositionTitles->find();
        $type = $query->func()->sum('type');

        $result = $Status
                    ->find()
                    ->where([
                        $Status->aliasField('security_user_id') => $entity->security_user_id,
                    ]) 
                    ->select([
                    'type' => $type,
                        ])
                    ->leftJoin([$securityRoles->alias() => $securityRoles->table()], [
                        $securityRoles->aliasField('id = ') . $Status->aliasField('security_role_id')
                    ])
                    ->leftJoin([$staffPositionTitles->alias() => $staffPositionTitles->table()], [
                        $staffPositionTitles->aliasField('security_role_id = ') . $securityRoles->aliasField('id')
                    ])
                    ->group([
                        $Status->aliasField('security_user_id'),
                    ])
                    ->first();

        if(!empty($result)) {
            if ($result->type >0) {
                $teachingStatus = 'Teaching';
            } else {
                $teachingStatus = 'Non-Teaching';
            }
        } else {
            $teachingStatus = 'Non-Teaching';
        }

        return $teachingStatus;
    }

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $conditions = [];
        if (!empty($academicPeriodId)) {
                $conditions['OR'] = [
                    'OR' => [
                        [
                            'InstitutionStaff.end_date' . ' IS NOT NULL',
                            'InstitutionStaff.start_date' . ' <=' => $startDate,
                            'InstitutionStaff.end_date' . ' >=' => $startDate
                        ],
                        [
                            'InstitutionStaff.end_date' . ' IS NOT NULL',
                            'InstitutionStaff.start_date' . ' <=' => $endDate,
                            'InstitutionStaff.end_date' . ' >=' => $endDate
                        ],
                        [
                            'InstitutionStaff.end_date' . ' IS NOT NULL',
                            'InstitutionStaff.start_date' . ' >=' => $startDate,
                            'InstitutionStaff.end_date' . ' <=' => $endDate
                        ]
                    ],
                    [
                        'InstitutionStaff.end_date' . ' IS NULL',
                        'InstitutionStaff.start_date' . ' <=' => $endDate
                    ]
                ];
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['InstitutionStaff.institution_id'] = $institutionId; 
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions[$InstitutionsTable->aliasField('area_id')] = $areaId; 
        }
        $query
            ->contain([
                'Users' => [
                    'fields' => [
                        'openemis_no' => 'Users.openemis_no',
                        'Users.id',
                        'Users.first_name',
                        'Users.middle_name',
                        'Users.third_name',
                        'Users.last_name',
                        'Users.preferred_name',
                    ]
                ],
            ])
            ->leftJoin(['InstitutionStaff' => 'institution_staff'], [
                'InstitutionStaff.staff_id = ' . $this->aliasField('security_user_id')
            ])
            ->leftJoin([$InstitutionsTable->alias() => $InstitutionsTable->table()], [
                $InstitutionsTable->aliasField('id = ') . 'InstitutionStaff.institution_id'
            ])
            ->order([$this->aliasField('security_user_id')])
            ->where(['Users.is_staff' => 1, $conditions]);

	}

	public function onExcelGetPreferred(Event $event, Entity $entity) {
		$options = [0 => __('No'), 1 => __('Yes')];
		return $options[$entity->preferred];
	}

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        foreach ($fields as $key => $field) {
            // change formatting to string to avoid content unreadable errors on excel
            if ($field['field'] == 'value') {
                $fields[$key]['formatting'] = 'string';
                break;
            }
        }

        $cloneFields = $fields->getArrayCopy();

        $extraFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $extraFields[] = [
            'key' => 'identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        $extraFields[] = [
            'key' => 'nationality',
            'field' => 'nationality',
            'type' => 'string',
            'label' => __('Nationality')
        ];

        $extraFields[] = [
            'key' => 'teaching_status',
            'field' => 'teaching_status',
            'type' => 'string',
            'label' => __('Teaching status')
        ];        

        $newFields = array_merge($cloneFields, $extraFields);
        $fields->exchangeArray($newFields);

    }
}
