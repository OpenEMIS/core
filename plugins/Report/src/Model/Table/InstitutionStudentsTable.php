<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class InstitutionStudentsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);

		$this->belongsTo('Users',			['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentStatuses',	['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationGrades',	['className' => 'Education.EducationGrades']);
		$this->belongsTo('Institutions',	['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods',	['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Excel', [
			'excludes' => ['start_year', 'end_year'], 
			'pages' => false
		]);
		$this->addBehavior('Report.InstitutionSecurity');

        $this->statuses = $this->StudentStatuses->findCodeList();
	}

	public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$sheets[] = [
			'name' => $this->alias(),
			'table' => $this,
			'query' => $this->find(),
			'orientation' => 'landscape'
		];
	}

	public function onExcelBeforeWrite(Event $event, ArrayObject $settings, $rowProcessed, $percentCount) {
		if (empty($settings['entity']->user)) {
			$entity = $settings['entity'];
			return $this->delete($entity);
		}
	}

	public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query) {
		// Setting request data and modifying fetch condition
		$requestData = json_decode($settings['process']['params']);
		$academicPeriodId = $requestData->academic_period_id;
        $educationGradeId = $requestData->education_grade_id;
		$statusId = $requestData->status;

        $Class = TableRegistry::get('Institution.InstitutionClasses');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');

		if ($academicPeriodId!=0) {
			$query->where([$this->aliasField('academic_period_id') => $academicPeriodId]);
		}

        if ($educationGradeId!=0) {
            $query->where([$this->aliasField('education_grade_id') => $educationGradeId]);
        }

		if ($statusId!=0) {
			$query->where([$this->aliasField('student_status_id') => $statusId]);
		}

        $statusOptions = $this->StudentStatuses
            ->find('list', ['keyField' => 'id', 'valueField' => 'code'])
            ->toArray();
		
		$query
			->contain(['Users.Genders', 'Users.MainNationalities', 'Users.Nationalities.NationalitiesLookUp', 'Institutions.Areas', 'Institutions.AreaAdministratives', 'Institutions.Types', 'Institutions.Providers'])
			->select([
                'openemis_no' => 'Users.openemis_no', 'number' => 'Users.identity_number', 'username' => 'Users.username', 'code' => 'Institutions.code', 'preferred_nationality' => 'MainNationalities.name',
                'gender_name' => 'Genders.name', 'area_name' => 'Areas.name', 'area_code' => 'Areas.code', 'area_administrative_code' => 'AreaAdministratives.code', 'area_administrative_name' => 'AreaAdministratives.name', 'institution_type' => 'Types.name', 'institution_provider' => 'Providers.name',
                'class_name' => 'InstitutionClasses.name'
            ])
            ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                $ClassStudents->aliasField('student_status_id = ') . $this->aliasField('student_status_id'),
                $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
            ])
            ->leftJoin([$Class->alias() => $Class->table()], [
                $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
            ])
            ->formatResults(function (ResultSetInterface $results) use ($statusOptions, $statusId) {
                return $results->map(function ($row) use ($statusOptions, $statusId) {
                    $statusCode = $statusOptions[$statusId];

                    $studentId = $row['student_id'];
                    $institutionId = $row['institution_id'];
                    $educationGradeId = $row['education_grade_id'];
                    $academicPeriodId = $row['academic_period_id'];

                    switch ($statusCode) {
                        case 'TRANSFERRED':
                            $StudentAdmission = TableRegistry::get('Institution.StudentAdmission');
                            $query = $StudentAdmission->find()
                                    ->contain(['StudentTransferReasons', 'Institutions.Areas', 'Institutions.AreaAdministratives'])
                                    ->where([
                                        $StudentAdmission->aliasField('student_id') => $studentId,
                                        $StudentAdmission->aliasField('previous_institution_id') => $institutionId,
                                        $StudentAdmission->aliasField('education_grade_id') => $educationGradeId,
                                        $StudentAdmission->aliasField('academic_period_id') => $academicPeriodId,
                                        $StudentAdmission->aliasField('status') => 1 //approved
                                    ])
                                    ->first();
                            
                            if (!empty($query)) {
                                $row['transfer_institution'] = $query->institution->code_name;
                                $row['transfer_institution_area_name'] = $query->institution->area->name;
                                $row['transfer_institution_area_code'] = $query->institution->area->code;
                                $row['transfer_institution_area_administrative_name'] = $query->institution->area_administrative->name;
                                $row['transfer_institution_area_administrative_code'] = $query->institution->area_administrative->code;
                                $row['transfer_comment'] = $query->comment;
                                $row['transfer_reason'] = $query->student_transfer_reason->name;
                            }
                            break;

                        case 'WITHDRAWN':
                            $StudentWithdraw = TableRegistry::get('Institution.StudentWithdraw');
                            $studentWithdrawEntity = $StudentWithdraw
                                ->find()
                                ->contain(['StudentWithdrawReasons'])
                                ->where([
                                    $StudentWithdraw->aliasField('student_id') => $studentId,
                                    $StudentWithdraw->aliasField('institution_id') => $institutionId,
                                    $StudentWithdraw->aliasField('education_grade_id') => $educationGradeId,
                                    $StudentWithdraw->aliasField('academic_period_id') => $academicPeriodId,
                                    $StudentWithdraw->aliasField('status') => 1 //approved
                                ])
                                ->first();
                            
                            if (!empty($studentWithdrawEntity)) {
                                $row['withdraw_comment'] = $studentWithdrawEntity->comment;
                                $row['withdraw_reason'] = $studentWithdrawEntity->student_withdraw_reason->name;
                            }
                            break;

                        case 'GRADUATED':
                            break;

                        case 'PROMOTED':
                            break;

                        case 'REPEATED':
                            break;

                        default:
                            break;
                    }

                    return $row;
                });
            });
    }

	public function onExcelRenderAge(Event $event, Entity $entity, $attr) {
		$age = '';
		if ($entity->has('user')) {
			if ($entity->user->has('date_of_birth')) {
				if (!empty($entity->user->date_of_birth)) {
					$yearOfBirth = $entity->user->date_of_birth->format('Y');
					$age = date("Y")-$yearOfBirth;
				}
			}
		}
		return $age;
	}

    public function onExcelGetAllNationalities(Event $event, Entity $entity)
    {
        $return = [];
        if ($entity->has('user')) {
            if ($entity->user->has('nationalities')) {
                if (!empty($entity->user->nationalities)) {
                    foreach ($entity->user->nationalities as $userNationality) {
                        if ($userNationality->has('nationalities_look_up')) {
                            $return[] = $userNationality->nationalities_look_up->name;
                        }
                    }
                }
            }
        }

        return implode(', ', array_values($return));
    }

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
		$identity = $IdentityType->getDefaultEntity();

		$settings['identity'] = $identity;

        $requestData = json_decode($settings['process']['params']);
        $statusId = $requestData->status;

		// To update to this code when upgrade server to PHP 5.5 and above
		// unset($fields[array_search('institution_id', array_column($fields, 'field'))]);

		foreach ($fields as $key => $field) {
			if ($field['field'] == 'institution_id' || $field['field'] == 'previous_institution_student_id') {
				unset($fields[$key]);
				// break;
			}
		}
		
		$extraField[] = [
			'key' => 'Institutions.code',
			'field' => 'code',
			'type' => 'string',
			'label' => ''
		];

        if ($statusId == $this->statuses['TRANSFERRED']) {
    		$extraField[] = [
    			'key' => 'Students.institution_id',
    			'field' => 'institution_id',
    			'type' => 'integer',
    			'label' => __('Institution Transferred From')
    		];
        } else {
            $extraField[] = [
                'key' => 'Students.institution_id',
                'field' => 'institution_id',
                'type' => 'integer',
                'label' => ''
            ];
        }

		$extraField[] = [
			'key' => 'Institutions.institution_type_id',
			'field' => 'institution_type',
			'type' => 'integer',
			'label' => '',
		];


        $extraField[] = [
            'key' => 'Institutions.institution_provider_id',
            'field' => 'institution_provider',
            'type' => 'integer',
            'label' => '',
        ];

        $extraField[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => '',
            'formatting' => 'string'
        ];

        $extraField[] = [
            'key' => 'Users.username',
            'field' => 'username',
            'type' => 'string',
            'label' => __('Username'),
            'formatting' => 'string'
        ];

        $extraField[] = [
            'key' => 'Users.identity_number',
            'field' => 'number',
            'type' => 'string',
            'label' => __($identity->name),
            'formatting' => 'string'
        ];

		$extraField[] = [
			'key' => 'Users.gender_id',
			'field' => 'gender_name',
			'type' => 'string',
			'label' => ''
		];

        if ($statusId == $this->statuses['TRANSFERRED']) {
            $extraField[] = [
                'key' => 'Institutions.area_code',
                'field' => 'area_code',
                'type' => 'string',
                'label' => __('Area Education Code Transferred From')
            ];

    		$extraField[] = [
    			'key' => 'Institutions.area_name',
    			'field' => 'area_name',
    			'type' => 'string',
    			'label' => __('Area Education Transferred From')
    		];

            $extraField[] = [
                'key' => 'Institutions.area_administrative_code',
                'field' => 'area_administrativecode',
                'type' => 'string',
                'label' => __('Area Administrative Code Transferred From')
            ];

    		$extraField[] = [
                'key' => 'Institutions.area_administrative_name',
                'field' => 'area_administrative_name',
                'type' => 'string',
                'label' => __('Area Administrative Transferred From')
            ];


        } else {
            $extraField[] = [
                'key' => 'Institutions.area_code',
                'field' => 'area_code',
                'type' => 'string',
                'label' => __('Area Education Code')
            ];

            $extraField[] = [
                'key' => 'Institutions.area_name',
                'field' => 'area_name',
                'type' => 'string',
                'label' => __('Area Education')
            ];

            $extraField[] = [
                'key' => 'Institutions.area_code',
                'field' => 'area_code',
                'type' => 'string',
                'label' => __('Area Administrative Code')
            ];

            $extraField[] = [
                'key' => 'Institutions.area_name',
                'field' => 'area_name',
                'type' => 'string',
                'label' => __('Area Administrative')
            ];
        } 

		$extraField[] = [
			'key' => 'Age',
			'field' => 'Age',
			'type' => 'Age',
			'label' => 'Age',
		];

        $newFields = array_merge($extraField, $fields->getArrayCopy());

        if ($statusId == $this->statuses['CURRENT']) {
            $enrolledExtraField[] = [
                'key' => 'InstitutionClasses.name',
                'field' => 'class_name',
                'type' => 'string',
                'label' => ''
            ];
        }

        $enrolledExtraField[] = [
            'key' => 'MainNationalities.name',
            'field' => 'preferred_nationality',
            'type' => 'string',
            'label' => __('Preferred Nationality')
        ];

        $enrolledExtraField[] = [
            'key' => 'NationalitiesLookUp.name',
            'field' => 'all_nationalities',
            'type' => 'string',
            'label' => __('All Nationalities')
        ];

        $newFields = array_merge($newFields, $enrolledExtraField);

        if ($statusId == $this->statuses['WITHDRAWN']) {
            $withdrawExtraField[] = [
                'key' => 'StudentWithdraw.comment',
                'field' => 'withdraw_comment',
                'type' => 'string',
                'label' => __('Withdraw Comment')
            ];

            $withdrawExtraField[] = [
                'key' => 'StudentWithdrawReasons.name',
                'field' => 'withdraw_reason',
                'type' => 'string',
                'label' => __('Withdraw Reason')
            ];

            $outputFields = array_merge($newFields, $withdrawExtraField);
            $fields->exchangeArray($outputFields);

        } else if ($statusId == $this->statuses['TRANSFERRED']) {
            $transferExtraField[] = [
                'key' => 'StudentTransfer.comment',
                'field' => 'transfer_comment',
                'type' => 'string',
                'label' => __('Transfer Comment')
            ];

            $transferExtraField[] = [
                'key' => 'StudentTransfer.name',
                'field' => 'transfer_reason',
                'type' => 'string',
                'label' => __('Transfer Reason')
            ];

            $transferExtraField[] = [
                'key' => 'StudentTransfer.institution_id',
                'field' => 'transfer_institution',
                'type' => 'string',
                'label' => __('Institution Transferred to')
            ];

            $transferExtraField[] = [
                'key' => 'StudentTransfer.institution_area_code',
                'field' => 'transfer_institution_area_code',
                'type' => 'string',
                'label' => __('Area Education Code Transferred to')
            ];

            $transferExtraField[] = [
                'key' => 'StudentTransfer.institution_area_name',
                'field' => 'transfer_institution_area_name',
                'type' => 'string',
                'label' => __('Area Education Transferred to')
            ];

            $transferExtraField[] = [
                'key' => 'StudentTransfer.institution_area_administrative_code',
                'field' => 'transfer_institution_area_administrative_code',
                'type' => 'string',
                'label' => __('Area Administrative Code Transferred to')
            ];

            $transferExtraField[] = [
                'key' => 'StudentTransfer.institution_area_administrative_name',
                'field' => 'transfer_institution_area_administrative_name',
                'type' => 'string',
                'label' => __('Area Administrative Transferred to')
            ];

            $outputFields = array_merge($newFields, $transferExtraField);
            $fields->exchangeArray($outputFields);

        } else {
            $fields->exchangeArray($newFields);
        }
	}
}