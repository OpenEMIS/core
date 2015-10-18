<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Institution\Model\Table\InstitutionRubricsTable;

class RubricsTable extends InstitutionRubricsTable  {
	public function initialize(array $config) {
		$this->table('institution_site_quality_rubrics');
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('Sections', ['className' => 'Institution.InstitutionSiteSections', 'foreignKey' => 'institution_site_section_id']);
		$this->belongsTo('Classes', ['className' => 'Institution.InstitutionSiteClasses', 'foreignKey' => 'institution_site_class_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->hasMany('InstitutionRubricAnswers', ['className' => 'Institution.InstitutionRubricAnswers', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->addBehavior('Excel', ['excludes' => ['status', 'comment'], 'pages' => ['view']]);
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
		$this->ControllerAction->field('rubric_template_id');
		$this->ControllerAction->field('academic_period_id');
	}

	public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {

		// Get the template id and the academic period id from the $settings['params']
		$templateId = 0;
		$academicPeriodId = 0;

		$sheets[] = [
			'name' => $this->alias(),
			'table' => $this,
			'query' => $this->find(),
			'rubric_template_id' => $templateId,
			'academic_period_id' => $academicPeriodId
		];
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$templateId = $settings['sheet']['rubric_template_id'];

		// Getting the section and the critieras
		$rubricSection = $this->getRubricTemplateSectionCriteria($templateId);
		
		// Get Maxiumum point for the template
		$maximumPoint = $this->getRubricTemplateOptionMaxWeighting($templateId);
		
		$totalPoints = 0;
		foreach ($rubricSection as $section) {
			$fields[] = [
				'key' => 'Rubric.RubricSections',
				'field' => 'rubric_section_id',
				'type' => 'section',
				'label' => $section['name']
			];
			$sectionPoint = 0;
			foreach ($section['rubric_criterias'] as $criteria) {
				$type = 'string';

				if ($criteria['type'] == 2) {
					$type = 'criteria';
					$sectionPoint += $maximumPoint;
				} elseif ($criteria['type'] == 1) {
					$type = 'section_break';
				}

				$fields[] = [
					'key' => 'Rubric.RubricCriterias',
					'field' => 'rubric_criteria_id',
					'type' => $type,
					'label' => $criteria['name']
				];
			}

			$fields[] = [
				'key' => 'Rubric.SectionSubTotal',
				'field' => 'section_subtotal',
				'type' => 'section_points',
				'label' => __('Sub Total').' ('.$sectionPoint.')',
				'points' => $sectionPoint
			];
			$totalPoints += $sectionPoint;
		}

		$fields[] = [
			'key' => 'Rubric.TotalPoints',
			'field' => 'total_points',
			'type' => 'total_points',
			'label' => __('Sub Total').' ('.$totalPoints.')',
			'points' => $totalPoints
		];

		$fields[] = [
			'key' => 'Rubric.TotalPercentage',
			'field' => 'total_percentage',
			'type' => 'total_percentage',
			'label' => __('Total').' (%)',
			'points' => $totalPoints
		];
	}
	public function onUpdateRubricTemplateId(Event $event, array $attr, $action, Request $request) {
		return $attr;
	}

	public function onUpdateAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		return $attr;
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		return $attr;
	}

	public function onGetReportName(Event $event, ArrayObject $data) {
		return __('Overview');
	}
}
