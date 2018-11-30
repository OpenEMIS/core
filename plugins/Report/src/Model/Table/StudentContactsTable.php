<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class StudentContactsTable extends AppTable  {
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

    public function onExcelGetEducationName(Event $event, Entity $entity)
    {
        $educationName = '';
        $institutionStudents = TableRegistry::get('Institution.Students');
        $result = $institutionStudents
                    ->find()
                    ->where([
                        $institutionStudents->aliasField('student_id') => $entity->security_user_id,
                        $institutionStudents->aliasField('end_date >= Date("' . date("Y-m-d") . '")') ,
                    ]) 
                    ->select([
                    'educationName' => 'EducationGrades.name'
                        ])
                    ->leftJoinWith('EducationGrades')
                    ->all();

        if (!$result->isEmpty()) {
            $educationName = $result->first()->educationName;
        }
        return $educationName;
    }

    public function onExcelGetInstitutionName(Event $event, Entity $entity)
    {
        $institutionName = '';
        $institutionStudents = TableRegistry::get('Institution.Students');
        $result = $institutionStudents
                    ->find()
                    ->where([
                        $institutionStudents->aliasField('student_id') => $entity->security_user_id,
                        $institutionStudents->aliasField('end_date >= Date("' . date("Y-m-d") . '")') ,
                    ]) 
                    ->select([
                    'institutionName' => 'Institutions.name'
                        ])
                    ->leftJoinWith('Institutions')
                    ->all();

        if (!$result->isEmpty()) {
            $institutionName = $result->first()->institutionName;
        }
        return $institutionName;
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
            ->where(['Users.is_student' => 1]);
	}

	public function onExcelGetPreferred(Event $event, Entity $entity) {
		$options = [0 => __('No'), 1 => __('Yes')];
		return $options[$entity->preferred];
	}

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
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
            'key' => 'education_name',
            'field' => 'education_name',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $extraFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields = array_merge($cloneFields, $extraFields);
        $fields->exchangeArray($newFields);
    }

}
