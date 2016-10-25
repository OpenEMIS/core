<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class VisitRequestsTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		$this->table('institution_visit_requests');
		parent::initialize($config);
		$this->belongsTo('QualityVisitTypes', ['className' => 'FieldOption.QualityVisitTypes', 'foreignKey' => 'quality_visit_type_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

		// $this->addBehavior('AcademicPeriod.AcademicPeriod');
		$this->addBehavior('ControllerAction.FileUpload', [
			// 'name' => 'file_name',
			// 'content' => 'file_content',
			'size' => '10MB',
			'contentEditable' => true,
			'allowable_file_types' => 'all',
			'useDefaultName' => true
		]);
		$this->addBehavior('Institution.Visit');
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);

		return $validator
			->allowEmpty('file_content');
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('comment', ['visible' => false]);
		$this->field('file_name', ['visible' => false]);
		$this->field('file_content', ['visible' => false]);

		$this->setFieldOrder(['academic_period_id', 'date_of_visit', 'quality_visit_type_id']);
	}
}
