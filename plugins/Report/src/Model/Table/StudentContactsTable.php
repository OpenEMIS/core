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
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');
        $conditions = [];
        if ($areaId != -1) {
            $conditions['Institution.area_id'] = $areaId;
        }
        if (!empty($academicPeriodId)) {
            $conditions['InstitutionStudent.academic_period_id'] = $academicPeriodId;
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['InstitutionStudent.institution_id'] = $institutionId;
        }
        if (!empty($enrolled)) {
            $conditions['InstitutionStudent.student_status_id'] = $enrolled;
        }
        $query->join([
            'InstitutionStudent' => [
                'type' => 'inner',
                'table' => 'institution_students', 
                'conditions' => [
                    'InstitutionStudent.student_id = '.$this->aliasField('id')
                ],
            ],
            'Institution' => [
                'type' => 'inner',
                'table' => 'institutions',
                'conditions' => [
                    'Institution.id = InstitutionStudent.institution_id'
                ]
            ]
        ]);
        $query
			->select([
                'security_user_id' => $this->aliasField('id'),
				'user_name' => $query->func()->concat([
					$this->aliasField('first_name') => 'literal',
					" ",
					$this->aliasField('last_name') => 'literal'
				]),
            ])
            ->order([$this->aliasField('id') => 'DESC'])
            ->where([$this->aliasField('is_student') => 1, $conditions]);
		    $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
				
				$ContactTypes = TableRegistry::get('User.ContactTypes');
                    
				$contactTypesData = $ContactTypes->find()
					->select([
						'contact_option_id' => $ContactTypes->aliasfield('contact_option_id'),
						'contact_type' => $ContactTypes->aliasfield('name'),
						'value' => 'UserContacts.value',
						'preferred' => 'UserContacts.preferred'
					])
					->innerJoin(['UserContacts' => 'user_contacts' ], [
						'UserContacts.contact_type_id = ' . $ContactTypes->aliasField('id'),
					])
					->where(['UserContacts.security_user_id' => $row->security_user_id])
					->toArray();
			
					if(!empty($contactTypesData)) {
						foreach($contactTypesData as $data) {
							$row['value_'.$data->contact_option_id] = $data->value;
							$row['description_'.$data->contact_option_id] = $data->contact_type;
							if($data->preferred == 1) {
								$row['preferred_'.$data->contact_option_id] = 'Yes';								
							} else {
								$row['preferred_'.$data->contact_option_id] = 'No';
							}
						}
					}
                return $row;
            });
        });
    	
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
