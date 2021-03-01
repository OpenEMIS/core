<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class InstitutionProgrammesTable extends AppTable  {
	use OptionsTrait;

	public function initialize(array $config) {
		$this->table('institution_grades');
		parent::initialize($config);

		$this->belongsTo('EducationGrades', 	['className' => 'Education.EducationGrades']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions']);
		
		$this->addBehavior('Excel', ['excludes' => ['start_year', 'end_year', 'institution_programme_id']]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.InstitutionSecurity');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
	{
		$query
			->contain(['Institutions.Areas', 'Institutions.AreaAdministratives','EducationGrades.EducationProgrammes'])
			->select(['area_code' => 'Areas.code', 'area_name' => 'Areas.name', 'area_administrative_code' => 'AreaAdministratives.code', 'area_administrative_name' => 'AreaAdministratives.name','programmes' => 'EducationProgrammes.name']);
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions('Institutions');
		return $attr;
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
	{
		// echo "<pre>"; print_r($fields); die();
		$cloneFields = $fields->getArrayCopy();
		$newFields = [];
		foreach ($cloneFields as $key => $value) {
			$newFields[0] = [
					'key' => 'EducationProgrammes.name',
					'field' => 'programmes',
					'type' => 'string',
					'label' => __('Programmes')
				];
			$newFields[] = $value;
			if ($value['field'] == 'institution_id') {
				

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

	public function onExcelGetStartDate(Event $event, Entity $entity) {
		return $this->formatDate($entity->start_date);
	}

	public function onExcelGetEndDate(Event $event, Entity $entity) {
		if (!empty($entity->end_date)) {
			return $this->formatDate($entity->end_date);
		}
	}

	public function onExcelGetInstitutionId(Event $event, Entity $entity) {
		return $entity->institution->code_name;
	}
}
