<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class UserEmploymentsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('user_employments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
	}

	public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('date_to', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'date_from', true]
            ]);
    }

	public function beforeAction(Event $event, ArrayObject $extra) {
        $this->setupTabElements();
	}

	private function setupTabElements() {
		$options['type'] = $this->controller->name;
		$tabElements = $this->controller->getProfessionalTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', __('Employments'));
	}
}
