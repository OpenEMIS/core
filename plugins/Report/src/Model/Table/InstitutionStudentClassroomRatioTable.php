<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use ControllerAction\Model\Traits\UtilityTrait;

class InstitutionStudentClassroomRatioTable extends AppTable  {
	use UtilityTrait;

	public function initialize(array $config) {
		$this->table('institutions');
		parent::initialize($config);

		$this->belongsTo('Areas', ['className' => 'Area.Areas']);
         $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);

		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Excel', [
			'excludes' => ['alternative_name','code','address','postal_code','contact_person','telephone','fax','email','website','date_opened','year_opened','date_closed','year_closed','longitude','latitude', 'area_id', 'area_administrative_id', 'institution_locality_id','institution_type_id','institution_ownership_id','institution_status_id','institution_sector_id','institution_provider_id','institution_gender_id','institution_network_connectivity_id','security_group_id','modified_user_id','modified','created_user_id','created','selected'], 
			'pages' => false
		]);
		$this->addBehavior('Report.InstitutionSecurity');
	}

	public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$sheets[] = [
			'name' => 'Student Classroom Ratio',
			'table' => $this,
			'query' => $this->find(),
			'orientation' => 'landscape'
		];
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$requestData = json_decode($settings['process']['params']);
		$superAdmin = $requestData->super_admin;
		$userId = $requestData->user_id;
		if (!$superAdmin) {
			$query->find('ByAccess', ['user_id' => $userId, 'institution_field_alias' => $this->aliasField('id')]);
		}
        $query
            ->contain(['Areas', 'AreaAdministratives'])
            ->select(['area_code' => 'Areas.code', 'area_name' => 'Areas.name', 'area_administrative_code' => 'AreaAdministratives.code', 'area_administrative_name' => 'AreaAdministratives.name']);
	}

	public function onExcelRenderStudentCount(Event $event, Entity $entity, $attr) {
		$InstitutionStudents = TableRegistry::get('Institution.Students');
		$institutionId = $entity->id;
		$query = $InstitutionStudents->find();
		$query->matching('Institutions', function ($q) use ($institutionId) {
			return $q->where(['Institutions.id' => $institutionId]);
		});
		$query->select(['totalStudents' => $query->func()->count('DISTINCT '.$InstitutionStudents->aliasField('student_id'))])
			->group('Institutions.id')
			;
		if (array_key_exists('academic_period_id', $attr) && !empty($attr['academic_period_id'])) {
			$query->where([
				$InstitutionStudents->aliasField('academic_period_id') => $attr['academic_period_id']
			]);
		}
		$count = $query->first();
		$count = $count['totalStudents'];

		if (array_key_exists('recordObj', $attr) && !empty($attr['recordObj'])) {
			$recordObj = $attr['recordObj'];
			$recordObj['studentCount'] = $count;
		}

  		return $count;
  	}

  	public function onExcelRenderClassCount(Event $event, Entity $entity, $attr) {
  		$InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
		$institutionId = $entity->id;
		$query = $InstitutionClasses->find();
		$query->matching('Institutions', function ($q) use ($institutionId) {
			return $q->where(['Institutions.id' => $institutionId]);
		});

		$query->select(['totalClasses' => $query->func()->count('DISTINCT '.$InstitutionClasses->aliasField('id'))])
			->group('Institutions.id')
			;

		if (array_key_exists('academic_period_id', $attr) && !empty($attr['academic_period_id'])) {
			$query->where([
				$InstitutionClasses->aliasField('academic_period_id') => $attr['academic_period_id']
			]);
		}

		$count = $query->first();
		$count = $count['totalClasses'];

		if (array_key_exists('recordObj', $attr) && !empty($attr['recordObj'])) {
			$recordObj = $attr['recordObj'];
			$recordObj['totalClasses'] = $count;
		}

  		return $count;
  	}

  	public function onExcelRenderAcademicPeriod(Event $event, Entity $entity, $attr) {
		if (array_key_exists('academic_period_name', $attr) && !empty($attr['academic_period_name'])) {
			return $attr['academic_period_name'];
		} else {
			return '';
		}
	}

	public function onExcelRenderStudentClassRatio(Event $event, Entity $entity, $attr) {
		if (array_key_exists('recordObj', $attr) && !empty($attr['recordObj'])) {
			if (!empty($attr['recordObj']['studentCount']) && !empty($attr['recordObj']['totalClasses'])) {
				$gcd = $this->gCD($attr['recordObj']['studentCount'],$attr['recordObj']['totalClasses']);
				return $attr['recordObj']['studentCount']/$gcd .':'.$attr['recordObj']['totalClasses']/$gcd;
			}
		}
		// if missing information, or zero in a single field, return nothing
		return '';
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$requestData = json_decode($settings['process']['params']);
		$academicPeriodId = $requestData->academic_period_id;

		// get the name of the academic period id
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$currAcademicPeriod = $AcademicPeriod->find()
				->where([$AcademicPeriod->aliasField($AcademicPeriod->primaryKey()) => $academicPeriodId])
				->first()
				;
		$currAcademicPeriodName = ($currAcademicPeriod->has('name'))? $currAcademicPeriod->name: '';

		$recordObj = new ArrayObject([]);

		$extraField[] = [
			'key' => 'AcademicPeriod',
			'field' => 'AcademicPeriod',
			'type' => 'AcademicPeriod',
			'label' => 'Academic Period',
			'academic_period_name' => $currAcademicPeriodName
		];

		$newFields = array_merge($extraField, $fields->getArrayCopy());
		$extraField = [];

		$extraField[] = [
			'key' => 'StudentCount',
			'field' => 'StudentCount',
			'type' => 'StudentCount',
			'label' => 'Student Count',
			'academic_period_id' => $academicPeriodId,
			'recordObj' => $recordObj
		];

		$extraField[] = [
			'key' => 'ClassCount',
			'field' => 'ClassCount',
			'type' => 'ClassCount',
			'label' => 'Class Count',
			'academic_period_id' => $academicPeriodId,
			'recordObj' => $recordObj
		];

		$extraField[] = [
			'key' => 'StudentClassRatio',
			'field' => 'StudentClassRatio',
			'type' => 'StudentClassRatio',
			'label' => 'Student Class Ratio',
			'recordObj' => $recordObj
		];
		$newFields = array_merge($newFields, $extraField);
		
		$fields->exchangeArray($newFields);

        $cloneFields = $fields->getArrayCopy();
        $newFields = [];
        foreach ($cloneFields as $key => $value) {
            $newFields[] = $value;
            if ($value['field'] == 'name') {
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
                    'key' => 'AreaAdministratives.code',
                    'field' => 'area_administrative_code',
                    'type' => 'string',
                    'label' => __('Area Administrative Code')
                ];

                $newFields[] = [
                    'key' => 'AreaAdministratives.name',
                    'field' => 'area_administrative_name',
                    'type' => 'string',
                    'label' => __('Area Administrative')
                ];
            }
        }
        $fields->exchangeArray($newFields);
	}
}