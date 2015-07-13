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

class AssessmentStatusesTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
		$this->belongsToMany('AcademicPeriods', [
			'className' => 'AcademicPeriod.AcademicPeriods',
			'joinTable' => 'assessment_status_periods',
			'foreignKey' => 'assessment_status_id',
			'targetForeignKey' => 'academic_period_id',
			'through' => 'Assessment.AssessmentStatusPeriods',
			'dependent' => true
		]);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('assessment_id', ['type' => 'select']);
		$this->ControllerAction->field('academic_period_level');
		$this->ControllerAction->field('academic_periods');

		$this->ControllerAction->setFieldOrder([
			'assessment_id', 'date_enabled', 'date_disabled', 'academic_period_level', 'academic_periods'
		]);
	}

	public function onUpdateFieldAcademicPeriodLevel(Event $event, array $attr, $action, Request $request) {
		$AcademicPeriodLevels = TableRegistry::get('AcademicPeriod.AcademicPeriodLevels');
		$levelOptions = $AcademicPeriodLevels->getList()->toArray();

		$attr['options'] = $levelOptions;
		$attr['onChangeReload'] = 'changePeriod';
		if ($action != 'add') {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldAcademicPeriods(Event $event, array $attr, $action, Request $request) {
		$selectedLevel = key($this->fields['academic_period_level']['options']);
		if ($request->is('post')) {
			$selectedLevel = $request->data($this->aliasField('academic_period_level'));
		}

		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriods
			->find('list')
			->find('visible')
			->find('order')
			->where([$AcademicPeriods->aliasField('academic_period_level_id') => $selectedLevel])
			->toArray();
		
		$attr['type'] = 'chosenSelect';
		$attr['options'] = $periodOptions;
		return $attr;
	}

	// contain is necessary for chosenSelect
	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$options['contain'][] = 'AcademicPeriods';
	}

	// contain is necessary for chosenSelect
	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain('AcademicPeriods');
	}
}
