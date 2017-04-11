<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;

use App\Model\Table\ControllerActionTable;

class StaffTrainingsTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('StaffTrainingCategories', ['className' => 'Staff.StaffTrainingCategories']);
	}

	public function beforeAction(Event $event, ArrayObject $extra)
	{
		$userId = $this->Session->read('Staff.Staff.id');
		$this->field('staff_id', ['type' => 'hidden', 'value' => $userId]);
		$this->field('staff_training_category_id', ['type' => 'select']);
		$this->field('completed_date', ['default_date' => true]);
	}

	private function setupTabElements()
	{
		$tabElements = $this->controller->getInstitutionTrainingTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Trainings');
	}

	public function afterAction(Event $event)
	{
		$this->setupTabElements();
	}
}
