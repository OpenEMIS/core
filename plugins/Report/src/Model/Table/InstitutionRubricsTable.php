<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class InstitutionRubricsTable extends AppTable {
	const EXPIRED = -1;
	const NEW_SURVEY = 0;
	const DRAFT = 1;
	const COMPLETED = 2;

	public function initialize(array $config) {
		$this->table('institution_quality_rubrics');
		parent::initialize($config);

		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('Classes', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
		$this->belongsTo('Subjects', ['className' => 'Institution.InstitutionSubjects', 'foreignKey' => 'institution_subject_id']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->hasMany('InstitutionRubricAnswers', ['className' => 'Institution.InstitutionRubricAnswers', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->addBehavior('Excel', [
			'excludes' => ['comment'],
			'pages' => false
		]);
		$this->addBehavior('Report.RubricsReport');
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) {
		$controllerName = $this->controller->name;
		$reportName = __('Rubrics');
		$this->controller->Navigation->substituteCrumb($this->alias(), $reportName);
		$this->controller->set('contentHeader', __($controllerName).' - '.$reportName);
		$this->fields = [];
		$this->ControllerAction->field('feature', ['select' => false]);
		$this->ControllerAction->field('format');
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('rubric_template_id', ['type' => 'hidden']);
		$this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
		$this->ControllerAction->field('status', ['type' => 'hidden']);
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['options'] = $this->controller->getFeatureOptions($this->alias());
			$attr['onChangeReload'] = true;
			if (!(isset($this->request->data[$this->alias()]['feature']))) {
				$option = $attr['options'];
				reset($option);
				$this->request->data[$this->alias()]['feature'] = key($option);
			}
			return $attr;
		}
	}

	public function onUpdateFieldRubricTemplateId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			if (isset($this->request->data[$this->alias()]['feature'])) {
				$feature = $this->request->data[$this->alias()]['feature'];
				if ($feature == $this->registryAlias()) {
					$templateOptions = $this
						->find('list', [
							'keyField' => 'rubric_template_id',
							'valueField' => 'template_name'
						])
						->matching('RubricTemplates')
						->select(['rubric_template_id' => $this->aliasField('rubric_template_id'), 'template_name' => 'RubricTemplates.name'])
						->group([$this->aliasField('rubric_template_id')])
						->toArray();
					$attr['options'] = $templateOptions;
					$attr['onChangeReload'] = true;
					$attr['type'] = 'select';
					if (empty($this->request->data[$this->alias()]['rubric_template_id'])) {
						$option = $attr['options'];
						reset($option);
						$this->request->data[$this->alias()]['rubric_template_id'] = key($option);
					}
					return $attr;
				}
			}
		}
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			if (isset($this->request->data[$this->alias()]['feature']) && isset($this->request->data[$this->alias()]['rubric_template_id'])) {
				$feature = $this->request->data[$this->alias()]['feature'];
				$templateId = $this->request->data[$this->alias()]['rubric_template_id'];
				if ($feature == $this->registryAlias() && !empty($templateId)) {
					$academicPeriodOptions = $this
						->find('list', [
							'keyField' => 'id',
							'valueField' => 'name'
						])
						->contain(['AcademicPeriods'])
						->select(['id' => 'AcademicPeriods.id', 'name' => 'AcademicPeriods.name'])
						->where([
							$this->aliasField('rubric_template_id') => $templateId
						])
						->group([
							$this->aliasField('rubric_template_id'),
							$this->aliasField('academic_period_id')
						])
						->order(['AcademicPeriods.order'])
						->toArray();
					$attr['options'] = $academicPeriodOptions;
					$attr['onChangeReload'] = true;
					$attr['type'] = 'select';
					if (empty($this->request->data[$this->alias()]['academic_period_id'])) {
						$option = $attr['options'];
						reset($option);
						$this->request->data[$this->alias()]['academic_period_id'] = key($option);
					}
					return $attr;
				}
			}
		}
	}

	public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			if (isset($this->request->data[$this->alias()]['feature'])
				&& isset($this->request->data[$this->alias()]['rubric_template_id'])
				&& isset($this->request->data[$this->alias()]['academic_period_id'])) {

				$feature = $this->request->data[$this->alias()]['feature'];
				$templateId = $this->request->data[$this->alias()]['rubric_template_id'];
				$academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];

				if ($feature == $this->registryAlias() && !empty($academicPeriodId)) {

					$attr['options'] = [
						self::COMPLETED => __('Completed'),
						'-1' => __('Not Completed')
					];

					$attr['type'] = 'select';

					$rubricsTable = $this;
					$selected = self::COMPLETED;

					$this->advancedSelectOptions($attr['options'], $selected, [
						'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noRubrics')),
						'callable' => function($id) use ($rubricsTable, $templateId, $academicPeriodId) {

							$query = $rubricsTable->find('list', [
								'keyField' => 'rubricsStatus',
								'valueField' => 'statusCount'
							]);

							// Add a case to check if the rubrics is completed or not
							$completedRubrics = $query->newExpr()->addCase(
								[$query->newExpr()->eq($this->aliasField('status'), self::COMPLETED)],
								[self::COMPLETED, -1],
								['integer', 'integer']);

							$query->select([
									'rubricsStatus' => $completedRubrics,
									'statusCount' => $query->func()->count($this->aliasField('id'))
								])
								->group(['rubricsStatus'])
								->where([
									$rubricsTable->aliasField('rubric_template_id') => $templateId,
									$rubricsTable->aliasField('academic_period_id') => $academicPeriodId
								]);

							return $query->having(['rubricsStatus' => $id])->count();
						}
					]);
					return $attr;
				}
			}
		}
	}

	public function onExcelGetStatus(Event $event, Entity $entity) {
		$status = $entity->status;
		switch ($status) {
			case self::COMPLETED:
				return __('Completed');
				break;
			default:
				return __('Not Completed');
				break;
		}
	}
}
