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
		$this->table('security_users');
		parent::initialize($config);
		
		//$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		//$this->belongsTo('ContactTypes', ['className' => 'User.ContactTypes']);
		
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
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');
        $institutionStudents = TableRegistry::get('Institution.InstitutionStudents');
        $institutionIds = TableRegistry::get('Institution.Institutions');
        $educationGrades = TableRegistry::get('Education.EducationGrades');
        $userIdentity = TableRegistry::get('User.Identities');
        $identityType = TableRegistry::get('FieldOption.IdentityTypes');
        $userContacts = TableRegistry::get('User.Contacts');
        $contactsOptions = TableRegistry::get('User.ContactOptions');
        $contactsType = TableRegistry::get('User.ContactTypes');
        $academicPeriods = TableRegistry::get('academic_periods');

        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions['InstitutionStudents.academic_period_id'] = $academicPeriodId;
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['InstitutionStudents.institution_id'] = $institutionId;
        }
        if (!empty($enrolled)) {
            $conditions['InstitutionStudents.student_status_id'] = $enrolled;
        }
        $query
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'student_id' => $institutionStudents->aliasField('student_id'),
                'education_grade_id' => $institutionStudents->aliasField('education_grade_id'),
                'education_name' => 'EducationGrades.name',
                'education_code' => 'EducationGrades.code',
                'openemis_no' => $this->aliasField('openemis_no'),
                'security_user_id' => $this->aliasField('id'),
                'user_name' => $query->func()->concat([
                    $this->aliasField('first_name') => 'literal',
                    " ",
                    $this->aliasField('last_name') => 'literal'
                ]),
            ])
           ->leftJoin([$institutionStudents->alias() => $institutionStudents->table()],
                [
                $institutionStudents->aliasField('student_id') . ' = '. $this->aliasField('id')
                ])
            ->innerJoin([$institutionIds->alias() => $institutionIds->table()],
                [
                $institutionIds->aliasField('id') . ' = '. $institutionStudents->aliasField('institution_id')
                ])
            ->innerJoin([$educationGrades->alias() => $educationGrades->table()],
                [
                $educationGrades->aliasField('id') . ' = '. $institutionStudents->aliasField('education_grade_id')
                ])
            ->leftJoin([$academicPeriods->alias() => $academicPeriods->table()],
                [
                $academicPeriods->aliasField('id') . ' = '. $institutionStudents->aliasField('academic_period_id')
                ])
            ->where([$institutionStudents->aliasField('student_status_id') => 1, $conditions])
            ->group(['InstitutionStudents.student_id']);

            // $query
            //     ->select([
            //         'security_user_id' => $userIdentity->aliasField('security_user_id'),
            //         $query->func()->concat([
            //         $this->aliasField('IdentityTypes.name') => 'literal',
            //         " ",
            //          $this->aliasField('Identities.number') => 'literal',
            //         " "
            //         ]),
            //     ])
            //     ->innerJoin([$userIdentity->alias() => $userIdentity->table()],
            //     [
            //         $userIdentity->aliasField('security_user_id') . ' = '. $this->aliasField('id')
            //     ])
            //     ->innerJoin([$identityType->alias() => $identityType->table()],
            //     [
            //         $identityType->aliasField('id') . ' = '. $userIdentity->aliasField('identity_type_id')
            //     ])
            //     ->where([$identityType->aliasField('default') => 1, $conditions])
            //     ->group(['Identities.security_user_id']);

            $query
            ->select([

                      'contact_option_id' => $contactsType->aliasfield('contact_option_id'),

                      'contact_type' => $contactsType->aliasfield('name'),
                      'value' => 'Contacts.value',
                      'preferred' => $userContacts->aliasField('preferred')
                   ])
                // ->select([
                //     'contacts' => $userContacts->aliasField('security_user_id'),
                //     'contact_name' => $query->func()->concat([
                //     // $this->aliasField('ContactOptions.name') => 'literal',
                //     // " ",
                //     $this->aliasField('Contacts.value') => 'literal',
                //     " "
                //     ]),
                //    'description' => $contactsType->aliasField('name'),
                //    'preferred' => $userContacts->aliasField('preferred'),
                // ])
                ->innerJoin([$userContacts->alias() => $userContacts->table()],
                [
                    $userContacts->aliasField('security_user_id') . ' = '. $this->aliasField('id')
                ])
                ->innerJoin([$contactsType->alias() => $contactsType->table()],
                [
                    $contactsType->aliasField('id') . ' = '. $userContacts->aliasField('contact_type_id')
                ])
                ->innerJoin([$contactsOptions->alias() => $contactsOptions->table()],
                [
                    $contactsOptions->aliasField('id') . ' = '. $contactsType->aliasField('contact_option_id')
                ])
                ->where([$userContacts->aliasField('preferred') => 1, $conditions])
                ->group(['Contacts.security_user_id']);
                //print_r($query->sql()); die;
    }

	public function onExcelGetPreferred(Event $event, Entity $entity) {
		$options = [0 => __('No'), 1 => __('Yes')];
		return $options[$entity->preferred];
	}

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
		$extraFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
		
		$extraFields[] = [
            'key' => 'education_name',
            'field' => 'education_name',
            'type' => 'string',
            'label' => __('Education Grade')
        ];
		
        $extraFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];    

        $extraFields[] = [
            'key' => 'user_name',
            'field' => 'user_name',
            'type' => 'string',
            'label' => __('Student')
        ];    

        $extraFields[] = [
            'key' => 'identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        // $extraFields[] = [
        //     'key' => 'contact_name',
        //     'field' => 'contact_name',
        //     'type' => 'string',
        //     'label' => __('Mobile')
        // ];

        // $extraFields[] = [
        //     'key' => 'preferred',
        //     'field' => 'preferred',
        //     'type' => 'string',
        //     'label' => __('Preferred')
        // ];
        // $extraFields[] = [
        //     'key' => 'description',
        //     'field' => 'description',
        //     'type' => 'string',
        //     'label' => __('Description')
        // ];
		
		$ContactOptions = TableRegistry::get('contact_options');
                    
        $contactOptionsData = $ContactOptions->find()
            ->select([
                'contact_option_id' => $ContactOptions->aliasfield('id'),
                'contact_option' => $ContactOptions->aliasfield('name')
            ])
            ->toArray();
       
		if(!empty($contactOptionsData)) {
			foreach($contactOptionsData as $data) {
				$contact_option_id = $data->contact_option_id;
				$contact_option = $data->contact_option;
				$extraFields[] = [
					'key' => '',
					'field' => 'value_'.$contact_option_id,
					'type' => 'string',
					'label' => __($contact_option)
				];
				
				$extraFields[] = [
					'key' => '',
					'field' => 'description_'.$contact_option_id,
					'type' => 'string',
					'label' => __('Description')
				];
				
				$extraFields[] = [
					'key' => '',
					'field' => 'preferred_'.$contact_option_id,
					'type' => 'string',
					'label' => __('Preferred')
				];

			}
		}

        $fields->exchangeArray($extraFields);
    }

}
