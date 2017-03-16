<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class EmploymentsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('staff_employments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('EmploymentTypes',` ['className' => 'FieldOption.EmploymentTypes']);

		$this->behaviors()->get('ControllerAction')->config('actions.search', false);
		$this->addBehavior('ControllerAction.FileUpload', [
			// 'name' => 'file_name',
			// 'content' => 'file_content',
			'size' => '2MB',
			'contentEditable' => true,
			'allowable_file_types' => 'all',
			'useDefaultName' => true
		]);
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('employment_type_id', ['type' => 'select', 'before' => 'employment_date']);

		$this->field('file_name', ['visible' => false]);
        $this->setupTabElements();
	}

	private function setupTabElements() {
		$options['type'] = 'staff';
		$tabElements = $this->controller->getCareerTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

    public function onGetEmploymentTypeId(Event $event, Entity $entity) 
    {
        if ($this->action == 'index') {
            $url = $event->subject()->HtmlField->link($entity->employment_type->name, [
                        'plugin' => $this->controller->plugin,
                        'controller' => $this->controller->name,
                        'action' => 'StaffEmployments',
                        'view',
                        $this->paramsEncode(['id' => $entity->id])
                    ]);
            return $url;
        }
    }
}
