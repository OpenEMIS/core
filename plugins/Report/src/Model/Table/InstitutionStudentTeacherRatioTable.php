<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class InstitutionStudentTeacherRatioTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institutions');
		parent::initialize($config);

		$this->belongsTo('Areas', ['className' => 'Area.Areas']);

		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Excel', [
			'excludes' => ['name','alternative_name','code','address','postal_code','contact_person','telephone','fax','email','website','date_opened','year_opened','date_closed','year_closed','longitude','latitude', 'area_administrative_id', 'institution_locality_id','institution_type_id','institution_ownership_id','institution_status_id','institution_sector_id','institution_provider_id','institution_gender_id','institution_network_connectivity_id','security_group_id','modified_user_id','modified','created_user_id','created','selected'], 
			// 
			'pages' => false
		]);


	}

	public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query) {
		$query->contain(['Areas']);
		$query->select([
			'Areas.name',
			'Areas.code'
		]);
		$query->group([
			'Areas.id'
		]);
	}

	public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$sheets[] = [
			'name' => $this->alias(),
			'table' => $this,
			'query' => $this->find(),
			'orientation' => 'landscape'
		];
	}

	public function onExcelRenderStudentCount(Event $event, Entity $entity, $attr) {
		// pr();
		// get all institution students where institution area id = that
		$InstitutionStudents = TableRegistry::get('Institution.Students');
		$area_id = $entity->area_id;
		$query = $InstitutionStudents->find();
		$query->matching('Institutions.Areas', function ($q) use ($area_id) {
				return $q->where(['Areas.id' => $area_id]);
			});
		$query->select(['totalStudents' => $query->func()->count('DISTINCT '.$InstitutionStudents->aliasField('student_id'))])
			->group('Areas.id')
			;
		if (array_key_exists('academic_period_id', $attr) && !empty($attr['academic_period_id'])) {
			$query->where([
				$InstitutionStudents->aliasField('academic_period_id') => $attr['academic_period_id']
			]);
		}
		$count = $query->first();
		$count = $count['totalStudents'];
  		return $count;
  	}

  	public function onExcelRenderStaffCount(Event $event, Entity $entity, $attr) {
  		if (array_key_exists('academic_period_id', $attr) && !empty($attr['academic_period_id'])) {
  			$academic_period_id = $attr['academic_period_id'];
  			$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
  			$currAcademicPeriod = $AcademicPeriod->find()
  				->where([$AcademicPeriod->aliasField($AcademicPeriod->primaryKey()) => $academic_period_id])
  				->first()
  				;
  			if(!empty($currAcademicPeriod)) {
  				$periodStartDate = $currAcademicPeriod->start_date->format('Y-m-d');
  				$periodEndDate = $currAcademicPeriod->end_date->format('Y-m-d');
  			}
  		}
		// get all institution Staff where institution area id = that
		$InstitutionStaff = TableRegistry::get('Institution.Staff');
		$area_id = $entity->area_id;
		$query = $InstitutionStaff->find();
		$query->matching('Institutions.Areas', function ($q) use ($area_id) {
			return $q->where(['Areas.id' => $area_id]);
		});	
		
		$query->select(['totalStaff' => $query->func()->count('DISTINCT '.$InstitutionStaff->aliasField('Staff_id'))]);

		if(!empty($currAcademicPeriod)) {
			$overlapDateCondition = [];
			$overlapDateCondition['OR'] = [
				'OR' => [
					[
						$InstitutionStaff->aliasField('end_date') . ' IS NOT NULL',
						$InstitutionStaff->aliasField('start_date') . ' <=' => $periodStartDate,
						$InstitutionStaff->aliasField('end_date') . ' >=' => $periodStartDate
					],
					[
						$InstitutionStaff->aliasField('end_date') . ' IS NOT NULL',
						$InstitutionStaff->aliasField('start_date') . ' <=' => $periodEndDate,
						$InstitutionStaff->aliasField('end_date') . ' >=' => $periodEndDate
					],
					[
						$InstitutionStaff->aliasField('end_date') . ' IS NOT NULL',
						$InstitutionStaff->aliasField('start_date') . ' >=' => $periodStartDate,
						$InstitutionStaff->aliasField('end_date') . ' <=' => $periodEndDate
					]
				],
				[
					$InstitutionStaff->aliasField('end_date') . ' IS NULL',
					$InstitutionStaff->aliasField('start_date') . ' <=' => $periodEndDate
				]
			];
			$query->where($overlapDateCondition);
		}

		$query->group('Areas.id');

		$count = $query->first();
		$count = $count['totalStaff'];
  		return $count;
  	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$requestData = json_decode($settings['process']['params']);
		$academicPeriodId = $requestData->academic_period_id;

		$extraField[] = [
			'key' => 'StudentCount',
			'field' => 'StudentCount',
			'type' => 'StudentCount',
			'label' => 'Student Count',
			'academic_period_id' => $academicPeriodId
		];

		$extraField[] = [
			'key' => 'StaffCount',
			'field' => 'StaffCount',
			'type' => 'StaffCount',
			'label' => 'Staff Count',
			'academic_period_id' => $academicPeriodId
		];

		$newFields = array_merge($extraField, $fields->getArrayCopy());
		$fields->exchangeArray($newFields);
	}
}