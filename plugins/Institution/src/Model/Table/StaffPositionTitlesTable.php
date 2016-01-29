<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class StaffPositionTitlesTable extends AppTable {
	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        $this->table('staff_position_titles');
        parent::initialize($config);
        $this->hasMany('Titles', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'staff_position_title_id']);
        $this->hasMany('TrainingCoursesTargetPopulations', ['className' => 'Training.TrainingCoursesTargetPopulations', 'foreignKey' => 'target_population_id']);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => 'transfer'],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
	}

	public function beforeAction($event) {
		$this->field('type', [
			'visible' => true,
			'type' => 'select',
			'options' => $this->getSelectOptions('Staff.position_types'),
			'after' => 'name'
		]);
	}

	public function onGetType(Event $event, Entity $entity) {
		$types = $this->getSelectOptions('Staff.position_types');
		return array_key_exists($entity->type, $types) ? $types[$entity->type] : $entity->type;
	}

	public function indexBeforeAction(Event $event) {
		$this->field('type', ['after' => 'name', 'visible' => true]);
	}
}
