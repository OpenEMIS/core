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
            ->order([$this->aliasField('security_user_id')])
            ->where(['Users.is_staff' => 1]);

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
