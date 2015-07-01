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
		// $this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true]);

		$this->belongsToMany('AcademicPeriods', [
			'className' => 'AcademicPeriod.AcademicPeriods',
			'joinTable' => 'assessment_status_periods',
			'foreignKey' => 'assessment_status_id',
			'targetForeignKey' => 'academic_period_id'
		]);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('assessment_id', ['type' => 'select']);
		$this->ControllerAction->field('academic_period_level_id', ['type' => 'select']);
		$this->ControllerAction->field('academic_periods', [
			'type' => 'chosenSelect',
			'fieldNameKey' => 'academic_periods',
			'fieldName' => $this->alias() . '.academic_periods._ids',
			// 'placeholder' => $this->getMessage('Users.select_teacher')
		]);

		$this->ControllerAction->setFieldOrder([
			'assessment_id', 'date_enabled', 'date_disabled', 'academic_period_level_id'
		]);
	}
}
