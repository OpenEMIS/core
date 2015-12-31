<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class InstitutionStudentClassroomRatioTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institutions');
		parent::initialize($config);

		$this->belongsTo('Areas', ['className' => 'Area.Areas']);

		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Excel', [
			'excludes' => ['name','alternative_name','code','address','postal_code','contact_person','telephone','fax','email','website','date_opened','year_opened','date_closed','year_closed','longitude','latitude', 'area_administrative_id', 'institution_locality_id','institution_type_id','institution_ownership_id','institution_status_id','institution_sector_id','institution_provider_id','institution_gender_id','institution_network_connectivity_id','security_group_id','modified_user_id','modified','created_user_id','created','selected'], 
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
			'name' => 'Student Classroom Ratio',
			'table' => $this,
			'query' => $this->find(),
			'orientation' => 'landscape'
		];
	}

	public function onExcelRenderStudentCount(Event $event, Entity $entity, $attr) {
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

  	public function onExcelRenderClassCount(Event $event, Entity $entity, $attr) {

  		$InstitutionSections = TableRegistry::get('Institution.InstitutionSections');
		$area_id = $entity->area_id;
		$query = $InstitutionSections->find();
		$query->matching('Institutions.Areas', function ($q) use ($area_id) {
				return $q->where(['Areas.id' => $area_id]);
			});

		$query->select(['totalClasses' => $query->func()->count('DISTINCT '.$InstitutionSections->aliasField('id'))])
			->group('Areas.id')
			;

		if (array_key_exists('academic_period_id', $attr) && !empty($attr['academic_period_id'])) {
			$query->where([
				$InstitutionSections->aliasField('academic_period_id') => $attr['academic_period_id']
			]);
		}

		$count = $query->first();
		$count = $count['totalClasses'];
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
			'key' => 'ClassCount',
			'field' => 'ClassCount',
			'type' => 'ClassCount',
			'label' => 'Class Count',
			'academic_period_id' => $academicPeriodId
		];

		$newFields = array_merge($extraField, $fields->getArrayCopy());
		$fields->exchangeArray($newFields);
	}
}