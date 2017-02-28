<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
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
		$statusId = $requestData->status;

		if ($academicPeriodId!=0) {
			$query->where([
				$this->aliasField('academic_period_id') => $academicPeriodId
			]);
		}

		if ($statusId!=0) {
			$query->where([
				$this->aliasField('student_status_id') => $statusId
			]);
		}
		
		$query
			->contain(['Users.Genders', 'Users.MainNationalities', 'Institutions.Areas', 'Institutions.Types'])
			->select([
                'openemis_no' => 'Users.openemis_no', 'number' => 'Users.identity_number', 'code' => 'Institutions.code', 'preferred_nationality' => 'MainNationalities.name', 
                'gender_name' => 'Genders.name', 'area_name' => 'Areas.name', 'area_code' => 'Areas.code', 'institution_type' => 'Types.name'
            ]);
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

    public function onExcelGetWithdrawComment(Event $event, Entity $entity)
    {
        $StudentWithdraw = TableRegistry::get('Institution.StudentWithdraw');
        $query = $StudentWithdraw->find()
                ->where([
                    $StudentWithdraw->aliasField('student_id') => $entity->student_id,
                    $StudentWithdraw->aliasField('institution_id') => $entity->institution_id,
                    $StudentWithdraw->aliasField('education_grade_id') => $entity->education_grade_id,
                    $StudentWithdraw->aliasField('academic_period_id') => $entity->academic_period_id,
                    $StudentWithdraw->aliasField('status') => 1 //approved
                ])
                ->first();

        $entity->student_withdraw_reasons = '';
        if (!empty($query)) {
            $entity->student_withdraw_reasons = $query->student_withdraw_reason_id;
            return $query->comment;
        }
    }

    public function onExcelGetWithdrawReason(Event $event, Entity $entity)
    {
        $StudentWithdrawReasons = TableRegistry::get('Institution.StudentWithdrawReasons');

        if (!empty($entity->student_withdraw_reasons)) {
            return $StudentWithdrawReasons->get($entity->student_withdraw_reasons)->name;
        }
    }

    public function onExcelGetTransferComment(Event $event, Entity $entity)
    {
        $StudentAdmission = TableRegistry::get('Institution.StudentAdmission');
        $query = $StudentAdmission->find()
                ->where([
                    $StudentAdmission->aliasField('student_id') => $entity->student_id,
                    $StudentAdmission->aliasField('previous_institution_id') => $entity->institution_id,
                    $StudentAdmission->aliasField('education_grade_id') => $entity->education_grade_id,
                    $StudentAdmission->aliasField('academic_period_id') => $entity->academic_period_id,
                    $StudentAdmission->aliasField('status') => 1 //approved
                ])
                ->first();

        $entity->student_transfer_reasons = '';
        $entity->student_transfer_to_institution = '';
        if (!empty($query)) {
            $entity->student_transfer_reasons = $query->student_transfer_reason_id;
            $entity->student_transfer_to_institution = $query->institution_id;
            return $query->comment;
        }
    }

    public function onExcelGetTransferReason(Event $event, Entity $entity)
    {
        $StudentTransferReasons = TableRegistry::get('Institution.StudentTransferReasons');

        if (!empty($entity->student_transfer_reasons)) {
            return $StudentTransferReasons->get($entity->student_transfer_reasons)->name;
        }
    }

    public function onExcelGetTransferInstitution(Event $event, Entity $entity)
    {
        if (!empty($entity->student_transfer_to_institution)) {
            $query = $this->Institutions->get($entity->student_transfer_to_institution);
            $student_transfer_to_institution_area = '';
            if (!empty($query)) {
                $entity->student_transfer_to_institution_area = $query->area_id;
                return $query->code_name;
            }
        }
    }

    public function onExcelGetTransferInstitutionAreaName(Event $event, Entity $entity)
    {
        $Areas = TableRegistry::get('Area.Areas');
        if (!empty($entity->student_transfer_to_institution_area)) {
            $query = $Areas->get($entity->student_transfer_to_institution_area);
            if (!empty($query)) {
                $entity->student_transfer_to_institution_area_code = $query->code;
                return $query->name;
            }
        }
    }

    public function onExcelGetTransferInstitutionAreaCode(Event $event, Entity $entity)
    {
        if (!empty($entity->student_transfer_to_institution_area_code)) {
            return $entity->student_transfer_to_institution_area_code;
        }
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
			'key' => 'Users.openemis_no',
			'field' => 'openemis_no',
			'type' => 'string',
			'label' => ''
		];

		$extraField[] = [
			'key' => 'Users.identity_number',
			'field' => 'number',
			'type' => 'string',
			'label' => __($identity->name)
		];

		$extraField[] = [
			'key' => 'Users.gender_id',
			'field' => 'gender_name',
			'type' => 'string',
			'label' => ''
		];

        if ($statusId == $this->statuses['TRANSFERRED']) {
    		$extraField[] = [
    			'key' => 'Institutions.area_name',
    			'field' => 'area_name',
    			'type' => 'string',
    			'label' => __('Area Name Transferred From')
    		];

    		$extraField[] = [
    			'key' => 'Institutions.area_code',
    			'field' => 'area_code',
    			'type' => 'string',
    			'label' => __('Area Code Transferred From')
    		];
        } else {
            $extraField[] = [
                'key' => 'Institutions.area_name',
                'field' => 'area_name',
                'type' => 'string',
                'label' => ''
            ];

            $extraField[] = [
                'key' => 'Institutions.area_code',
                'field' => 'area_code',
                'type' => 'string',
                'label' => ''
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
            $withdrawExtraField[] = [
                'key' => 'MainNationalities.name',
                'field' => 'preferred_nationality',
                'type' => 'string',
                'label' => __('Preferred Nationality')
            ];

            $outputFields = array_merge($newFields, $withdrawExtraField);
            $fields->exchangeArray($outputFields);

        } else if ($statusId == $this->statuses['WITHDRAWN']) {
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
                'key' => 'StudentTransfer.institution_area_name',
                'field' => 'transfer_institution_area_name',
                'type' => 'string',
                'label' => __('Area Name Transferred to')
            ];

            $transferExtraField[] = [
                'key' => 'StudentTransfer.institution_area_code',
                'field' => 'transfer_institution_area_code',
                'type' => 'string',
                'label' => __('Area Code Transferred to')
            ];

            $outputFields = array_merge($newFields, $transferExtraField);
            $fields->exchangeArray($outputFields);

        } else {

            $fields->exchangeArray($newFields);

        }
	}
}