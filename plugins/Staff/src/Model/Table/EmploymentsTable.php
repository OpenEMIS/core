<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class EmploymentsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('staff_employments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('EmploymentTypes', ['className' => 'FieldOption.EmploymentTypes']);

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

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('file_content');
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
            if ($this->controller->plugin == 'Staff') {
                $action = 'Employments';
            } else if ($this->controller->plugin == 'Directory') {
                $action = 'StaffEmployments';
            }
            
            $url = $event->subject()->HtmlField->link($entity->employment_type->name, [
                        'plugin' => $this->controller->plugin,
                        'controller' => $this->controller->name,
                        'action' => $action,
                        'view',
                        $this->paramsEncode(['id' => $entity->id])
                    ]);
            return $url;
        }
    }
}
