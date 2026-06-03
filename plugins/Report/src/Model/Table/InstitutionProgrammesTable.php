<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\FrozenTime;

class InstitutionProgrammesTable extends AppTable
{
	use OptionsTrait;

	public function initialize(array $config): void
	{
		$this->setTable('institution_grades');
		parent::initialize($config);

		$this->belongsTo('EducationGrades', 	['className' => 'Education.EducationGrades']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions']);

		$this->addBehavior('Excel', ['excludes' => ['start_year', 'end_year', 'institution_programme_id']]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.InstitutionSecurity');
	}

	public function beforeAction(EventInterface $event)
	{
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
	{
		$requestData = json_decode($settings['process']['params']);
		$institution_id = $requestData->institution_id;
		$periodId = $requestData->academic_period_id;
		$areaId = $requestData->area_education_id;
		$where = [];
		if ($institution_id != 0) {
			$where[$this->aliasField('institution_id')] = $institution_id;
		}
		if ($areaId != -1) {
			$where['Institutions.area_id'] = $areaId;
		}

		$query
			->contain(['Institutions.Areas', 'Institutions.AreaAdministratives', 'EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems.AcademicPeriods', 'Institutions.InstitutionTypes'])
			->select(['area_code' => 'Areas.code', 'area_name' => 'Areas.name', 'area_administrative_code' => 'AreaAdministratives.code', 'area_administrative_name' => 'AreaAdministratives.name', 'programmes' => 'EducationProgrammes.name', 'academic_period' => 'AcademicPeriods.name', 'institution_type' => 'InstitutionTypes.name'])
			->where(['AcademicPeriods.id' => $periodId, $where]);
	}

	public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, Request $request)
	{
		$attr['options'] = $this->controller->getFeatureOptions('Institutions');
		return $attr;
	}

	public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
	{
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

				//POCOR-9301[START]
				$newFields[] = [
					'key' => 'Institutions.code',
					'field' => 'institutionCode',
					'type' => 'string',
					'label' => __('Institution Code')
				];
				//POCOR-9301[END]
				
				$newFields[] = [
					'key' => 'AcademicPeriods.name',
					'field' => 'academic_period',
					'type' => 'string',
					'label' => __('Academic Period')
				];

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
				$newFields[] = [
					'key' => 'InstitutionTypes.name',
					'field' => 'institution_type',
					'type' => 'string',
					'label' => __('Institution Type')
				];

				//POCOR-9302 start
				if ($value['field'] == 'start_date') {
					$newFields[$key] = [
						'key' => 'InstitutionProgrammes.start_date',
						'field' => 'start_date',
						'type' => 'string',
						'label' => __('Start Date')
					];
				}

				if ($value['field'] == 'end_date') {
					$newFields[$key] = [
						'key' => 'InstitutionProgrammes.end_closed',
						'field' => 'end_date',
						'type' => 'string',
						'label' => __('End Date')
					];
				}

				//POCOR-9302 end
			}
		}
		
		//POCOR-9301[START]
		foreach ($newFields as $index => $field) {
			if (isset($field['field']) && $field['field'] === 'academic_period_id') {
				unset($newFields[$index]);
			}
			if (isset($field['field']) && $field['field'] === 'start_date') {
				unset($newFields[$index]);
			}
			if (isset($field['field']) && $field['field'] === 'end_date') {
				unset($newFields[$index]);
			}
		}
		//POCOR-9301[END]
		$fields->exchangeArray($newFields);
	}

	public function onExcelGetStartDate(EventInterface $event, Entity $entity) {
		if (!empty($entity->start_date)) {
			return $this->formatDate($entity->start_date);
		}
	}

	public function onExcelGetEndDate(EventInterface $event, Entity $entity)
	{
		if (!empty($entity->end_date)) {
			return $this->formatDate($entity->end_date);
		}
	}
	 //POCOR-9302 end

	//POCOR-9301[START]
	public function onExcelGetInstitutionCode(EventInterface $event, Entity $entity)
    {
        return $entity->institution->code;
    }

	// public function onExcelGetInstitutionId(EventInterface $event, Entity $entity) {
	// 	return $entity->institution->code_name;
	// }
	
	//POCOR-9301[END]
}
