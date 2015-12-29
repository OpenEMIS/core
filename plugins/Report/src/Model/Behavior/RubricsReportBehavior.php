<?php
namespace Report\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\ORM\Table;

class RubricsReportBehavior extends Behavior {
	const EXPIRED = -1;
	const NEW_SURVEY = 0;
	const DRAFT = 1;
	const COMPLETED = 2;

	protected $_defaultConfig = [
		'events' => [
			'Model.excel.onExcelBeforeStart' => ['callable' => 'onExcelBeforeStart', 'priority' => 100],
			'Model.excel.onExcelUpdateFields' => ['callable' => 'onExcelUpdateFields', 'priority' => 110],
			'Model.excel.onExcelRenderRubrics' => ['callable' => 'onExcelRenderRubrics', 'priority' => 120],
		],
	];

	// For rubrics report
	private $_totalPoints = 0;
	private $_sectionPoints = 0;
	private $_rubricCriteriaOptions = [];
	private $_rubricTemplateOptions = [];

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events = array_merge($events, $this->config('events'));
    	return $events;
	}
	public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$requestData = json_decode($settings['process']['params']);
		$templateId = $requestData->rubric_template_id;
		$academicPeriodId = $requestData->academic_period_id;
		$status = $requestData->status;
		$condition = [
			$this->_table->aliasField('rubric_template_id') => $templateId,
			$this->_table->aliasField('academic_period_id') => $academicPeriodId,
		];

		$statusCondition = [];
		if ($status == self::COMPLETED) {
			$statusCondition = [
				$this->_table->aliasField('status') => self::COMPLETED
			];
		} else {
			$statusCondition = [
				$this->_table->aliasField('status').' IS NOT' => self::COMPLETED
			];
		}
		$condition = array_merge($condition, $statusCondition);

		$sheets[] = [
    		'name' => $this->_table->alias(),
			'table' => $this->_table,
			'query' => $this->_table->find()->where($condition),
			'orientation' => 'landscape',
			'templateId' => $templateId,
    	];
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$sheet = $settings['sheet'];
		$templateId = $sheet['templateId'];

		// Getting the institution site rubrics
		$RubricTemplatesTable = $this->_table->RubricTemplates;
		$weightingType = $RubricTemplatesTable->get($templateId)->weighting_type;

		$RubricTemplateOptionTable = $this->_table->RubricTemplates->RubricTemplateOptions;
		$this->_rubricTemplateOptions = $RubricTemplateOptionTable
			->find('list', [
				'keyField' => 'id',
				'valueField' => 'points',
			])
			->select([
				'id' => $RubricTemplateOptionTable->aliasField('id'), 
				'points' => $RubricTemplateOptionTable->aliasField('weighting')])
			->where([$RubricTemplateOptionTable->aliasField('rubric_template_id') => $templateId])
			->toArray();

		// Getting the section and the critieras
		$rubricSection = $this->getRubricTemplateSectionCriteria($templateId);
		
		// Get Maxiumum point for the template
		$maximumPoint = $this->getRubricTemplateOptionMaxWeighting($templateId);
		
		$totalPoints = 0;
		$sectionCounter = 0;
		foreach ($rubricSection as $section) {
			++$sectionCounter;
			$fields[] = [
				'key' => 'Rubric.RubricSections',
				'field' => 'rubricSection',
				'type' => 'rubrics',
				'label' => __('Section').' '.$sectionCounter,
				'id' => $section['id'],
				'name' => $section['name']
			];
			$sectionPoint = 0;
			$sectionHeaderCounter = 0;
			$criteriaCounter = 0;
			foreach ($section['rubric_criterias'] as $criteria) {
				$type = 'string';

				if ($criteria['type'] == 2) {
					$type = 'rubricCriteria';
					++$criteriaCounter;
					$sectionPoint += $maximumPoint;

					$fields[] = [
						'key' => 'Rubric.RubricCriterias',
						'field' => $type,
						'type' => 'rubrics',
						'label' => __('Criteria').' '.$sectionCounter.'.'.$sectionHeaderCounter.'.'.$criteriaCounter,
						'id' => $criteria['id'],
						'sectionId' => $section['id']
					];
				} elseif ($criteria['type'] == 1) {
					$type = 'sectionBreak';
					++$sectionHeaderCounter;
					$criteriaCounter = 0;

					$fields[] = [
						'key' => 'Rubric.RubricCriterias',
						'field' => $type,
						'type' => 'rubrics',
						'label' => _('Header').' '.$sectionCounter.'.'.$sectionHeaderCounter,
						'id' => $criteria['id'],
						'name' => $criteria['name']
					];
				}
			}

			$fields[] = [
				'key' => 'Rubric.SectionSubTotal',
				'field' => 'sectionSubtotal',
				'type' => 'rubrics',
				'label' => __('Sub Total').' ('.$sectionPoint.')',
				'points' => $sectionPoint
			];
			$totalPoints += $sectionPoint;
		}

		// $fields[] = [
		// 	'key' => 'Rubric.TemplateStatus',
		// 	'field' => 'rubricTemplateStatus',
		// 	'type' => 'rubrics',
		// 	'label' => __('Pass').'/'.__('Fail'),
		// 	'points' => $totalPoints,
		// 	'passMark' => $this->getTemplatePassingMark($templateId),
		// 	'weightingType' => $weightingType
		// ];

		$fields[] = [
			'key' => 'Rubric.TotalPoints',
			'field' => 'totalPoints',
			'type' => 'rubrics',
			'label' => __('Total').' ('.$totalPoints.')',
			'points' => $totalPoints,
		];

		$fields[] = [
			'key' => 'Rubric.TotalPercentage',
			'field' => 'totalPercentage',
			'type' => 'rubrics',
			'label' => __('Total').' (%)',
			'points' => $totalPoints,
		];
	}

	public function onExcelRenderRubrics(Event $event, Entity $entity, array $attr) {
		$type = $attr['field'];
		// To rewrite this part
		if (method_exists($this, $type)) {
			$ans = $this->$type($entity, $attr);
			if (empty($ans)) {
				return '';
			} else {
				return $ans;
			}
		} else {
			return '';
		}	
	}

	// Function to get the rubric template section and criteria
	public function getRubricTemplateSectionCriteria($templateId) {
		$RubricSectionTable = $this->_table->RubricTemplates->RubricSections;
		$rubricSection = $RubricSectionTable
			->find()
			->find('order')
			->contain(['RubricCriterias'])
			->where([$RubricSectionTable->aliasField('rubric_template_id') => $templateId])
			->hydrate(false)
			->toArray();
		return $rubricSection;
	}

	// Function to get the rubric template's maxmium weighting for each criteria
	public function getRubricTemplateOptionMaxWeighting($templateId) {
		$RubricTemplateOptionTable = $this->_table->RubricTemplates->RubricTemplateOptions;
		$rubricTemplateOptionQuery = $RubricTemplateOptionTable->find();
		$maximumPoint = $rubricTemplateOptionQuery
			->select(['maxpoint' => $rubricTemplateOptionQuery->func()->max($RubricTemplateOptionTable->aliasField('weighting'))])
			->where([$RubricTemplateOptionTable->aliasField('rubric_template_id') => $templateId])
			->first();
		return $maximumPoint['maxpoint'];
	}

	// Function to get the rubric criteria option base on the institution site quality rubric id
	public function getRubricCriteriaOptions($rubricId) {
		$RubricCriteriaOptionsTable = $this->_table->InstitutionRubricAnswers;
		$sectionAnswer = $RubricCriteriaOptionsTable->find()
			->contain(['RubricCriteriaOptions'])
			->where([$RubricCriteriaOptionsTable->aliasField('institution_quality_rubric_id') => $rubricId])
			->hydrate(false)
			->toArray();

		$newData = [];
		foreach ($sectionAnswer as $answer) {
			$newData[$answer['rubric_criteria_id']] = [
				'id' => $answer['rubric_criteria_option']['id'],
				'name' => $answer['rubric_criteria_option']['name'],
				'rubric_template_option_id' => $answer['rubric_criteria_option']['rubric_template_option_id']
			];
		}
		$data[$rubricId] = $newData;

		return $data;
	}

	// Function to get the passing mark of the template
	public function getTemplatePassingMark($templateId) {
		$RubricTemplatesTable = $this->_table->RubricTemplates;
		$passingMark = $RubricTemplatesTable->find()->where([$RubricTemplatesTable->aliasField('id') => $templateId])->first()->pass_mark;
		return $passingMark;
	}

	private function rubricSection($data, $attr) {
		$this->_sectionPoints = 0;
	}

	private function sectionHeader($data, $attr) {
		return $attr['name'];
	}

	private function rubricCriteria($data, $attr) {
		$rubricId = $data['id'];
		$criteriaId = $attr['id'];
		// Criteria options of the template
		$criteriaOptions = $this->_rubricCriteriaOptions;

		// Get Rubric answer
		$criteriaOptions = $this->_rubricCriteriaOptions;
		if (!isset($criteriaOptions[$rubricId])) {
			$criteriaOptions = $this->getRubricCriteriaOptions($rubricId);
			$this->_rubricCriteriaOptions = $this->getRubricCriteriaOptions($rubricId);
		}
		$templateOptions = $this->_rubricTemplateOptions;
		
		// Points for the criteria
		if (isset($criteriaOptions[$rubricId][$criteriaId]['rubric_template_option_id'])) {
			$templateOptionId = $criteriaOptions[$rubricId][$criteriaId]['rubric_template_option_id'];
			$points = $templateOptions[$templateOptionId];
			$this->_sectionPoints += $points;
			$this->_totalPoints += $points;
			// the = sign is needed so that if the number is 0 it can be recognised by excel as a number instead of boolean
			return '='.$points;
		} else {
			return '';
		}
	}

	private function sectionSubtotal($data, $attr) {
		// the = sign is needed so that if the number is 0 it can be recognised by excel as a number instead of boolean
		return '='.$this->_sectionPoints;
	}

	private function rubricTemplateStatus($data, $attr) {
		$totalPoint = $this->_totalPoints;
		$maximumPoint = $attr['points'];
		$weightingType = $attr['weightingType'];
		$passingMark = $attr['passMark'];
		$WEIGHTING_POINT = 1;
		$WEIGHTING_PERCENT = 2;
		$status = '';

		switch($weightingType) {
			case $WEIGHTING_POINT:
				if ($totalPoint >= $passingMark) {
					$status = __('Pass');
				} else {
					$status = __('Fail');
				}
				break;
			case $WEIGHTING_PERCENT:
				$percentage = $totalPoint / $maximumPoint * 100;
				if ($percentage >= $passingMark) {
					$status = __('Pass');
				} else {
					$status = __('Fail');
				}
				break;
		}

		return $status;
	}

	private function totalPoints($data, $attr) {
		// the = sign is needed so that if the number is 0 it can be recognised by excel as a number instead of boolean
		return '='.$this->_totalPoints;
	}

	private function totalPercentage($data, $attr) {
		$maxiumPoint = $attr['points'];
		$totalPoint = $this->_totalPoints;
		$percentage = $totalPoint / $maxiumPoint * 100;
		$this->_totalPoints = 0;
		return round($percentage, 2).'%';
	}
}
