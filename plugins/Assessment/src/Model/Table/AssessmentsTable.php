<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;

class AssessmentsTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('AssessmentStatuses', ['className' => 'Assessment.AssessmentStatuses', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->addBehavior('Reorder');
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('type', ['visible' => false]);
		$this->ControllerAction->field('education_programmes');
		$this->ControllerAction->field('education_grade_id');
		$this->ControllerAction->field('assessment_items');

		$this->ControllerAction->setFieldOrder([
			'code', 'name', 'description', 'visible', 'education_programmes', 
			'education_grade_id', 'assessment_items'
		]);

		$gradingTypeOptions = $this->AssessmentItems->GradingTypes->getList()->toArray();
		if (empty($gradingTypeOptions)) {
			$this->Alert->warning('Assessments.noGradingTypes');
		}

		$this->controller->set('gradingTypeOptions', $gradingTypeOptions);
		$this->controller->set('markTypeOptions', $this->getSelectOptions($this->aliasField('mark_types')));
		$this->controller->set('selectedAction', $this->alias());
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder(['visible', 'code', 'name', 'description']);
	}

	public function onGetEducationProgrammes(Event $event, Entity $entity) {
		return $entity->education_grade->programme_name;
	}

	public function onUpdateFieldEducationProgrammes(Event $event, array $attr, $action, Request $request) {
		$attr['visible'] = ['index' => false, 'view' => true, 'edit' => true];
		
		$EducationGrades = $this->EducationGrades;
		$programmeOptions = $EducationGrades->EducationProgrammes
			->find('list')
			->find('visible')
			->find('order')
			->toArray()
			;

		$selectedProgramme = $this->queryString('education_programmes', $programmeOptions);
		$this->advancedSelectOptions($programmeOptions, $selectedProgramme, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
			'callable' => function($id) use ($EducationGrades) {
				return $EducationGrades->findAllByEducationProgrammeId($id)->find('visible')->count();
			}
		]);

		if ($action != 'view') {
			$attr['options'] = $programmeOptions;
		}
		$attr['onChangeReload'] = 'changeProgrammes';

		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		$programmeId = $request->query('education_programmes');

		if ($request->is('post')) {
			$programmeId = $request->data($this->aliasField('education_programmes'));
		}

		$gradeOptions = $this->EducationGrades
			->find('list')
			->find('visible')
			->find('order')
			->where([$this->EducationGrades->aliasField('education_programme_id') => $programmeId])
			->toArray();

		$attr['options'] = $gradeOptions;
		$attr['onChangeReload'] = 'changeGrades';

		return $attr;
	}

	public function onUpdateFieldAssessmentItems(Event $event, array $attr, $action, Request $request) {
		if ($action != 'index') {
			$attr['type'] = 'element';
			$attr['element'] = 'Assessment.Assessments/subjects';
			$attr['valueClass'] = 'table-full-width';
		} else {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain('AssessmentItems.EducationSubjects');
	}

	public function addAfterAction(Event $event, Entity $entity) {
		if ($this->request->is('get')) {
			$gradeId = key($this->fields['education_grade_id']['options']);
			$obj = $this->EducationGrades
				->findById($gradeId)
				->contain(['EducationSubjects'])
				->first();

			$entity->assessment_items = [];
			foreach ($obj->education_subjects as $subject) {
				if ($subject->_joinData->visible == 1) {
					$AssessmentItem = $this->AssessmentItems->newEntity();
					$AssessmentItem->pass_mark = 50;
					$AssessmentItem->max = 100;
					$AssessmentItem->visible = 1;
					$AssessmentItem->education_subject = $subject;
					$entity->assessment_items[] = $AssessmentItem;
				}
			}
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		foreach ($data[$this->alias()]['assessment_items'] as $i => $item) {
			if (strlen($item['pass_mark']) == 0) {
				$data[$this->alias()]['assessment_items'][$i]['pass_mark'] = 50;
			}
			if (strlen($item['max']) == 0) {
				$data[$this->alias()]['assessment_items'][$i]['max'] = 100;
			}
		}
	}

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$EducationSubjects = TableRegistry::get('Education.EducationSubjects');
		foreach ($entity->assessment_items as $item) {
			$item->education_subject = $EducationSubjects->get($item->education_subject_id);
		}
	}

	public function addEditOnChangeProgrammes(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$gradeId = key($this->fields['education_grade_id']['options']);
		$gradingTypeOptions = $this->ControllerAction->getVar('gradingTypeOptions');
		
		$this->populateAssessmentItems($data, $gradeId, ['gradingTypeOptions' => $gradingTypeOptions]);
	}

	public function addEditOnChangeGrades(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$gradeId = $data[$this->alias()]['education_grade_id'];
		$gradingTypeOptions = $this->ControllerAction->getVar('gradingTypeOptions');
		
		$this->populateAssessmentItems($data, $gradeId, ['gradingTypeOptions' => $gradingTypeOptions]);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$programmeId = $this->EducationGrades
			->findById($entity->education_grade_id)
			->select([$this->EducationGrades->aliasField('education_programme_id')])
			->first()
			->education_programme_id;

		$entity->education_programmes = $programmeId;
	}

	public function populateAssessmentItems($data, $gradeId, $options=[]) {
		$gradingTypeOptions = $options['gradingTypeOptions'];

		$obj = $this->EducationGrades
			->findById($gradeId)
			->contain(['EducationSubjects'])
			// ->contain([
			// 	'EducationGradesSubjects.EducationSubjects',
			// 	'EducationGradesSubjects' => function ($q) {
			// 		return $q->find('visible');
			// 	}
			// ])
			->first();

		$assessmentItems = [];
		foreach ($obj->education_subjects as $subject) {
			if ($subject->_joinData->visible == 1) {
				$item = [
					'id' => '',
					'visible' => 1,
					'education_subject_id' => $subject->id,
					'result_type' => key($this->getSelectOptions($this->aliasField('mark_types'))),
					'pass_mark' => 50,
					'max' => 100,
					'assessment_grading_type_id' => key($gradingTypeOptions),
					'education_subject' => $subject
				];
				$assessmentItems[] = $item;
			}
		}

		$data[$this->alias()]['assessment_items'] = $assessmentItems;
	}
}
